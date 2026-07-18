// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["announcer", "dropzone", "input", "list", "template"];

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
        messages: { type: Object, default: {} },
    };

    connect() {
        this.disconnected = false;
        this.items = this.hydrateItems();
        this.nextId = this.nextAvailableId();
        this.activeUploads = 0;
        this.dispatch("ready");
    }

    disconnect() {
        this.disconnected = true;
        for (const item of this.items) {
            if (item.state === "uploading") item.xhr?.abort();
        }
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
        if (!this.multipleValue && selected.length > 0) this.removeLocalItems();

        for (const file of selected) {
            if (this.isDuplicateFile(file)) continue;

            const validationError = this.validateFile(file);
            if (validationError) {
                this.addRejectedFile(file, validationError);
                continue;
            }

            const item = this.createItem(file);
            this.items.push(item);
            this.announce(`${this.message("uploading")} ${file.name}`);
            this.dispatch("added", { detail: { file } });
        }

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

    validateFile(file) {
        if (this.maxFilesValue > 0 && this.acceptedItems().length >= this.maxFilesValue) {
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
        this.dispatch("error", { detail: { file, message: text, xhr: null, text } });
    }

    createItem(file) {
        const id = String(this.nextId++);
        const element = this.previewValue ? this.renderItem(id, file) : null;

        return {
            id,
            file,
            element,
            hidden: null,
            progress: 0,
            removed: false,
            state: "queued",
            value: null,
            xhr: null,
        };
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
        this.showProgress(item, true);

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
            this.activeUploads = Math.max(0, this.activeUploads - 1);
            this.handleSuccess(item, this.parseResponse(xhr));
        } else {
            this.handleError(item, this.parseResponse(xhr), xhr);
        }

        this.processQueue();
    }

    handleSuccess(item, response) {
        if (this.isStale(item)) return;

        if (this.maybeRenderStream(typeof response === "string" ? response : null)) {
            this.finishSuccess(item, response, null);
            return;
        }

        const value = this.extractValue(response);
        item.value = value;

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
        this.updateProgress(item, 100);
        this.showProgress(item, false);
        this.setDescription(item, `${this.message("uploaded")} · ${this.fileDescription(item.file)}`);
        this.announce(`${this.message("uploaded")} ${item.file.name}`);
        this.dispatch("success", { detail: { file: item.file, response, value } });
    }

    handleError(item, message, xhr) {
        if (this.isStale(item, xhr)) return;

        this.activeUploads = item.state === "uploading" ? Math.max(0, this.activeUploads - 1) : this.activeUploads;
        this.maybeRenderStream(xhr?.responseText ?? null);

        const text = this.extractErrorMessage(message);
        item.state = "error";
        this.setState(item, "error");
        this.showProgress(item, false);
        this.setDescription(item, text);
        this.announce(`${this.message("uploadFailed")}: ${text}`);
        this.dispatch("error", { detail: { file: item.file, message, xhr, text } });
        this.processQueue();
    }

    removeItem(item, { dispatch = false } = {}) {
        item.removed = true;
        if (item.state === "uploading") {
            this.activeUploads = Math.max(0, this.activeUploads - 1);
            item.xhr?.abort();
        }

        this.removeHidden(item);
        if (item.value && this.deleteUrlValue !== "") {
            this.deleteRemote(item.value).catch((error) => console.error("file-upload delete failed:", error));
        }

        item.element?.remove();
        this.items = this.items.filter((candidate) => candidate !== item);
        this.announce(`${this.message("removed")} ${item.file.name}`);

        if (dispatch) this.dispatch("removed", { detail: { file: item.file } });
    }

    removeLocalItems() {
        for (const item of [...this.items]) {
            this.removeItem(item, { dispatch: false });
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

    extractValue(response) {
        if (response == null) return null;
        if (typeof response === "string") return response;
        return response[this.responseKeyValue] ?? null;
    }

    extractErrorMessage(raw) {
        if (typeof raw === "string") return raw || this.message("uploadFailed");
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
        this.element.querySelectorAll("[data-hw-upload-preserved]").forEach((element) => element.remove());
    }

    async deleteRemote(token) {
        const url = this.deleteUrlValue.split(":token").join(encodeURIComponent(token));
        await fetch(url, { method: "DELETE", headers: this.csrfHeaders() });
    }

    requestHeaders() {
        const headers = this.csrfHeaders();
        if (this.turboStreamValue) headers.Accept = "text/vnd.turbo-stream.html, application/json";
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

    isDuplicateFile(file) {
        return this.acceptedItems().some((item) => this.fileSignature(item.file) === this.fileSignature(file));
    }

    fileSignature(file) {
        return [file.name, file.size, file.type, file.lastModified ?? ""].join("\u0000");
    }

    hydrateItems() {
        if (!this.hasListTarget) return [];

        return [...this.listTarget.querySelectorAll("[data-file-upload-attachment][data-file-upload-id]")].map((element) => {
            const id = element.dataset.fileUploadId;
            const hidden = [...this.element.querySelectorAll('input[type="hidden"][data-hw-upload]')]
                .find((input) => input.dataset.fileUploadId === id) ?? null;

            return {
                id,
                file: this.fileFromElement(element, hidden?.value),
                element,
                hidden,
                progress: Number(element.querySelector('[data-slot="progress"]')?.dataset.value ?? 100),
                removed: false,
                state: element.dataset.state ?? "done",
                value: hidden?.value ?? null,
                xhr: null,
            };
        });
    }

    nextAvailableId() {
        const ids = this.hasListTarget
            ? [...this.listTarget.querySelectorAll("[data-file-upload-id]")].map((element) => Number(element.dataset.fileUploadId))
            : [];

        return Math.max(0, ...ids.filter(Number.isFinite)) + 1;
    }

    fileFromElement(element, value) {
        const name = element.querySelector("[data-file-upload-name]")?.textContent?.trim() || value || "file";
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

    get defaultMessages() {
        return {
            idle: "Choose files",
            idleMultiple: "Choose files",
            hint: "Drop files here or click to choose",
            button: "Choose files",
            uploading: "Uploading",
            uploaded: "Uploaded",
            uploadFailed: "Upload failed",
            removed: "Removed",
            removeFile: "Remove",
            fileTooBig: "File is too large",
            invalidFileType: "File type is not allowed",
            maxFilesExceeded: "Maximum number of files reached",
        };
    }
}
