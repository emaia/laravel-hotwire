import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["clearButton", "input"];

    styleElement = null;

    initialize() {
        this.styleElement = document.createElement("style");
        this.styleElement.innerHTML = `
            .clear-input--touched:focus + .clear-input-button,
            .clear-input--touched:hover + .clear-input-button,
            .clear-input--touched + .clear-input-button:hover {
                display: block !important;
            }
        `;

        document.head.appendChild(this.styleElement);
    }

    connect() {
        if (this.inputTarget.value.length > 0) {
            this.inputTarget.classList.add("clear-input--touched");
        }

        this.handleButtonClick = this.handleButtonClick.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);

        this.clearButtonTarget.addEventListener("click", this.handleButtonClick);
        this.inputTarget.addEventListener("input", this.handleInputChange);
        this.inputTarget.addEventListener("focus", this.handleInputChange);
    }

    handleInputChange(event) {
        if (event.target.value && !this.inputTarget.classList.contains("clear-input--touched")) {
            this.inputTarget.classList.add("clear-input--touched");
        } else if (!event.target.value && this.inputTarget.classList.contains("clear-input--touched")) {
            this.inputTarget.classList.remove("clear-input--touched");
        }
    }

    handleButtonClick(event) {
        this.inputTarget.value = "";
        this.inputTarget.focus();
        this.inputTarget.classList.remove("clear-input--touched");

        this.inputTarget.dispatchEvent(new CustomEvent("inputCleared", { detail: event.detail, bubbles: true }));
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
    }
}
