// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";
import { createUploadFeedback } from "./_upload_feedback.js";

export default class extends Controller {
    static targets = ["announcer", "dropzone", "feedback", "imagePreview", "input", "list", "template"];

    static values = {
        url: String,
        accept: { type: String, default: "" },
        maxSizeBytes: { type: Number, default: 0 },
        maxFiles: { type: Number, default: 0 },
        multiple: { type: Boolean, default: false },
        mode: { type: String, default: "managed" },
        outputMode: { type: String, default: "full" },
        paramName: { type: String, default: "file" },
        responseKey: { type: String, default: "token" },
        previewUrlKey: { type: String, default: "preview_url" },
        hiddenName: { type: String, default: "" },
        deleteUrl: { type: String, default: "" },
        parallelUploads: { type: Number, default: 3 },
        view: { type: String, default: "list" },
        messages: { type: Object, default: {} },
    };

    initialize() {
        this.prepareForCache = this.prepareForCache.bind(this);
        this.refreshRootState = this.refreshRootState.bind(this);
    }

    connect() {
        this.disconnected = false;
        this.items = this.hydrateItems();
        this.nextId = this.nextAvailableId();
        this.activeUploads = 0;
        this.pendingStreamRenders = 0;
        this.streamRenderGeneration = {};
        this.deleteGeneration = {};
        this.feedback = createUploadFeedback({
            dropzone: () => this.hasDropzoneTarget ? this.dropzoneTarget : null,
            status: () => this.hasFeedbackTarget ? this.feedbackTarget : null,
            announcer: () => this.hasAnnouncerTarget ? this.announcerTarget : null,
            onChange: () => this.syncRootState(),
        });
        this.syncImagePreview();
        document.addEventListener("turbo:before-cache", this.prepareForCache);
        document.addEventListener("turbo:morph", this.refreshRootState);
        document.addEventListener("turbo:morph-element", this.refreshRootState);
        this.updateClearAction();
        this.syncRootState();
        this.dispatch("ready");
    }

    disconnect() {
        this.disconnected = true;
        this.pendingStreamRenders = 0;
        this.streamRenderGeneration = {};
        this.deleteGeneration = {};
        document.removeEventListener("turbo:before-cache", this.prepareForCache);
        document.removeEventListener("turbo:morph", this.refreshRootState);
        document.removeEventListener("turbo:morph-element", this.refreshRootState);
        this.normalizeDisconnectedItems();
        this.element.dataset.dragging = "false";
        this.feedback.reset(this.feedbackSnapshot());
        this.activeUploads = 0;
        this.syncRootState();
        this.feedback.suspend();
    }

    prepareForCache() {
        this.pendingStreamRenders = 0;
        this.streamRenderGeneration = {};
        this.deleteGeneration = {};
        this.normalizeDisconnectedItems();
        this.element.dataset.dragging = "false";
        this.feedback.reset(this.feedbackSnapshot());
        this.activeUploads = 0;
        this.syncRootState();
    }

    openPicker(event) {
        if (isComposing(event)) return;

        event?.preventDefault?.();
        this.inputTarget?.click?.();
    }

    select(event) {
        this.addFiles(Array.from(event?.target?.files ?? []));
        if (event?.target && !this.preservesInputSelection()) event.target.value = "";
    }

    dragEnter(event) {
        event?.preventDefault?.();
        this.element.dataset.dragging = "true";
    }

    dragOver(event) {
        event?.preventDefault?.();
        this.element.dataset.dragging = "true";
    }

    dragLeave(event) {
        event?.preventDefault?.();
        if (event?.relatedTarget && this.element.contains(event.relatedTarget)) return;
        this.element.dataset.dragging = "false";
    }

    drop(event) {
        event?.preventDefault?.();
        this.element.dataset.dragging = "false";
        this.addFiles(Array.from(event?.dataTransfer?.files ?? []));
    }

    addFiles(files) {
        const selected = this.multipleValue ? files : files.slice(0, 1);
        if (selected.length > 0) this.feedback.beginSelection(this.feedbackSnapshot());
        if (!this.multipleValue && selected.length > 0) this.removePendingItems();

        for (const file of selected) {
            if (this.multipleValue && this.isDuplicateFile(file)) continue;

            const validationError = this.validateFile(file);
            if (validationError) {
                this.addRejectedFile(file, validationError);
                continue;
            }

            const item = this.createItem(file);
            this.items.push(item);
            this.syncRootState();
            this.dispatch("added", { detail: { file } });
        }

        this.updateClearAction();
        this.processQueue();
    }

    remove(event) {
        event?.preventDefault?.();
        const rawId = event?.params?.id ?? event?.currentTarget?.closest?.("[data-file-upload-id]")?.dataset.fileUploadId;
        if (rawId == null) return;

        const id = String(rawId);

        const item = this.items.find((candidate) => candidate.id === id);
        if (!item) return;

        this.removeItem(item, { dispatch: true });
        this.processQueue();
    }

