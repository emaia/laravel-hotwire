// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import Dropzone from "@deltablot/dropzone";
import "@deltablot/dropzone/dist/dropzone.css";

Dropzone.autoDiscover = false;

export default class extends Controller {
    static targets = ["announcer"];

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
    };

    dropzone = null;
    tokensByFile = new WeakMap();
    inputsByFile = new WeakMap();

    connect() {
        this.dropzone = new Dropzone(this.element, this.dropzoneOptions());
        this.wireEvents();
        this.afterInit();
        this.dispatch("ready");
    }

    disconnect() {
        this.dropzone?.destroy();
        this.dropzone = null;
    }

    dropzoneOptions() {
        const opts = {
            url: this.urlValue,
            paramName: this.paramNameValue,
            acceptedFiles: this.acceptValue || null,
            maxFilesize: this.maxSizeBytesValue ? this.maxSizeBytesValue / (1024 * 1024) : null,
            maxFiles: this.maxFilesValue || null,
            parallelUploads: this.parallelUploadsValue,
            uploadMultiple: false,
            headers: this.csrfHeaders(),
            ...this.defaultOptions(),
        };
        if (!this.previewValue) opts.previewsContainer = false;
        return opts;
    }

    wireEvents() {
        this.dropzone.on("addedfile", (file) => {
            this.announce(`Uploading ${file.name}`);
            this.dispatch("added", { detail: { file } });
        });
        this.dropzone.on("uploadprogress", (file, percent, bytes) => {
            this.dispatch("progress", { detail: { file, percent, bytes } });
        });
        this.dropzone.on("success", (file, response) => this.handleSuccess(file, response));
        this.dropzone.on("error", (file, message, xhr) => this.handleError(file, message, xhr));
        this.dropzone.on("removedfile", (file) => this.handleRemoved(file));
    }

    handleSuccess(file, response) {
        const value = this.extractValue(response);
        if (value != null) this.tokensByFile.set(file, value);
        if (this.emitHiddenValue) this.appendHidden(file, value);
        this.announce(`Uploaded ${file.name}`);
        this.dispatch("success", { detail: { file, response, value } });
    }

    handleError(file, message, xhr) {
        const text = this.extractErrorMessage(message);
        // Dropzone writes the raw `message` into the thumb's `[data-dz-errormessage]`,
        // which coerces objects to "[object Object]". Override with the normalized text.
        file.previewElement
            ?.querySelector("[data-dz-errormessage]")
            ?.replaceChildren(document.createTextNode(text));
        this.announce(`Upload failed: ${text}`);
        this.dispatch("error", { detail: { file, message, xhr, text } });
    }

    /** Coerces a Dropzone error payload into a user-facing string.
     *  Handles Laravel's two common shapes: `{ errors: { field: [...] } }` (422 validation,
     *  preferred when present) and `{ message }` (500 default). Falls back to a generic. */
    extractErrorMessage(raw) {
        if (typeof raw === "string") return raw;
        if (raw == null) return "Upload failed";
        if (typeof raw === "object") {
            if (raw.errors && typeof raw.errors === "object") {
                const firstField = Object.values(raw.errors)[0];
                if (Array.isArray(firstField) && typeof firstField[0] === "string") return firstField[0];
            }
            if (typeof raw.message === "string") return raw.message;
            return "Upload failed";
        }
        return String(raw);
    }

    handleRemoved(file) {
        this.removeHidden(file);
        const token = this.tokensByFile.get(file);
        if (token && this.deleteUrlValue !== "") {
            this.deleteRemote(token).catch((e) =>
                console.error("file-upload delete failed:", e)
            );
        }
        this.tokensByFile.delete(file);
        this.announce(`Removed ${file.name}`);
        this.dispatch("removed", { detail: { file } });
    }

    extractValue(response) {
        if (response == null) return null;
        if (typeof response === "string") return response;
        return response[this.responseKeyValue] ?? null;
    }

    appendHidden(file, value) {
        if (value == null || this.hiddenNameValue === "") return;
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = this.hiddenNameValue;
        input.value = value;
        input.dataset.hwUpload = "";
        this.inputsByFile.set(file, input);
        this.element.appendChild(input);
    }

    removeHidden(file) {
        const input = this.inputsByFile.get(file);
        input?.remove();
        this.inputsByFile.delete(file);
    }

    async deleteRemote(token) {
        const url = this.deleteUrlValue.replace(":token", encodeURIComponent(token));
        await fetch(url, { method: "DELETE", headers: this.csrfHeaders() });
    }

    csrfHeaders() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
        return token ? { "X-CSRF-TOKEN": token } : {};
    }

    openPicker(event) {
        event?.preventDefault?.();
        this.dropzone?.hiddenFileInput?.click();
    }

    announce(message) {
        if (!this.hasAnnouncerTarget) return;
        this.announcerTarget.textContent = message;
    }

    /** Override in subclass to merge extra Dropzone options (templates, dictionaries, etc.). */
    defaultOptions() {
        return {};
    }

    /** Override in subclass to attach extra listeners on this.dropzone after init. */
    afterInit() {}
}
