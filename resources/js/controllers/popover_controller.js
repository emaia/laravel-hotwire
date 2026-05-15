import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["trigger", "content"];

    connect() {
        this._hasTransition = this.calcHasTransition();
        this.setupListeners();
        this.close(false);
        this.dispatch("basecoat:initialized", { bubbles: true });
    }

    disconnect() {
        this.removeListeners();
    }

    get isOpen() {
        return this.triggerTarget.getAttribute("aria-expanded") === "true";
    }

    calcHasTransition() {
        const style = getComputedStyle(this.contentTarget);
        return parseFloat(style.transitionDuration) > 0 || parseFloat(style.transitionDelay) > 0;
    }

    open() {
        document.dispatchEvent(
            new CustomEvent("basecoat:popover", {
                detail: { source: this.element },
            }),
        );

        // Foco automático em elemento com atributo "autofocus"
        const autoFocusEl = this.contentTarget.querySelector("[autofocus]");
        if (autoFocusEl) {
            if (this._hasTransition) {
                this.contentTarget.addEventListener("transitionend", () => autoFocusEl.focus(), { once: true });
            } else {
                autoFocusEl.focus();
            }
        }

        this.triggerTarget.setAttribute("aria-expanded", "true");
        this.contentTarget.setAttribute("aria-hidden", "false");
    }

    close(focusOnTrigger = true) {
        if (!this.isOpen) return;

        this.triggerTarget.setAttribute("aria-expanded", "false");
        this.contentTarget.setAttribute("aria-hidden", "true");

        if (focusOnTrigger) this.triggerTarget.focus();
    }

    toggle = () => {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    };

    handleTriggerClick = () => {
        this.toggle();
    };

    handleKeydown = (event) => {
        if (event.key === "Escape") this.close();
    };

    handleClickOutside = (event) => {
        if (!this.element.contains(event.target)) this.close();
    };

    handleExternalPopover = (event) => {
        if (event.detail.source !== this.element) {
            this.close(false); // não rouba o foco
        }
    };

    setupListeners() {
        this._onTriggerClick = this.handleTriggerClick;
        this._onKeydown = this.handleKeydown;
        this._onClickOutside = this.handleClickOutside;
        this._onExternalPopover = this.handleExternalPopover;

        this.triggerTarget.addEventListener("click", this._onTriggerClick);
        this.element.addEventListener("keydown", this._onKeydown);
        document.addEventListener("click", this._onClickOutside);
        document.addEventListener("basecoat:popover", this._onExternalPopover);
    }

    removeListeners() {
        if (this.triggerTarget) {
            this.triggerTarget.removeEventListener("click", this._onTriggerClick);
        }
        if (this.element) {
            this.element.removeEventListener("keydown", this._onKeydown);
        }
        document.removeEventListener("click", this._onClickOutside);
        document.removeEventListener("basecoat:popover", this._onExternalPopover);
    }
}
