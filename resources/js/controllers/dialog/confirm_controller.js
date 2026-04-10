import { Controller } from "@hotwired/stimulus";

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
        openDuration: { type: Number, default: 200 },
        closeDuration: { type: Number, default: 200 },
        lockScroll: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    pendingElement = null;
    triggerElement = null;
    confirmed = false;
    isOpen = false;
    trapPriming = false;

    connect() {
        this.handleEscapeKey = this.handleEscapeKey.bind(this);
        this.handleFocusTrap = this.handleFocusTrap.bind(this);
        document.addEventListener("keydown", this.handleEscapeKey);
        document.addEventListener("keydown", this.handleFocusTrap);
    }

    disconnect() {
        document.removeEventListener("keydown", this.handleEscapeKey);
        document.removeEventListener("keydown", this.handleFocusTrap);

        if (this.isOpen) {
            this.#close();
        }
    }

    intercept(event) {
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
        if (event.key === "Escape" && this.isOpen) {
            this.cancel();
        }
    }

    handleFocusTrap(event) {
        if (event.key !== "Tab" || this.modalTarget.hidden || !this.isOpen) return;

        const focusableElements = this.#getFocusableElements();
        if (focusableElements.length === 0) return;

        const first = focusableElements[0];
        const last = focusableElements[focusableElements.length - 1];
        const active = document.activeElement;

        if (this.trapPriming) {
            event.preventDefault();
            this.trapPriming = false;
            first.focus();
            return;
        }

        if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        } else if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        }
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

            this.trapPriming = true;
        });
    }

    #close() {
        if (!this.isOpen) return;

        this.isOpen = false;

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

    #getFocusableElements() {
        return this.modalTarget.querySelectorAll(
            'a[href], area[href], input:not([disabled]):not([type="hidden"]), ' +
                "select:not([disabled]), textarea:not([disabled]), " +
                'button:not([disabled]), [tabindex]:not([tabindex="-1"])',
        );
    }
}