    clear(event) {
        event?.preventDefault?.();
        const items = [...this.items].filter((item) => !item.removed);
        const preserved = this.preservedHiddens();
        if (items.length === 0 && preserved.length === 0) return;

        const remoteValues = [...new Set([
            ...items.map((item) => item.value),
            ...preserved.map((element) => element.value),
        ].filter((value) => value != null && value !== ""))];

        for (const item of items) {
            this.removeItem(item, { dispatch: false, announce: false, deleteRemote: false });
        }

        preserved.forEach((element) => element.remove());
        this.updateClearAction();
        this.element.dataset.dragging = "false";
        this.feedback.reset(this.feedbackSnapshot());

        if (remoteValues.length > 0 && this.deleteUrlValue !== "") {
            void this.deleteRemoteValues(remoteValues, this.deleteGeneration);
        }

        const files = items.map((item) => item.file);
        const count = files.length + preserved.length;
        this.feedback.announce(`${this.message("cleared")} · ${count}`);
        this.dispatch("cleared", { detail: { files, count } });
        this.processQueue();
    }

    retry(event) {
        event?.preventDefault?.();
        const rawId = event?.params?.id ?? event?.currentTarget?.closest?.("[data-file-upload-id]")?.dataset.fileUploadId;
        if (rawId == null) return;

        const item = this.items.find((candidate) => candidate.id === String(rawId));
        if (!item || item.removed || !item.retryable) return;

        if (this.hasReachedMaxFiles(item)) {
            const text = this.message("maxFilesExceeded");
            this.setDescription(item, text);
            this.feedback.announce(`${this.message("uploadFailed")}: ${text}`);
            this.dispatch("error", { detail: { file: item.file, message: text, xhr: null, text } });
            return;
        }

        item.state = "queued";
        item.progress = 0;
        item.xhr = null;
        item.retryable = false;
        item.message = null;
        this.configureItemPresentation(item);
        this.feedback.clearError();
        this.setState(item, "queued");
        this.setDescription(item, this.fileDescription(item.file));
        this.updateProgress(item, 0);
        this.showProgress(item, false);
        this.setRetryAction(item, false);
        this.syncRootState();
        this.dispatch("retry", { detail: { file: item.file } });
        this.processQueue();
    }

    validateFile(file) {
        if (this.hasReachedMaxFiles()) {
            return this.message("maxFilesExceeded");
        }

        if (this.maxSizeBytesValue > 0 && file.size > this.maxSizeBytesValue) {
            return this.message("fileTooBig");
        }

        if (this.viewValue === "image" && !this.shouldPreviewImage(file)) {
            return this.message("invalidFileType");
        }

        if (!this.acceptsFile(file)) {
            return this.message("invalidFileType");
        }

        return null;
    }

    addRejectedFile(file, text) {
        const item = this.createItem(file, { imagePreview: false });
        item.state = "error";
        item.message = text;
        this.items.push(item);
        this.setState(item, "error");
        this.setDescription(item, text);
        this.feedback.announce(`${this.message("uploadFailed")}: ${text}`);
        this.feedback.present({ text, state: "error" }, this.feedbackSnapshot());
        this.dispatch("error", { detail: { file, message: text, xhr: null, text } });
    }

    createItem(file, { imagePreview = true } = {}) {
        const id = String(this.nextId++);
        const element = this.previewEnabled && this.viewValue !== "image" ? this.renderItem(id, file) : null;

        const item = {
            id,
            file,
            element,
            hidden: null,
            imageLoader: null,
            imageSrc: null,
            message: null,
            progress: 0,
            previewUrl: null,
            removed: false,
            retryable: false,
            state: "queued",
            value: null,
            xhr: null,
            mediaFallback: null,
        };

        this.configureItemPresentation(item, { imagePreview });

        return item;
    }

    renderItem(id, file) {
        if (!this.hasTemplateTarget || !this.hasListTarget) return null;

        const fragment = this.templateTarget.content.cloneNode(true);
        const element = fragment.querySelector("[data-file-upload-attachment]") ?? fragment.firstElementChild;
        if (!element) return null;

        element.dataset.fileUploadId = id;
        element.querySelector("[data-file-upload-name]")?.replaceChildren(document.createTextNode(file.name));
        this.setDescription({ element }, this.fileDescription(file));
        const remove = element.querySelector("[data-file-upload-remove]");
        remove?.setAttribute("data-file-upload-id-param", id);
        remove?.setAttribute("aria-label", `${this.message("removeFile")} ${file.name}`);

        const retry = element.querySelector("[data-file-upload-retry]");
        retry?.setAttribute("data-file-upload-id-param", id);
        retry?.setAttribute("aria-label", `${this.message("retry")} ${file.name}`);

        this.listTarget.appendChild(fragment);

        return element;
    }

