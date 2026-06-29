// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { FocusTrap } from "./_focus_trap.js";

export default class DrawerController extends Controller {
    static targets = ["container", "backdrop", "panel"];

    static classes = [
        "hidden",
        "visible",
        "backdropHidden",
        "backdropVisible",
        "panelHidden",
        "panelVisible",
        "lockScroll",
    ];

    static values = {
        openDuration: { type: Number, default: 300 },
        closeDuration: { type: Number, default: 300 },
        lockScroll: { type: Boolean, default: true },
        closeOnEscape: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    isOpen = false;
    isAnimating = false;
    triggerElement = null;
    focusTrap = null;
    closeTimer = null;
    openTimer = null;

    connect() {
        this.handleEscapeKey = this.handleEscapeKey.bind(this);
        // Capture phase so we run before bubble-phase Escape listeners on the
        // document (e.g. an enclosing dropdown's) and can stop them while open.
        document.addEventListener("keydown", this.handleEscapeKey, true);

        if (this.hasPanelTarget) {
            this.focusTrap = new FocusTrap(this.panelTarget);
        }
    }

    disconnect() {
        document.removeEventListener("keydown", this.handleEscapeKey, true);
        clearTimeout(this.openTimer);
        clearTimeout(this.closeTimer);

        if (this.isOpen) {
            this.#hardClose();
        } else {
            this.focusTrap?.deactivate();
        }
    }

    open(event) {
        if (event) {
            if (event.ctrlKey || event.metaKey || event.shiftKey) return;
            if (event.button !== undefined && event.button !== 0) return;
        }
        if (this.isOpen || this.isAnimating) return;

        this.triggerElement = event?.currentTarget ?? event?.target ?? document.activeElement;
        this.#open();
    }

    close() {
        if (!this.isOpen) return;
        this.#close();
    }

    toggle(event) {
        if (this.isOpen) {
            this.close();
        } else {
            this.open(event);
        }
    }

    clickOutside() {
        if (!this.closeOnClickOutsideValue || !this.isOpen) return;
        this.close();
    }

    handleEscapeKey(event) {
        if (!this.closeOnEscapeValue || event.key !== "Escape" || !this.isOpen) return;

        event.stopImmediatePropagation();
        event.preventDefault();
        this.close();
    }

    closeForCache() {
        if (!this.isOpen) return;
        this.#hardClose();
    }

    #open() {
        this.isOpen = true;
        this.isAnimating = true;

        this.containerTarget.hidden = false;

        if (this.lockScrollValue) {
            document.body.classList.add(...this.lockScrollClasses);
        }

        requestAnimationFrame(() => {
            this.containerTarget.classList.remove(...this.hiddenClasses);
            this.containerTarget.classList.add(...this.visibleClasses);

            if (this.hasBackdropTarget) {
                this.backdropTarget.classList.remove(...this.backdropHiddenClasses);
                this.backdropTarget.classList.add(...this.backdropVisibleClasses);
            }

            this.panelTarget.classList.remove(...this.panelHiddenClasses);
            this.panelTarget.classList.add(...this.panelVisibleClasses);

            this.focusTrap?.activate();

            clearTimeout(this.openTimer);
            this.openTimer = setTimeout(() => {
                this.isAnimating = false;
                this.dispatch("opened");
            }, this.openDurationValue);
        });
    }

    #close() {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.isAnimating = true;
        this.focusTrap?.deactivate();

        this.containerTarget.classList.remove(...this.visibleClasses);
        this.containerTarget.classList.add(...this.hiddenClasses);

        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.remove(...this.backdropVisibleClasses);
            this.backdropTarget.classList.add(...this.backdropHiddenClasses);
        }

        this.panelTarget.classList.remove(...this.panelVisibleClasses);
        this.panelTarget.classList.add(...this.panelHiddenClasses);

        if (this.lockScrollValue) {
            document.body.classList.remove(...this.lockScrollClasses);
        }

        const trigger = this.triggerElement;
        this.triggerElement = null;
        if (trigger && typeof trigger.focus === "function" && !trigger.disabled) {
            trigger.focus();
        }

        clearTimeout(this.closeTimer);
        this.closeTimer = setTimeout(() => {
            this.containerTarget.hidden = true;
            this.isAnimating = false;
            this.dispatch("closed");
        }, this.closeDurationValue);
    }

    // Synchronous close used by disconnect and turbo:before-cache — leaves the
    // DOM in a fully closed state without animation, so Turbo snapshots and
    // controller teardown don't capture a half-open frame.
    #hardClose() {
        clearTimeout(this.openTimer);
        clearTimeout(this.closeTimer);

        this.isOpen = false;
        this.isAnimating = false;
        this.focusTrap?.deactivate();

        this.containerTarget.classList.remove(...this.visibleClasses);
        this.containerTarget.classList.add(...this.hiddenClasses);

        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.remove(...this.backdropVisibleClasses);
            this.backdropTarget.classList.add(...this.backdropHiddenClasses);
        }

        this.panelTarget.classList.remove(...this.panelVisibleClasses);
        this.panelTarget.classList.add(...this.panelHiddenClasses);

        if (this.lockScrollValue) {
            document.body.classList.remove(...this.lockScrollClasses);
        }

        this.containerTarget.hidden = true;
        this.triggerElement = null;
    }
}
