import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        document.addEventListener("turbo:morph", this.checkAndReset.bind(this));
    }

    disconnect() {
        document.removeEventListener("turbo:morph", this.checkAndReset.bind(this));
    }

    checkAndReset() {
        if (this.element.dataset.resetOnSuccess === "true") {
            this.resetInputs();
        }
    }

    resetInputs() {
        this.element.querySelectorAll('input[type="file"]').forEach((input) => {
            input.value = "";
        });
    }
}