    processQueue() {
        if (this.pendingStreamRenders > 0) {
            this.syncRootState();

            return;
        }

        while (this.activeUploads < this.parallelUploadsValue) {
            const item = this.items.find((candidate) => candidate.state === "queued" && !candidate.removed);
            if (!item) break;

            this.upload(item);
        }

        this.syncRootState();
    }

    upload(item) {
        const xhr = new XMLHttpRequest();
        item.xhr = xhr;
        item.state = "uploading";
        this.activeUploads++;
        this.setState(item, "uploading");
        this.setRetryAction(item, false);
        this.showProgress(item, true);
        const feedbackText = `${this.message("uploading")} ${item.file.name}`;
        if (this.feedback.present({ text: feedbackText, state: "uploading" }, this.feedbackSnapshot())) {
            this.feedback.announce(feedbackText);
        }

        xhr.open("POST", this.urlValue);
        for (const [name, value] of Object.entries(this.requestHeaders())) {
            xhr.setRequestHeader(name, value);
        }

        xhr.upload?.addEventListener?.("progress", (event) => this.handleProgress(item, event));
        xhr.addEventListener("load", () => this.handleLoad(item, xhr));
        xhr.addEventListener("error", () => this.handleError(item, this.message("uploadFailed"), xhr));

        const body = new FormData();
        body.append(this.paramNameValue, item.file);
        xhr.send(body);
    }

    handleProgress(item, event) {
        if (this.isStale(item)) return;
        if (!event.lengthComputable) return;

        const percent = Math.round((event.loaded / event.total) * 100);
        item.progress = percent;
        this.updateProgress(item, percent);
        this.dispatch("progress", { detail: { file: item.file, percent, bytes: event.loaded } });
    }

    handleLoad(item, xhr) {
        if (this.isStale(item, xhr)) return;

        if (xhr.status >= 200 && xhr.status < 300) {
            const response = this.parseResponse(xhr);
            if (this.isUsableSuccessResponse(response, xhr)) {
                this.activeUploads = Math.max(0, this.activeUploads - 1);
                const stream = this.handleSuccess(item, response);
                this.processQueueAfterStream(this.renderStream(stream));
            } else {
                this.handleError(item, this.message("uploadFailed"), xhr);
            }
        } else {
            this.handleError(item, this.parseResponse(xhr), xhr);
        }
    }

    handleSuccess(item, response) {
        if (this.isStale(item)) return null;

        const stream = this.extractResponseStream(response);
        if (typeof response === "string" && stream !== null) {
            this.commitSingleReplacement(item);
            this.finishSuccess(item, response, null);

            return stream;
        }

        const value = this.extractValue(response);
        item.value = value;
        this.promoteImagePreview(item, response);
        this.commitSingleReplacement(item);

        if (this.hiddenOutputEnabled) {
            if (!this.multipleValue) this.removePreservedHiddens();
            this.appendHidden(item, value);
        }

        this.finishSuccess(item, response, value);

        return stream;
    }

    finishSuccess(item, response, value) {
        if (this.isStale(item)) return;

        item.state = "done";
        item.message = null;
        this.setState(item, "done");
        item.retryable = false;
        this.setRetryAction(item, false);
        this.updateProgress(item, 100);
        this.showProgress(item, false);
        this.setDescription(item, `${this.message("uploaded")} · ${this.fileDescription(item.file)}`);
        const pending = this.pendingUpload();
        const feedbackText = pending
            ? `${this.message("uploading")} ${pending.file.name}`
            : `${this.message("uploaded")} ${item.file.name}`;
        this.feedback.present(
            { text: feedbackText, state: pending ? "uploading" : "done" },
            this.feedbackSnapshot(),
        );
        this.feedback.announce(`${this.message("uploaded")} ${item.file.name}`);
        this.syncRootState();
        this.dispatch("success", { detail: { file: item.file, response, value } });
    }

    handleError(item, message, xhr) {
        if (this.isStale(item, xhr)) return;

        this.activeUploads = item.state === "uploading" ? Math.max(0, this.activeUploads - 1) : this.activeUploads;
        const stream = this.extractResponseStream(message);

        const text = typeof message === "string" && stream !== null
            ? this.message("uploadFailed")
            : this.extractErrorMessage(message, xhr);
        item.retryable = this.isRetryableError(xhr);
        item.state = "error";
        item.message = text;
        this.restoreItemPreview(item);
        this.syncImagePreview();
        this.setState(item, "error");
        this.showProgress(item, false);
        this.setRetryAction(item, item.retryable);
        this.setDescription(item, text);
        this.feedback.announce(`${this.message("uploadFailed")}: ${text}`);
        this.feedback.present({ text, state: "error" }, this.feedbackSnapshot());
        this.syncRootState();
        this.dispatch("error", { detail: { file: item.file, message, xhr, text } });
        this.processQueueAfterStream(this.renderStream(stream));
    }

