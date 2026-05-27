import { Controller } from "@hotwired/stimulus";
import { formHasErrors } from "./_form_errors";

export default class extends Controller {
    connect() {
        this.armed = false;
        this.lastSubmitSucceeded = false;
        this.onRender = this.onRender.bind(this);
        this.trackSubmit = this.trackSubmit.bind(this);

        document.addEventListener("turbo:render", this.onRender);
        document.addEventListener("turbo:frame-render", this.onRender);
        document.addEventListener("turbo:submit-end", this.trackSubmit);
    }

    disconnect() {
        document.removeEventListener("turbo:render", this.onRender);
        document.removeEventListener("turbo:frame-render", this.onRender);
        document.removeEventListener("turbo:submit-end", this.trackSubmit);
    }

    trackSubmit(event) {
        const form = event.target;
        if (this.element === form || form?.contains(this.element)) {
            // `success` reflects the HTTP status (2xx/3xx); formHasErrors() in
            // onRender still guards against a 200 that re-renders the form with
            // validation errors.
            this.lastSubmitSucceeded = event.detail?.success === true;
            this.armed = true;
        }
    }

    onRender() {
        if (this.element.dataset.resetOnSuccess !== "true") return;
        if (!this.armed) return;

        this.armed = false;
        if (this.lastSubmitSucceeded && !formHasErrors(this.element)) {
            this.resetInputs();
        }
    }

    resetInputs() {
        this.fileInputs().forEach((input) => {
            input.value = "";
        });
    }

    fileInputs() {
        // Mounted either on the file input itself or on a wrapper around it.
        if (this.element.matches?.('input[type="file"]')) return [this.element];
        return this.element.querySelectorAll('input[type="file"]');
    }
}
