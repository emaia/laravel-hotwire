// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";

export default class AlertDialogController extends Controller {
    static targets = ["modal", "backdrop", "dialog"];

    static classes = [
        "hidden",
        "visible",
        "backdropHidden",
        "backdropVisible",
        "dialogHidden",
        "dialogVisible",
        "lockScroll",
    ];

    static values = {
        openDuration: { type: Number, default: 100 },
        closeDuration: { type: Number, default: 100 },
        lockScroll: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    pendingElement = null;
    confirmed = false;
    overlay = null;

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.overlay = createOverlay(this, {
            modalTarget: this.modalTarget,
            backdropTarget: this.backdropTarget,
            dialogTarget: this.dialogTarget,
            hiddenClasses: this.hiddenClasses,
            visibleClasses: this.visibleClasses,
            backdropHiddenClasses: this.backdropHiddenClasses,
            backdropVisibleClasses: this.backdropVisibleClasses,
            dialogHiddenClasses: this.dialogHiddenClasses,
            dialogVisibleClasses: this.dialogVisibleClasses,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            openDuration: this.openDurationValue,
            closeDuration: this.closeDurationValue,
            closeOnEscape: true,
            escapeCapture: true,
            stopEscapePropagation: true,
            closeOnClickOutside: this.closeOnClickOutsideValue,
            onEscape: () => this.cancel(),
        });
    }

    disconnect() {
        this.overlay?.cleanup();
    }

    intercept(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.button !== undefined && event.button !== 0) return;

        if (this.confirmed) {
            this.confirmed = false;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        this.pendingElement = event.target.closest("a, button") ?? event.target;
        this.overlay?.open();
    }

    confirm() {
        const element = this.pendingElement;
        this.overlay?.close();

        setTimeout(() => {
            this.confirmed = true;
            element?.click();
            this.pendingElement = null;
        }, this.closeDurationValue);
    }

    cancel() {
        this.pendingElement = null;
        this.overlay?.close();
    }

    clickOutside(event) {
        event.stopPropagation();

        if (
            this.closeOnClickOutsideValue &&
            this.overlay?.isOpen &&
            this.overlay?.isTop &&
            !this.dialogTarget.contains(event.target) &&
            event.target !== this.dialogTarget
        ) {
            this.cancel();
        }
    }

}
