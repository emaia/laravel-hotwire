// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { RichTextEditor } from "./_rich_text_editor.js";

export default class extends Controller {
    static values = {
        id: { type: String, default: "" },
        placeholder: { type: String, default: "" },
        editable: { type: Boolean, default: true },
        output: { type: String, default: "html" },
        imageUpload: { type: Boolean, default: false },
    };

    static targets = ["editor", "input"];

    instance = null;

    connect() {
        const placeholder = this.placeholderValue || null;
        const initial = this.hasInputTarget ? this.inputTarget.value : "";

        this.instance = new RichTextEditor(this.editorTarget, {
            content: this.parseInitial(initial),
            editable: this.editableValue,
            placeholder,
            extensions: this.extensions({ placeholder }),
            onUpdate: ({ html, json }) => this.handleUpdate(html, json),
            onFocus: () => this.dispatch("focus"),
            onBlur: () => this.dispatch("blur"),
            onSelectionUpdate: () => this.dispatch("state", { detail: { editor: this.editor } }),
            onImageDrop: this.imageUploadValue ? (file) => this.handleImageUpload(file) : null,
        });

        this.dispatch("ready", { detail: { editor: this.editor } });
        this.dispatch("state", { detail: { editor: this.editor } });
    }

    disconnect() {
        this.instance?.destroy();
        this.instance = null;
    }

    handleUpdate(html, json) {
        if (this.hasInputTarget) {
            this.inputTarget.value =
                this.outputValue === "json" ? JSON.stringify(json) : html;
        }
        this.dispatch("change", { detail: { html, json } });
        this.dispatch("state", { detail: { editor: this.editor } });
    }

    handleImageUpload(file) {
        this.dispatch("image-upload", { detail: { file, editor: this.editor } });
    }

    // --- public API ---

    get editor() {
        return this.instance?.editor ?? null;
    }

    get html() {
        return this.instance?.html ?? "";
    }

    get json() {
        return this.instance?.json ?? null;
    }

    setContent(content) {
        this.instance?.setContent(content);
    }

    clear() {
        this.instance?.clear();
    }

    focus() {
        this.instance?.focus();
    }

    // --- subclass hook ---

    /** Override in a subclass to replace the extension list.
     *  Receives `{ placeholder }` so subclasses can rebuild defaults via
     *  `defaultExtensions({ placeholder })` and append their own. Return
     *  `null` to use the default list. */
    extensions(_options) {
        return null;
    }

    // --- internals ---

    parseInitial(raw) {
        if (this.outputValue !== "json" || raw === "") return raw;
        try {
            return JSON.parse(raw);
        } catch (_error) {
            return raw;
        }
    }
}