    removeItem(item, { dispatch = false, announce = true, deleteRemote = true } = {}) {
        item.removed = true;
        if (item.state === "uploading") {
            this.activeUploads = Math.max(0, this.activeUploads - 1);
            item.xhr?.abort();
        }

        this.cancelImageLoader(item);
        this.revokePreviewUrl(item);
        this.removeHidden(item);
        if (deleteRemote && item.value && this.deleteUrlValue !== "") {
            const generation = this.deleteGeneration;
            this.deleteRemote(item.value).catch((error) => {
                if (generation === this.deleteGeneration && !this.disconnected) {
                    this.handleDeleteError(item, error);
                }
            });
        }

        item.element?.remove();
        this.items = this.items.filter((candidate) => candidate !== item);
        this.syncImagePreview();
        this.feedback.reconcile(this.feedbackSnapshot(), { clearError: item.state === "error" });
        this.updateClearAction();
        this.syncRootState();
        if (announce) this.feedback.announce(`${this.message("removed")} ${item.file.name}`);

        if (dispatch) this.dispatch("removed", { detail: { file: item.file } });
    }

    removePendingItems() {
        for (const item of [...this.items]) {
            if (item.state !== "done") {
                this.removeItem(item, { dispatch: false, announce: false, deleteRemote: false });
            }
        }
    }

    commitSingleReplacement(item) {
        if (this.multipleValue) return;

        for (const previous of [...this.items]) {
            if (previous !== item) {
                this.removeItem(previous, { dispatch: false, announce: false });
            }
        }
    }

