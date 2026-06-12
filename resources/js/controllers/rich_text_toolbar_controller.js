// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static outlets = ["rich-text"];

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

    syncBound = null;
    boundElement = null;

    richTextOutletConnected(_outlet, element) {
        this.boundElement = element;
        this.syncBound = () => this.syncButtons();
        element.addEventListener("rich-text:state", this.syncBound);
        this.syncButtons();
    }

    richTextOutletDisconnected(_outlet, element) {
        if (this.syncBound) {
            element.removeEventListener("rich-text:state", this.syncBound);
            this.syncBound = null;
        }
        this.boundElement = null;
    }

    disconnect() {
        if (this.syncBound && this.boundElement) {
            this.boundElement.removeEventListener("rich-text:state", this.syncBound);
        }
        this.syncBound = null;
        this.boundElement = null;
    }

    get editor() {
        return this.hasRichTextOutlet ? this.richTextOutlet.editor : null;
    }

    syncButtons() {
        const editor = this.editor;
        if (!editor) return;

        const reflect = (name, targets) => {
            const active = editor.isActive(name);
            this.applyActive(targets, active);
        };

        if (this.hasBoldTarget) reflect("bold", this.boldTargets);
        if (this.hasItalicTarget) reflect("italic", this.italicTargets);
        if (this.hasUnderlineTarget) reflect("underline", this.underlineTargets);
        if (this.hasBulletListTarget) reflect("bulletList", this.bulletListTargets);
        if (this.hasOrderedListTarget) reflect("orderedList", this.orderedListTargets);
        if (this.hasBlockquoteTarget) reflect("blockquote", this.blockquoteTargets);
        if (this.hasCodeBlockTarget) reflect("codeBlock", this.codeBlockTargets);
        if (this.hasLinkTarget) reflect("link", this.linkTargets);

        if (this.hasHeadingTarget) {
            this.headingTargets.forEach((btn) => {
                const level = parseInt(btn.dataset.level ?? "1", 10);
                const active = editor.isActive("heading", { level });
                this.applyActive([btn], active);
            });
        }
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
