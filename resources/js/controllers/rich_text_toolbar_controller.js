// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        /** CSS selector resolving to the editor element (the one carrying the rich-text
         *  controller). Set by the Blade view; can be raw HTML for hand-written toolbars. */
        editor: { type: String, default: "" },
    };

    static targets = [
        "bold",
        "italic",
        "underline",
        "bulletList",
        "orderedList",
        "blockquote",
        "codeBlock",
        "link",
        "heading",
        "undo",
        "redo",
    ];

    /** Map of target name → Tiptap state name used by editor.isActive().
     *  Subclasses extend via: static activeStates = { ...super.activeStates, table: "table" };
     *  Targets without an isActive concept (undo, redo) deliberately stay out.
     *  Heading is special-cased in syncHeading() because it needs an attr lookup. */
    static activeStates = {
        bold: "bold",
        italic: "italic",
        underline: "underline",
        bulletList: "bulletList",
        orderedList: "orderedList",
        blockquote: "blockquote",
        codeBlock: "codeBlock",
        link: "link",
    };

    editorElement = null;
    syncBound = null;
    cachedEditor = null;

    connect() {
        if (this.editorValue === "") return;

        this.editorElement = document.querySelector(this.editorValue);
        if (!this.editorElement) return;

        this.syncBound = (event) => {
            if (event.detail?.editor) this.cachedEditor = event.detail.editor;
            this.syncButtons();
        };
        this.editorElement.addEventListener("rich-text:state", this.syncBound);

        // If the editor controller already mounted, grab its editor synchronously
        // so the initial active state renders without waiting for a state event.
        this.cacheEditorFromController();
        if (this.cachedEditor) this.syncButtons();
    }

    disconnect() {
        if (this.editorElement && this.syncBound) {
            this.editorElement.removeEventListener("rich-text:state", this.syncBound);
        }
        this.syncBound = null;
        this.editorElement = null;
        this.cachedEditor = null;
    }

    /** Walk the editor element's data-controller attribute and grab the first controller
     *  instance that exposes an `editor` getter. Identifier-agnostic — works with the
     *  default `rich-text` and any subclass-via-swap (`rich-text-full`, etc.). */
    cacheEditorFromController() {
        if (!this.editorElement) return;
        const ids = (this.editorElement.getAttribute("data-controller") ?? "")
            .split(/\s+/)
            .filter(Boolean);
        for (const id of ids) {
            const ctrl = this.application.getControllerForElementAndIdentifier(this.editorElement, id);
            if (ctrl?.editor) {
                this.cachedEditor = ctrl.editor;
                return;
            }
        }
    }

    get editor() {
        return this.cachedEditor ?? null;
    }

    syncButtons() {
        const editor = this.editor;
        if (!editor) return;

        for (const [target, state] of Object.entries(this.constructor.activeStates)) {
            const targets = this[`${target}Targets`];
            if (!targets?.length) continue;
            this.applyActive(targets, editor.isActive(state));
        }

        this.syncHeading(editor);
    }

    syncHeading(editor) {
        if (!this.hasHeadingTarget) return;
        this.headingTargets.forEach((btn) => {
            const level = parseInt(btn.dataset.level ?? "1", 10);
            this.applyActive([btn], editor.isActive("heading", { level }));
        });
    }

    applyActive(targets, active) {
        targets.forEach((btn) => {
            btn.classList.toggle("is-active", active);
            btn.setAttribute("aria-pressed", active ? "true" : "false");
        });
    }

    // --- actions ---

    bold() { this.editor?.chain().focus().toggleBold().run(); }
    italic() { this.editor?.chain().focus().toggleItalic().run(); }
    underline() { this.editor?.chain().focus().toggleUnderline().run(); }
    bulletList() { this.editor?.chain().focus().toggleBulletList().run(); }
    orderedList() { this.editor?.chain().focus().toggleOrderedList().run(); }
    blockquote() { this.editor?.chain().focus().toggleBlockquote().run(); }
    codeBlock() { this.editor?.chain().focus().toggleCodeBlock().run(); }
    undo() { this.editor?.chain().focus().undo().run(); }
    redo() { this.editor?.chain().focus().redo().run(); }

    heading(event) {
        const raw = event?.params?.level ?? event?.currentTarget?.dataset?.level ?? "1";
        const level = parseInt(raw, 10);
        this.editor?.chain().focus().toggleHeading({ level }).run();
    }

    link(event) {
        const fromParams = event?.params?.url;
        const url = typeof fromParams === "string"
            ? fromParams
            : globalThis.prompt?.("URL", this.editor?.getAttributes?.("link")?.href ?? "");

        if (url === null || url === undefined) return;
        if (url === "") {
            this.editor?.chain().focus().unsetLink().run();
            return;
        }
        this.editor?.chain().focus().setLink({ href: url }).run();
    }
}
