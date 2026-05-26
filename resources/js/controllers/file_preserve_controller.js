import { Controller } from "@hotwired/stimulus";

const stash = new Map();
let submitHappened = false;

export default class extends Controller {
    connect() {
        this.capture = this.capture.bind(this);
        this.restore = this.restore.bind(this);
        this.trackSubmit = this.trackSubmit.bind(this);

        document.addEventListener("turbo:before-morph", this.capture);
        document.addEventListener("turbo:before-frame-render", this.capture);
        document.addEventListener("turbo:before-render", this.capture);

        document.addEventListener("turbo:morph", this.restore);
        document.addEventListener("turbo:frame-render", this.restore);
        document.addEventListener("turbo:render", this.restore);

        document.addEventListener("turbo:submit-end", this.trackSubmit);
    }

    disconnect() {
        document.removeEventListener("turbo:before-morph", this.capture);
        document.removeEventListener("turbo:before-frame-render", this.capture);
        document.removeEventListener("turbo:before-render", this.capture);

        document.removeEventListener("turbo:morph", this.restore);
        document.removeEventListener("turbo:frame-render", this.restore);
        document.removeEventListener("turbo:render", this.restore);

        document.removeEventListener("turbo:submit-end", this.trackSubmit);

        if (submitHappened) {
            this.#stashFiles();
        }
    }

    capture() {
        if (submitHappened) {
            this.#stashFiles();
        }
    }

    restore() {
        if (!submitHappened) return;

        submitHappened = false;
        requestAnimationFrame(() => {
            if (this.#formHasErrors()) {
                this.#restoreStashed();
            } else {
                stash.clear();
            }
        });
    }

    trackSubmit() {
        submitHappened = true;
    }

    #formHasErrors() {
        const input = this.element.querySelector('input, select, textarea');
        if (input) {
            const form = input.closest('form');
            return form && form.querySelector('[aria-invalid="true"]') !== null;
        }
        return false;
    }

    #stashFiles() {
        this.element.querySelectorAll('input[type="file"]').forEach((input) => {
            if (input.files && input.files.length > 0 && input.name) {
                stash.set(input.name, Array.from(input.files));
            }
        });
    }

    #restoreStashed() {
        this.element.querySelectorAll('input[type="file"]').forEach((input) => {
            if (!input.name) return;
            const files = stash.get(input.name);
            if (files && files.length > 0) {
                const dt = new DataTransfer();
                files.forEach((f) => dt.items.add(f));
                input.files = dt.files;
                stash.delete(input.name);
            }
        });
    }
}
