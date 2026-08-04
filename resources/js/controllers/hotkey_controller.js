// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";

export default class extends Controller {
    click(event) {
        if (this.isClickable && !this.shouldIgnore(event)) {
            event.preventDefault();
            this.element.click();
        }
    }

    focus(event) {
        if (this.isClickable && !this.shouldIgnore(event)) {
            event.preventDefault();
            this.element.focus();
        }
    }

    shouldIgnore(event) {
        const target = event.target;
        return event.defaultPrevented
            || isComposing(event)
            || !!target?.closest?.('input, textarea, select, [contenteditable]:not([contenteditable="false"]), lexxy-editor, .ProseMirror');
    }

    get isClickable() {
        return getComputedStyle(this.element).pointerEvents !== "none";
    }
}
