// @hotwire-package
//
// Tiptap-backed rich text editor. Pure JS wrapper — no Stimulus. The
// `rich-text` controller mounts one instance per editor and bridges to the
// DOM (hidden input sync, dispatch events). Subclasses can override the
// controller's `extensions()` hook and rebuild the list with
// `defaultExtensions()`.

import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Placeholder from "@tiptap/extension-placeholder";
import Link from "@tiptap/extension-link";
import Underline from "@tiptap/extension-underline";

export function defaultExtensions({ placeholder = null } = {}) {
    const list = [StarterKit, Link.configure({ openOnClick: false }), Underline];
    if (placeholder) {
        list.push(Placeholder.configure({ placeholder }));
    }
    return list;
}

export class RichTextEditor {
    constructor(element, options = {}) {
        const {
            content = "",
            editable = true,
            placeholder = null,
            editorClass = "",
            extensions,
            onUpdate,
            onFocus,
            onBlur,
            onSelectionUpdate,
            onImageDrop,
        } = options;

        const editorProps = {};
        if (editorClass) {
            editorProps.attributes = { class: editorClass };
        }
        if (onImageDrop) {
            editorProps.handlePaste = (_view, event) => this.handleImage(event, onImageDrop);
            editorProps.handleDrop = (_view, event) => this.handleImage(event, onImageDrop);
        }
        const editorPropsOption = Object.keys(editorProps).length > 0 ? editorProps : undefined;

        this.editor = new Editor({
            element,
            content,
            editable,
            extensions: extensions ?? defaultExtensions({ placeholder }),
            onUpdate: ({ editor }) =>
                onUpdate?.({ editor, html: editor.getHTML(), json: editor.getJSON() }),
            onFocus: ({ editor }) => onFocus?.({ editor }),
            onBlur: ({ editor }) => onBlur?.({ editor }),
            onSelectionUpdate: ({ editor }) => onSelectionUpdate?.({ editor }),
            editorProps: editorPropsOption,
        });
    }

    handleImage(event, callback) {
        const files = Array.from(
            event.clipboardData?.files ?? event.dataTransfer?.files ?? [],
        );
        const images = files.filter((f) => f.type?.startsWith("image/"));
        if (images.length === 0) return false;
        event.preventDefault?.();
        images.forEach((file) => callback(file));
        return true;
    }

    get html() {
        return this.editor.getHTML();
    }

    get json() {
        return this.editor.getJSON();
    }

    setContent(content) {
        this.editor.commands.setContent(content, true);
    }

    clear() {
        this.editor.commands.clearContent(true);
    }

    focus() {
        this.editor.commands.focus();
    }

    setEditable(editable) {
        this.editor.setEditable(editable);
    }

    destroy() {
        this.editor.destroy();
    }
}
