import { Controller } from "@hotwired/stimulus";

let lastSubmitWasSuccessful = false;
let submitTracked = false;

export default class extends Controller {
    connect() {
        this.onMorph = this.onMorph.bind(this);
        this.trackSubmit = this.trackSubmit.bind(this);

        document.addEventListener("turbo:morph", this.onMorph);
        document.addEventListener("turbo:frame-render", this.onMorph);
        document.addEventListener("turbo:render", this.onMorph);
        document.addEventListener("turbo:submit-end", this.trackSubmit);
    }

    disconnect() {
        document.removeEventListener("turbo:morph", this.onMorph);
        document.removeEventListener("turbo:frame-render", this.onMorph);
        document.removeEventListener("turbo:render", this.onMorph);
        document.removeEventListener("turbo:submit-end", this.trackSubmit);
    }

    onMorph() {
        if (this.element.dataset.resetOnSuccess !== "true") return;
        if (!submitTracked) return;
        if (!lastSubmitWasSuccessful) {
            submitTracked = false;
            return;
        }

        submitTracked = false;
        lastSubmitWasSuccessful = false;

        if (!this.#formHasErrors()) {
            this.resetInputs();
        }
    }

    trackSubmit(event) {
        const submittedForm = event.target;
        if (this.element === submittedForm || (submittedForm && submittedForm.contains(this.element))) {
            lastSubmitWasSuccessful = event.detail?.success === true;
            submitTracked = true;
        }
    }

    resetInputs() {
        this.element.querySelectorAll('input[type="file"]').forEach((input) => {
            input.value = "";
        });
    }

    #formHasErrors() {
        const input = this.element.querySelector('input, select, textarea');
        if (input) {
            const form = input.closest('form');
            return form && form.querySelector('[aria-invalid="true"]') !== null;
        }
        return false;
    }
}
