// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["clearButton", "input"];

    styleElement = null;

    initialize() {
        this.styleElement = document.createElement("style");
        this.styleElement.innerHTML = `
            .clear-input--touched:hover + [data-slot="clear-input-button"],
            .clear-input--touched + [data-slot="clear-input-button"]:hover {
                display: inline-flex !important;
            }
        `;

        document.head.appendChild(this.styleElement);
    }

    connect() {
        this.handleButtonClick = this.handleButtonClick.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.resyncAfterMorph = this.resyncAfterMorph.bind(this);
        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleFocusOut = this.handleFocusOut.bind(this);

        this.resyncAfterMorph();

        this.clearButtonTarget.addEventListener("click", this.handleButtonClick);
        this.inputTarget.addEventListener("input", this.handleInputChange);
        this.inputTarget.addEventListener("focus", this.handleInputChange);
        this.element.addEventListener("focusin", this.handleFocusIn);
        this.element.addEventListener("focusout", this.handleFocusOut);
        document.addEventListener("turbo:render", this.resyncAfterMorph);
    }

    resyncAfterMorph() {
        if (!this.hasInputTarget) return;

        if (this.inputTarget.value.length > 0) {
            this.inputTarget.classList.add("clear-input--touched");
        } else {
            this.inputTarget.classList.remove("clear-input--touched");
        }

        this.#syncButtonVisibility();
    }

    handleInputChange(event) {
        if (event.target.value && !this.inputTarget.classList.contains("clear-input--touched")) {
            this.inputTarget.classList.add("clear-input--touched");
        } else if (!event.target.value && this.inputTarget.classList.contains("clear-input--touched")) {
            this.inputTarget.classList.remove("clear-input--touched");
        }

        this.#syncButtonVisibility();
    }

    handleButtonClick(event) {
        this.inputTarget.value = "";
        this.inputTarget.focus();
        this.inputTarget.classList.remove("clear-input--touched");
        this.clearButtonTarget.classList.add("hidden");

        this.inputTarget.dispatchEvent(new Event("input", { bubbles: true }));
        this.inputTarget.dispatchEvent(new CustomEvent("inputCleared", { detail: event.detail, bubbles: true }));
    }

    handleFocusIn() {
        this.#syncButtonVisibility();
    }

    handleFocusOut(event) {
        if (!this.element.contains(event.relatedTarget)) {
            this.clearButtonTarget.classList.add("hidden");
        }
    }

    #syncButtonVisibility() {
        if (!this.hasInputTarget || !this.hasClearButtonTarget) return;

        if (this.inputTarget.value.length > 0 && this.element.contains(document.activeElement)) {
            this.clearButtonTarget.classList.remove("hidden");
        } else if (this.inputTarget.value.length === 0) {
            this.clearButtonTarget.classList.add("hidden");
        }
    }

    disconnect() {
        if (this.styleElement && this.styleElement.parentNode) {
            this.styleElement.parentNode.removeChild(this.styleElement);
        }

        this.clearButtonTarget.removeEventListener("click", this.handleButtonClick);

        if (this.hasInputTarget) {
            this.inputTarget.removeEventListener("input", this.handleInputChange);
            this.inputTarget.removeEventListener("focus", this.handleInputChange);
        }

        this.element.removeEventListener("focusin", this.handleFocusIn);
        this.element.removeEventListener("focusout", this.handleFocusOut);

        document.removeEventListener("turbo:render", this.resyncAfterMorph);
    }
}
