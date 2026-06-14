// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { RichTextEditor } from "./_rich_text_editor.js";
import { attachMorphRecovery } from "./_turbo_morph_recovery.js";

export default class extends Controller {
    static values = {
        id: { type: String, default: "" },
        placeholder: { type: String, default: "" },
        editable: { type: Boolean, default: true },
        output: { type: String, default: "html" },
        editorClass: { type: String, default: "" },
        imageUpload: { type: Boolean, default: false },
    };

    static targets = ["editor", "input"];

    instance = null;

    connect() {
        this.initEditor();

        this.detachMorphRecovery = attachMorphRecovery(this, {
            isStale: () => !this.hasEditorTarget || !this.editorTarget.querySelector(".ProseMirror"),
            recover: () => {
                this.instance?.destroy();
                this.instance = null;
                this.initEditor();
            },
        });
    }

    disconnect() {
        this.detachMorphRecovery?.();
        this.instance?.destroy();
        this.instance = null;
    }

    initEditor() {
        const placeholder = this.placeholderValue || null;
        const initial = this.hasInputTarget ? this.inputTarget.value : "";

        this.instance = new RichTextEditor(this.editorTarget, {
            content: this.parseInitial(initial),
            editable: this.editableValue,
            placeholder,
            editorClass: this.editorClassValue,
            extensions: this.extensions({ placeholder }),
            onUpdate: ({ html, json }) => this.handleUpdate(html, json),
            onFocus: () => this.dispatch("focus"),
            onBlur: () => this.dispatch("blur"),
            onSelectionUpdate: () => this.dispatch("state", { detail: { editor: this.editor } }),
            onImageDrop: this.imageUploadValue ? (file) => this.handleImageUpload(file) : null,
        });

        // Clear a leftover `<p></p>` from old()/database when Tiptap mounts an
        // effectively-empty doc, so the next submit ships "" not placeholder markup.
        if (this.editor?.isEmpty) {
            this.syncInput();
        }

        this.dispatch("ready", { detail: { editor: this.editor } });
        this.dispatch("state", { detail: { editor: this.editor } });
    }

    handleUpdate(html, json) {
        this.syncInput();
        this.dispatch("change", { detail: { html, json } });
        this.dispatch("state", { detail: { editor: this.editor } });
    }

    syncInput() {
        if (!this.hasInputTarget || !this.editor) return;
        if (this.editor.isEmpty) {
            this.inputTarget.value = "";
            return;
        }
        this.inputTarget.value = this.outputValue === "json"
            ? JSON.stringify(this.instance.json)
            : this.instance.html;
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