    parseResponse(xhr) {
        const text = xhr.responseText ?? "";
        const contentType = xhr.getResponseHeader?.("content-type") ?? "";

        if (contentType.includes("json") || /^[\[{]/.test(text.trim())) {
            try {
                return JSON.parse(text);
            } catch (error) {}

            return null;
        }

        return text;
    }

    extractResponseStream(response) {
        if (typeof response === "object" && response !== null) {
            return typeof response.stream === "string" ? response.stream : null;
        }

        if (this.turboStreamMode && this.hasTurboStreamElement(response)) return response;

        return null;
    }

    renderStream(body) {
        const stream = this.extractTurboStreamElements(body);
        if (stream === null) return null;

        const renderer = globalThis.Turbo?.renderStreamMessage;
        if (typeof renderer !== "function") return null;

        const existing = new Set(document.documentElement.querySelectorAll("turbo-stream"));
        const rendering = renderer(stream);
        const inserted = [...document.documentElement.querySelectorAll("turbo-stream")]
            .filter((element) => !existing.has(element));
        const pending = inserted
            .filter((element) => typeof element.render === "function")
            .map((element) => element.render());
        if (rendering && typeof rendering.then === "function") pending.push(rendering);

        return Promise.allSettled(pending);
    }

    processQueueAfterStream(rendering) {
        if (rendering === null) {
            if (this.element.isConnected) this.processQueue();

            return;
        }

        const generation = this.streamRenderGeneration;
        this.pendingStreamRenders++;
        void rendering.then(() => {
            if (generation !== this.streamRenderGeneration) return;

            this.pendingStreamRenders = Math.max(0, this.pendingStreamRenders - 1);
            if (!this.disconnected && this.element.isConnected) this.processQueue();
        });
    }

    hasTurboStreamElement(body) {
        return this.extractTurboStreamElements(body) !== null;
    }

    extractTurboStreamElements(body) {
        if (typeof body !== "string") return null;

        const template = document.createElement("template");
        template.innerHTML = body.trim();
        const streams = [...template.content.querySelectorAll("turbo-stream")];

        return streams.length > 0 ? streams.map((stream) => stream.outerHTML).join("") : null;
    }

    configureItemPresentation(item, { imagePreview = true } = {}) {
        if (this.viewValue === "image") {
            if (imagePreview && this.previewEnabled) this.configureImagePreview(item);

            return;
        }
        if (!item.element) return;

        if (this.viewValue === "grid") {
            item.element.dataset.orientation = "vertical";
        }

        const media = item.element.querySelector('[data-slot="attachment-media"]');
        if (!media || !this.shouldPreviewImage(item.file)) return;
        if (typeof globalThis.URL?.createObjectURL !== "function") return;
        if (typeof globalThis.Blob === "function" && !(item.file instanceof globalThis.Blob)) return;

        item.mediaFallback = [...media.childNodes].map((node) => node.cloneNode(true));
        const url = globalThis.URL.createObjectURL(item.file);
        const image = document.createElement("img");
        image.src = url;
        image.alt = item.file.name;

        item.previewUrl = url;
        media.dataset.variant = "image";
        media.replaceChildren(image);
    }

    shouldPreviewImage(file) {
        if (typeof file.type === "string" && file.type.toLowerCase().startsWith("image/")) return true;
        if (typeof file.type === "string" && file.type !== "") return false;

        const extension = `.${file.name.split(".").pop()?.toLowerCase() ?? ""}`;

        return [".apng", ".avif", ".bmp", ".gif", ".ico", ".jpeg", ".jpg", ".png", ".svg", ".webp"]
            .includes(extension);
    }

    configureImagePreview(item) {
        if (!this.hasImagePreviewTarget || !this.shouldPreviewImage(item.file)) return;
        if (typeof globalThis.URL?.createObjectURL !== "function") return;

        const url = globalThis.URL.createObjectURL(item.file);
        item.previewUrl = url;
        item.imageSrc = url;
        this.showImagePreview(item);
    }

    promoteImagePreview(item, response) {
        if (this.viewValue !== "image" || !this.previewEnabled || typeof response !== "object" || response === null) return;

        const src = response[this.previewUrlKeyValue];
        if (typeof src !== "string" || src === "") return;

        this.cancelImageLoader(item);
        const loader = document.createElement("img");
        item.imageLoader = loader;
        loader.addEventListener("load", () => {
            if (this.isStale(item) || item.imageLoader !== loader) return;

            item.imageLoader = null;
            item.imageSrc = src;
            this.revokePreviewUrl(item);
            this.syncImagePreview();
        }, { once: true });
        loader.addEventListener("error", () => {
            if (item.imageLoader === loader) item.imageLoader = null;
        }, { once: true });
        loader.src = src;
    }

    cancelImageLoader(item) {
        if (!item.imageLoader) return;

        item.imageLoader.src = "";
        item.imageLoader = null;
    }

    syncImagePreview() {
        if (this.viewValue !== "image" || !this.hasImagePreviewTarget || !this.items) return;

        const item = [...this.items].reverse().find((candidate) => !candidate.removed && candidate.imageSrc);
        if (item) {
            this.showImagePreview(item);
        } else {
            this.hideImagePreview();
        }
    }

    showImagePreview(item) {
        if (!this.hasImagePreviewTarget || !item.imageSrc) return;

        this.imagePreviewTarget.setAttribute("src", item.imageSrc);
        this.imagePreviewTarget.dataset.fileUploadId = item.id;
        this.imagePreviewTarget.hidden = false;
    }

    hideImagePreview() {
        if (!this.hasImagePreviewTarget) return;

        this.imagePreviewTarget.removeAttribute("src");
        delete this.imagePreviewTarget.dataset.fileUploadId;
        this.imagePreviewTarget.hidden = true;
    }

    revokePreviewUrl(item) {
        if (!item.previewUrl) return;

        const url = item.previewUrl;
        globalThis.URL?.revokeObjectURL?.(url);
        item.previewUrl = null;
        if (item.imageSrc === url) item.imageSrc = null;
    }

    restoreItemPreview(item) {
        if (this.viewValue === "image") {
            this.cancelImageLoader(item);
            this.revokePreviewUrl(item);

            return;
        }

        const media = item.element?.querySelector('[data-slot="attachment-media"]');
        const image = media?.querySelector("img");
        const imageSource = image?.getAttribute("src") ?? "";
        const blobSource = imageSource.startsWith("blob:");
        if (!media || (!item.previewUrl && !blobSource)) return;

        if (item.previewUrl) {
            this.revokePreviewUrl(item);
        } else {
            globalThis.URL?.revokeObjectURL?.(imageSource);
        }
        const fallback = item.mediaFallback ?? this.templateMediaFallback();
        media.dataset.variant = "icon";
        media.replaceChildren(...fallback.map((node) => node.cloneNode(true)));
    }

    templateMediaFallback() {
        if (!this.hasTemplateTarget) return [];

        const media = this.templateTarget.content.querySelector('[data-slot="attachment-media"]');

        return media ? [...media.childNodes].map((node) => node.cloneNode(true)) : [];
    }

    extractValue(response) {
        if (response == null) return null;
        if (typeof response === "string") return response;
        return response[this.responseKeyValue] ?? null;
    }

    isUsableSuccessResponse(response, xhr = null) {
        if (this.isHtmlDocument(response)) return false;
        if (this.turboStreamMode) {
            const contentType = xhr?.getResponseHeader?.("content-type")?.toLowerCase() ?? "";

            return !contentType.includes("json")
                && typeof response === "string"
                && this.hasTurboStreamElement(response);
        }
        if (typeof response === "string" && this.hasTurboStreamElement(response)) return false;
        if (!this.hiddenOutputEnabled || this.hiddenNameValue === "") return true;

        const value = this.extractValue(response);

        return value != null && value !== "";
    }

    extractErrorMessage(raw, xhr = null) {
        if (xhr?.status === 413) return this.message("fileTooBig");

        if (typeof raw === "string") {
            if (this.isHtmlResponse(raw, xhr)) {
                const status = Number(xhr?.status ?? 0);

                return status >= 200 && status < 300
                    ? this.message("serverRejected")
                    : this.message("uploadFailed");
            }

            return raw || this.message("uploadFailed");
        }
        if (raw == null) return this.message("uploadFailed");

        if (typeof raw === "object") {
            if (raw.errors && typeof raw.errors === "object") {
                const firstField = Object.values(raw.errors)[0];
                if (Array.isArray(firstField) && typeof firstField[0] === "string") return firstField[0];
            }

            if (typeof raw.message === "string") return raw.message;
            return this.message("uploadFailed");
        }

        return String(raw);
    }

    isHtmlResponse(body, xhr = null) {
        const contentType = xhr?.getResponseHeader?.("content-type")?.toLowerCase() ?? "";

        return contentType.includes("html") || this.isHtmlDocument(body);
    }

    isHtmlDocument(body) {
        return typeof body === "string"
            && /^\s*(?:<!doctype\s+html|<html[\s>]|<head[\s>]|<body[\s>])/i.test(body);
    }

    isRetryableError(xhr) {
        const status = Number(xhr?.status ?? 0);

        return status === 0 || status >= 500;
    }

    appendHidden(item, value) {
        if (value == null || this.hiddenNameValue === "") return;

        const input = document.createElement("input");
        input.type = "hidden";
        input.name = this.hiddenNameValue;
        input.value = value;
        input.dataset.hwUpload = "";
        input.dataset.fileUploadId = item.id;
        item.hidden = input;
        this.element.appendChild(input);
    }

    removeHidden(item) {
        item.hidden?.remove();
        item.hidden = null;
    }

    removePreservedHiddens() {
        this.preservedHiddens().forEach((element) => element.remove());
    }

    preservedHiddens() {
        return [...this.element.querySelectorAll("[data-hw-upload-preserved]")];
    }

    async deleteRemote(token) {
        const url = this.deleteUrlValue.split(":token").join(encodeURIComponent(token));
        const response = await fetch(url, { method: "DELETE", headers: this.csrfHeaders() });
        if (!response.ok) {
            const error = new Error(this.message("deleteFailed"));
            error.response = response;
            throw error;
        }

        return response;
    }

    async deleteRemoteValues(values, generation = this.deleteGeneration) {
        const queue = [...values];
        const limit = Math.max(1, this.parallelUploadsValue);
        const workers = Array.from({ length: Math.min(limit, queue.length) }, async () => {
            while (queue.length > 0) {
                const value = queue.shift();

                try {
                    await this.deleteRemote(value);
                } catch (error) {
                    if (generation === this.deleteGeneration && !this.disconnected) {
                        this.handleDeleteError({ file: null, value }, error);
                    }
                }
            }
        });

        await Promise.all(workers);
    }

    requestHeaders() {
        const headers = {
            ...this.csrfHeaders(),
            Accept: this.turboStreamMode ? "application/json, text/vnd.turbo-stream.html" : "application/json",
            "X-Requested-With": "XMLHttpRequest",
        };
        return headers;
    }

    csrfHeaders() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
        return token ? { "X-CSRF-TOKEN": token } : {};
    }

    acceptsFile(file) {
        const accept = this.acceptValue.trim();
        if (accept === "") return true;

        const extension = `.${file.name.split(".").pop()?.toLowerCase() ?? ""}`;
        const type = file.type.toLowerCase();

        return accept.split(",").map((rule) => rule.trim().toLowerCase()).filter(Boolean).some((rule) => {
            if (rule.startsWith(".")) return extension === rule;
            if (rule === "image/*") return this.shouldPreviewImage(file);
            if (rule.endsWith("/*")) return type.startsWith(rule.slice(0, -1));
            return type === rule;
        });
    }

    acceptedItems() {
        return this.items.filter((item) => !item.removed && item.state !== "error");
    }

    hasReachedMaxFiles(excluded = null) {
        if (this.maxFilesValue <= 0) return false;

        const accepted = this.acceptedItems().filter((item) => item !== excluded);
        const counted = this.multipleValue ? accepted : accepted.filter((item) => item.state !== "done");

        return counted.length >= this.maxFilesValue;
    }

    pendingUpload() {
        return this.items.find((item) => !item.removed && ["queued", "uploading"].includes(item.state)) ?? null;
    }

    hasPendingUploads() {
        return this.pendingUpload() !== null;
    }

    syncRootState() {
        this.element.dataset.loading = String(this.hasPendingUploads());
        this.element.dataset.uploadState = this.uploadState();
    }

    refreshRootState(event) {
        if (event.type === "turbo:morph-element" && event.target !== this.element) return;

        this.restoreMorphState();
        this.syncRootState();
    }

    restoreMorphState() {
        const hasCompletedSingle = this.hiddenOutputEnabled && !this.multipleValue
            && this.items.some((item) => item.state === "done" && item.value != null && item.value !== "");
        if (hasCompletedSingle) this.removePreservedHiddens();

        for (const item of this.items) {
            if (!this.hiddenOutputEnabled || item.value == null || item.value === "") continue;

            const hidden = [...this.element.querySelectorAll('input[type="hidden"][data-hw-upload]')]
                .find((input) => input.dataset.fileUploadId === item.id);
            if (hidden) {
                item.hidden = hidden;
            } else if (!item.hidden?.isConnected) {
                item.hidden = null;
                this.appendHidden(item, item.value);
            }
        }

        this.restoreAttachmentCards();
        this.syncImagePreview();
        this.feedback.reconcile(this.feedbackSnapshot());
        this.updateClearAction();
    }

    restoreAttachmentCards() {
        if (this.viewValue === "image" || !this.previewEnabled || !this.hasListTarget || !this.hasTemplateTarget) return;

        for (const item of this.items) {
            const current = [...this.listTarget.querySelectorAll("[data-file-upload-attachment][data-file-upload-id]")]
                .find((element) => element.dataset.fileUploadId === item.id);
            if (current) {
                item.element = current;

                continue;
            }

            if (item.element && !item.element.isConnected) this.restoreItemPreview(item);
            item.element = this.renderItem(item.id, item.file);
            this.configureItemPresentation(item);
            this.restoreAttachmentState(item);
        }
    }

    restoreAttachmentState(item) {
        this.setState(item, item.state);
        const description = item.state === "done"
            ? `${this.message("uploaded")} · ${this.fileDescription(item.file)}`
            : item.message ?? this.fileDescription(item.file);
        this.setDescription(item, description);
        this.updateProgress(item, item.progress);
        this.showProgress(item, item.state === "uploading");
        this.setRetryAction(item, item.retryable);
    }

    feedbackSnapshot() {
        const error = this.items.find((item) => !item.removed && item.state === "error");
        const pending = this.pendingUpload();
        const completed = [...this.items].reverse().find((item) => !item.removed && item.state === "done");

        return {
            busy: pending !== null,
            completedText: completed ? `${this.message("uploaded")} ${completed.file.name}` : null,
            itemErrorText: error?.message ?? null,
            pendingText: pending ? `${this.message("uploading")} ${pending.file.name}` : null,
            preview: this.previewEnabled,
            serverInvalid: this.element.hasAttribute("data-invalid"),
        };
    }

    uploadState() {
        if (this.feedback?.error || this.element.hasAttribute("data-invalid")
            || this.items.some((item) => !item.removed && item.state === "error")) {
            return "error";
        }
        if (this.hasPendingUploads()) return "uploading";
        if (this.items.some((item) => !item.removed && item.state === "done")) return "done";

        return "idle";
    }

    isDuplicateFile(file) {
        return this.acceptedItems().some((item) => this.fileSignature(item.file) === this.fileSignature(file));
    }

    fileSignature(file) {
        return [file.name, file.size, file.type, file.lastModified ?? ""].join("\u0000");
    }

    hydrateItems() {
        const hiddens = [...this.element.querySelectorAll('input[type="hidden"][data-hw-upload]')];
        const items = [];
        const elements = this.hasListTarget
            ? this.listTarget.querySelectorAll("[data-file-upload-attachment][data-file-upload-id]")
            : [];
        for (const element of elements) {
            const state = element.dataset.state ?? "done";
            if (state !== "done") {
                element.remove();
                continue;
            }

            const id = element.dataset.fileUploadId;
            const hidden = hiddens.find((input) => input.dataset.fileUploadId === id) ?? null;
            const item = {
                id,
                file: this.fileFromElement(element, hidden?.value),
                element,
                hidden,
                imageLoader: null,
                imageSrc: null,
                message: null,
                progress: Number(element.querySelector('[data-slot="progress"]')?.dataset.value ?? 100),
                previewUrl: null,
                removed: false,
                retryable: false,
                state,
                value: hidden?.value ?? null,
                xhr: null,
                mediaFallback: null,
            };
            this.restoreItemPreview(item);
            items.push(item);
        }

        const hydratedIds = new Set(items.map((item) => item.id));
        for (const hidden of hiddens) {
            const id = hidden.dataset.fileUploadId;
            if (!id || hydratedIds.has(id)) continue;

            items.push({
                id,
                file: this.fileFromElement(null, hidden.value),
                element: null,
                hidden,
                imageLoader: null,
                imageSrc: this.imageSourceForId(id),
                message: null,
                progress: 100,
                previewUrl: null,
                removed: false,
                retryable: false,
                state: "done",
                value: hidden.value,
                xhr: null,
                mediaFallback: null,
            });
        }

        return items;
    }

    imageSourceForId(id) {
        if (this.viewValue !== "image" || !this.hasImagePreviewTarget || this.imagePreviewTarget.hidden) return null;
        if (this.imagePreviewTarget.dataset.fileUploadId !== id) return null;

        return this.imagePreviewTarget.getAttribute("src");
    }

    normalizeDisconnectedItems() {
        for (const item of [...this.items]) {
            if (item.state === "done") {
                this.restoreItemPreview(item);
            } else {
                this.removeItem(item, { dispatch: false, announce: false, deleteRemote: false });
            }
        }

        this.syncImagePreview();
        this.updateClearAction();
    }

    nextAvailableId() {
        const ids = this.items.map((item) => Number(item.id));

        return Math.max(0, ...ids.filter(Number.isFinite)) + 1;
    }

    fileFromElement(element, value) {
        const name = element?.querySelector("[data-file-upload-name]")?.textContent?.trim() || value || "file";
        return { name, size: 0, type: "" };
    }

    isStale(item, xhr = null) {
        return this.disconnected || item.removed || (xhr !== null && item.xhr !== xhr) || !this.items.includes(item);
    }

    preservesInputSelection() {
        const controllers = (this.element.dataset.controller ?? "").split(/\s+/);
        return controllers.includes("file-preserve") || controllers.includes("reset-files");
    }

    setState(item, state) {
        if (!item.element) return;

        const visualState = state === "queued" ? "idle" : state;
        item.element.setAttribute("data-state", visualState);

        const description = item.element.querySelector("[data-file-upload-description]");
        if (visualState === "error") {
            item.element.setAttribute("aria-invalid", "true");
            description?.setAttribute("role", "alert");
            return;
        }

        item.element.removeAttribute("aria-invalid");
        description?.removeAttribute("role");
    }

    setDescription(item, text) {
        item.element
            ?.querySelector("[data-file-upload-description]")
            ?.replaceChildren(document.createTextNode(text));
    }

    setRetryAction(item, visible) {
        const retry = item.element?.querySelector("[data-file-upload-retry]");
        if (retry) retry.hidden = !visible;
    }

    updateClearAction() {
        const clear = this.element.querySelector("[data-file-upload-clear]");
        if (!clear) return;

        clear.hidden = this.items.filter((item) => !item.removed).length + this.preservedHiddens().length === 0;
    }

    showProgress(item, visible) {
        const progress = item.element?.querySelector("[data-file-upload-progress]");
        if (progress) progress.hidden = !visible;
    }

    updateProgress(item, percent) {
        const progress = item.element?.querySelector('[data-slot="progress"]');
        if (!progress) return;

        progress.dataset.value = String(percent);
        progress.setAttribute("aria-valuenow", String(percent));
        progress.style.setProperty("--progress-value", `${percent}%`);
    }

    fileDescription(file) {
        return `${this.fileType(file)} · ${this.formatBytes(file.size)}`;
    }

    fileType(file) {
        const extension = file.name.includes(".") ? file.name.split(".").pop() : "file";
        return extension.toUpperCase();
    }

    formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return "0 B";

        const units = ["B", "KB", "MB", "GB"];
        let value = bytes;
        let unit = 0;

        while (value >= 1024 && unit < units.length - 1) {
            value /= 1024;
            unit++;
        }

        const formatted = value >= 10 || unit === 0 ? Math.round(value) : Math.round(value * 10) / 10;
        return `${formatted} ${units[unit]}`;
    }

