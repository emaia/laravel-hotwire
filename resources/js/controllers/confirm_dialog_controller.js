// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { FocusTrap } from "./_focus_trap.js";

export default class ConfirmController extends Controller {
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
    triggerElement = null;
    confirmed = false;
    isOpen = false;
    focusTrap = null;

    connect() {
        this.handleEscapeKey = this.handleEscapeKey.bind(this);
        document.addEventListener("keydown", this.handleEscapeKey, true);

        if (this.hasModalTarget) {
            this.focusTrap = new FocusTrap(this.modalTarget);
        }
    }

    disconnect() {
        document.removeEventListener("keydown", this.handleEscapeKey, true);
        this.focusTrap?.deactivate();

        if (this.isOpen) {
            this.#close();
        }
    }

    intercept(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        if (this.confirmed) {
            this.confirmed = false;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        this.pendingElement = event.target.closest("a, button") ?? event.target;
        this.triggerElement = this.pendingElement;
        this.#open();
    }

    confirm() {
        const element = this.pendingElement;
        this.#close();

        setTimeout(() => {
            this.confirmed = true;
            element?.click();
            this.pendingElement = null;
        }, this.closeDurationValue);
    }

    cancel() {
        this.pendingElement = null;
        this.#close();
    }

    clickOutside(event) {
        event.stopPropagation();

        if (
            this.closeOnClickOutsideValue &&
            this.isOpen &&
            !this.dialogTarget.contains(event.target) &&
            event.target !== this.dialogTarget
        ) {
            this.cancel();
        }
    }

    handleEscapeKey(event) {
        if (event.key !== "Escape" || !this.isOpen) return;

        event.stopImmediatePropagation();
        event.preventDefault();
        this.cancel();
    }

    #open() {
        this.isOpen = true;
        this.modalTarget.hidden = false;

        if (this.lockScrollValue) {
            document.body.classList.add(...this.lockScrollClasses);
        }

        requestAnimationFrame(() => {
            this.modalTarget.classList.remove(...this.hiddenClasses);
            this.modalTarget.classList.add(...this.visibleClasses);

            this.backdropTarget.classList.remove(...this.backdropHiddenClasses);
            this.backdropTarget.classList.add(...this.backdropVisibleClasses);

            this.dialogTarget.classList.remove(...this.dialogHiddenClasses);
            this.dialogTarget.classList.add(...this.dialogVisibleClasses);

            this.focusTrap?.activate();
        });
    }

    #close() {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.focusTrap?.deactivate();

        this.modalTarget.classList.remove(...this.visibleClasses);
        this.modalTarget.classList.add(...this.hiddenClasses);

        this.backdropTarget.classList.remove(...this.backdropVisibleClasses);
        this.backdropTarget.classList.add(...this.backdropHiddenClasses);

        this.dialogTarget.classList.remove(...this.dialogVisibleClasses);
        this.dialogTarget.classList.add(...this.dialogHiddenClasses);

        if (this.lockScrollValue) {
            document.body.classList.remove(...this.lockScrollClasses);
        }

        if (this.triggerElement && typeof this.triggerElement.focus === "function") {
            this.triggerElement.focus();
        }

        setTimeout(() => {
            this.modalTarget.hidden = true;
        }, this.closeDurationValue);
    }
}
