// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["announcer", "dropzone", "feedback", "input", "list", "template"];

    static values = {
        url: String,
        accept: { type: String, default: "" },
        maxSizeBytes: { type: Number, default: 0 },
        maxFiles: { type: Number, default: 0 },
        multiple: { type: Boolean, default: false },
        preview: { type: Boolean, default: true },
        emitHidden: { type: Boolean, default: true },
        paramName: { type: String, default: "file" },
        responseKey: { type: String, default: "token" },
        hiddenName: { type: String, default: "" },
        deleteUrl: { type: String, default: "" },
        parallelUploads: { type: Number, default: 3 },
        turboStream: { type: Boolean, default: false },
        view: { type: String, default: "list" },
        messages: { type: Object, default: {} },
    };

    initialize() {
        this.prepareForCache = this.prepareForCache.bind(this);
    }

    connect() {
        this.disconnected = false;
        this.feedbackError = null;
        this.feedbackDefault = this.hasFeedbackTarget
            ? this.feedbackTarget.dataset.fileUploadDefaultFeedback ?? this.feedbackTarget.textContent
            : "";
        this.items = this.hydrateItems();
        this.nextId = this.nextAvailableId();
        this.activeUploads = 0;
        document.addEventListener("turbo:before-cache", this.prepareForCache);
        this.updateClearAction();
        this.dispatch("ready");
    }

    disconnect() {
        this.disconnected = true;
        document.removeEventListener("turbo:before-cache", this.prepareForCache);
        this.normalizeDisconnectedItems();
        this.restoreUploadFeedback();
        this.activeUploads = 0;
    }

    prepareForCache() {
        this.normalizeDisconnectedItems();
        this.restoreUploadFeedback();
        this.activeUploads = 0;
    }

    openPicker(event) {
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
        if (selected.length > 0) this.beginSelection();
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
        this.restoreUploadFeedback();

        if (remoteValues.length > 0 && this.deleteUrlValue !== "") {
            this.deleteRemoteValues(remoteValues).catch((error) => console.error("file-upload delete failed:", error));
        }

        const files = items.map((item) => item.file);
        const count = files.length + preserved.length;
        this.announce(`${this.message("cleared")} · ${count}`);
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
            this.announce(`${this.message("uploadFailed")}: ${text}`);
            this.dispatch("error", { detail: { file: item.file, message: text, xhr: null, text } });
            return;
        }

        item.state = "queued";
        item.progress = 0;
        item.xhr = null;
        item.retryable = false;
        this.feedbackError = null;
        this.setState(item, "queued");
        this.setDescription(item, this.fileDescription(item.file));
        this.updateProgress(item, 0);
        this.showProgress(item, false);
        this.setRetryAction(item, false);
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

        if (!this.acceptsFile(file)) {
            return this.message("invalidFileType");
        }

        return null;
    }

    addRejectedFile(file, text) {
        const item = this.createItem(file);
        item.state = "error";
        this.items.push(item);
        this.setState(item, "error");
        this.setDescription(item, text);
        this.announce(`${this.message("uploadFailed")}: ${text}`);
        this.setUploadFeedback(text, "error");
        this.dispatch("error", { detail: { file, message: text, xhr: null, text } });
    }

    createItem(file) {
        const id = String(this.nextId++);
        const element = this.previewValue ? this.renderItem(id, file) : null;

        const item = {
            id,
            file,
            element,
            hidden: null,
            progress: 0,
            previewUrl: null,
            removed: false,
            retryable: false,
            state: "queued",
            value: null,
            xhr: null,
            mediaFallback: null,
        };

        this.configureItemPresentation(item);

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
        while (this.activeUploads < this.parallelUploadsValue) {
            const item = this.items.find((candidate) => candidate.state === "queued" && !candidate.removed);
            if (!item) return;

            this.upload(item);
        }
    }

    upload(item) {
        const xhr = new XMLHttpRequest();
        item.xhr = xhr;
        item.state = "uploading";
        this.activeUploads++;
        this.setState(item, "uploading");
        this.setRetryAction(item, false);
        this.showProgress(item, true);
        const feedback = `${this.message("uploading")} ${item.file.name}`;
        if (this.setUploadFeedback(feedback, "uploading")) this.announce(feedback);

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
            if (this.isUsableSuccessResponse(response)) {
                this.activeUploads = Math.max(0, this.activeUploads - 1);
                this.handleSuccess(item, response);
            } else {
                this.handleError(item, this.message("uploadFailed"), xhr);
            }
        } else {
            this.handleError(item, this.parseResponse(xhr), xhr);
        }

        this.processQueue();
    }

    handleSuccess(item, response) {
        if (this.isStale(item)) return;

        if (this.maybeRenderStream(typeof response === "string" ? response : null)) {
            this.commitSingleReplacement(item);
            this.finishSuccess(item, response, null);
            return;
        }

        const value = this.extractValue(response);
        item.value = value;
        this.commitSingleReplacement(item);

        if (this.emitHiddenValue) {
            if (!this.multipleValue) this.removePreservedHiddens();
            this.appendHidden(item, value);
        }

        this.finishSuccess(item, response, value);
    }

    finishSuccess(item, response, value) {
        if (this.isStale(item)) return;

        item.state = "done";
        this.setState(item, "done");
        item.retryable = false;
        this.setRetryAction(item, false);
        this.updateProgress(item, 100);
        this.showProgress(item, false);
        this.setDescription(item, `${this.message("uploaded")} · ${this.fileDescription(item.file)}`);
        const pending = this.pendingUpload();
        const feedback = pending
            ? `${this.message("uploading")} ${pending.file.name}`
            : `${this.message("uploaded")} ${item.file.name}`;
        this.setUploadFeedback(feedback, pending ? "uploading" : "done");
        this.announce(`${this.message("uploaded")} ${item.file.name}`);
        this.dispatch("success", { detail: { file: item.file, response, value } });
    }

    handleError(item, message, xhr) {
        if (this.isStale(item, xhr)) return;

        this.activeUploads = item.state === "uploading" ? Math.max(0, this.activeUploads - 1) : this.activeUploads;
        this.maybeRenderStream(xhr?.responseText ?? null);

        const text = this.extractErrorMessage(message, xhr);
        item.retryable = this.isRetryableError(xhr);
        item.state = "error";
        this.setState(item, "error");
        this.showProgress(item, false);
        this.setRetryAction(item, item.retryable);
        this.setDescription(item, text);
        this.announce(`${this.message("uploadFailed")}: ${text}`);
        this.setUploadFeedback(text, "error");
        this.dispatch("error", { detail: { file: item.file, message, xhr, text } });
        this.processQueue();
    }

    removeItem(item, { dispatch = false, announce = true, deleteRemote = true } = {}) {
        item.removed = true;
        if (item.state === "uploading") {
            this.activeUploads = Math.max(0, this.activeUploads - 1);
            item.xhr?.abort();
        }

        this.revokePreviewUrl(item);
        this.removeHidden(item);
        if (deleteRemote && item.value && this.deleteUrlValue !== "") {
            this.deleteRemote(item.value).catch((error) => this.handleDeleteError(item, error));
        }

        item.element?.remove();
        this.items = this.items.filter((candidate) => candidate !== item);
        this.syncFeedbackAfterRemoval(item);
        this.updateClearAction();
        if (announce) this.announce(`${this.message("removed")} ${item.file.name}`);

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

    maybeRenderStream(body) {
        if (!this.turboStreamValue) return false;
        if (!this.hasTurboStreamElement(body)) return false;

        const renderer = globalThis.Turbo?.renderStreamMessage;
        if (typeof renderer !== "function") return false;

        renderer(body);
        return true;
    }

    hasTurboStreamElement(body) {
        if (typeof body !== "string") return false;

        const template = document.createElement("template");
        template.innerHTML = body.trim();

        return template.content.querySelector("turbo-stream") !== null;
    }

    configureItemPresentation(item) {
        if (!item.element) return;

        if (this.viewValue === "grid") {
            item.element.dataset.orientation = "vertical";
        }

        const media = item.element.querySelector('[data-slot="attachment-media"]');
        if (!media || !this.shouldPreviewImage(item.file)) return;
        if (typeof globalThis.URL?.createObjectURL !== "function") return;

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
        return typeof file.type === "string" && file.type.toLowerCase().startsWith("image/");
    }

    revokePreviewUrl(item) {
        if (!item.previewUrl) return;

        globalThis.URL?.revokeObjectURL?.(item.previewUrl);
        item.previewUrl = null;
    }

    restoreItemPreview(item) {
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

    isUsableSuccessResponse(response) {
        if (this.isHtmlDocument(response)) return false;
        if (this.turboStreamValue && this.hasTurboStreamElement(response)) return true;
        if (!this.emitHiddenValue || this.hiddenNameValue === "") return true;

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

    async deleteRemoteValues(values) {
        const queue = [...values];
        const limit = Math.max(1, this.parallelUploadsValue);
        const workers = Array.from({ length: Math.min(limit, queue.length) }, async () => {
            while (queue.length > 0) {
                const value = queue.shift();

                try {
                    await this.deleteRemote(value);
                } catch (error) {
                    this.handleDeleteError({ file: null, value }, error);
                }
            }
        });

        await Promise.all(workers);
    }

    requestHeaders() {
        const headers = {
            ...this.csrfHeaders(),
            Accept: this.turboStreamValue ? "application/json, text/vnd.turbo-stream.html" : "application/json",
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

    isDuplicateFile(file) {
        return this.acceptedItems().some((item) => this.fileSignature(item.file) === this.fileSignature(file));
    }

    fileSignature(file) {
        return [file.name, file.size, file.type, file.lastModified ?? ""].join("\u0000");
    }

    hydrateItems() {
        if (!this.hasListTarget) return [];

        const hiddens = [...this.element.querySelectorAll('input[type="hidden"][data-hw-upload]')];
        const items = [];
        for (const element of this.listTarget.querySelectorAll("[data-file-upload-attachment][data-file-upload-id]")) {
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

    normalizeDisconnectedItems() {
        for (const item of [...this.items]) {
            if (item.state === "done") {
                this.restoreItemPreview(item);
            } else {
                this.removeItem(item, { dispatch: false, announce: false, deleteRemote: false });
            }
        }

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

        item.element.setAttribute("data-state", state);

        const description = item.element.querySelector("[data-file-upload-description]");
        if (state === "error") {
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

    announce(message) {
        if (!this.hasAnnouncerTarget) return;
        this.announcerTarget.textContent = message;
    }

    beginSelection() {
        this.feedbackError = null;

        if (this.hasFeedbackTarget) {
            this.feedbackTarget.replaceChildren(document.createTextNode(this.feedbackDefault));
        }
        if (!this.hasDropzoneTarget) return;

        delete this.dropzoneTarget.dataset.state;
        this.dropzoneTarget.removeAttribute("aria-busy");
        if (!this.element.hasAttribute("data-invalid")) this.dropzoneTarget.removeAttribute("aria-invalid");

        const pending = this.pendingUpload();
        if (!this.previewValue && pending) {
            this.setUploadFeedback(`${this.message("uploading")} ${pending.file.name}`, "uploading");
        }
    }

    setUploadFeedback(text, state, { force = false } = {}) {
        if (state === "error") {
            this.feedbackError = text;
        } else if (this.feedbackError && !force) {
            if (!this.previewValue && this.hasDropzoneTarget) {
                this.dropzoneTarget.toggleAttribute("aria-busy", this.hasPendingUploads());
            }

            return false;
        }

        if (this.previewValue && !force) return true;

        if (this.hasFeedbackTarget) this.feedbackTarget.replaceChildren(document.createTextNode(text));
        if (!this.hasDropzoneTarget) return true;

        this.dropzoneTarget.dataset.state = state;
        this.dropzoneTarget.toggleAttribute("aria-busy", state === "uploading" || this.hasPendingUploads());
        if (state === "error") {
            this.dropzoneTarget.setAttribute("aria-invalid", "true");
        } else if (!this.element.hasAttribute("data-invalid")) {
            this.dropzoneTarget.removeAttribute("aria-invalid");
        }

        return true;
    }

    restoreUploadFeedback() {
        this.element.dataset.dragging = "false";
        this.feedbackError = null;

        if (this.hasFeedbackTarget) {
            this.feedbackTarget.replaceChildren(document.createTextNode(this.feedbackDefault));
        }
        if (!this.hasDropzoneTarget) return;

        delete this.dropzoneTarget.dataset.state;
        this.dropzoneTarget.removeAttribute("aria-busy");
        if (!this.element.hasAttribute("data-invalid")) this.dropzoneTarget.removeAttribute("aria-invalid");
    }

    syncFeedbackAfterRemoval(item) {
        if (item.state !== "error" || this.items.some((candidate) => candidate.state === "error")) return;

        this.feedbackError = null;
        const pending = this.pendingUpload();
        if (pending) {
            this.setUploadFeedback(`${this.message("uploading")} ${pending.file.name}`, "uploading");
        } else {
            this.restoreUploadFeedback();
        }
    }

    handleDeleteError(item, error) {
        const text = this.message("deleteFailed");
        const name = item.file?.name;
        this.announce(name ? `${text}: ${name}` : text);
        this.setUploadFeedback(name ? `${text}: ${name}` : text, "error", { force: true });
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