    message(key) {
        return this.messagesValue[key] ?? this.defaultMessages[key] ?? key;
    }

    get previewEnabled() {
        return !this.turboStreamMode && ["full", "preview"].includes(this.outputModeValue);
    }

    get hiddenOutputEnabled() {
        return !this.turboStreamMode && ["full", "hidden"].includes(this.outputModeValue);
    }

    get turboStreamMode() {
        return this.modeValue === "turbo-stream";
    }

    handleDeleteError(item, error) {
        const text = this.message("deleteFailed");
        const name = item.file?.name;
        const feedbackText = name ? `${text}: ${name}` : text;
        this.feedback.announce(feedbackText);
        this.feedback.present(
            { text: feedbackText, state: "error" },
            this.feedbackSnapshot(),
            { force: true },
        );
        this.syncRootState();
        this.dispatch("delete-error", {
            detail: {
                error,
                file: item.file,
                response: error.response ?? null,
                text,
                value: item.value,
            },
        });
    }

    get defaultMessages() {
        return {
            idle: "Choose files",
            idleMultiple: "Choose files",
            hint: "Drop files here or click to choose",
            button: "Choose files",
            uploading: "Uploading",
            uploaded: "Uploaded",
            uploadFailed: "Upload failed",
            serverRejected: "The server rejected this file. Check the file type and server upload-size limit.",
            clearAll: "Clear all",
            cleared: "Cleared files",
            removed: "Removed",
            removeFile: "Remove",
            deleteFailed: "Failed to remove file",
            retry: "Retry upload",
            fileTooBig: "File is too large",
            invalidFileType: "File type is not allowed",
            maxFilesExceeded: "Maximum number of files reached",
        };
    }
}
