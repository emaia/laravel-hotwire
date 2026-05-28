import { Controller } from "@hotwired/stimulus";
import { formHasErrors } from "./_form_errors";

const stash = new Map();

export default class extends Controller {
    connect() {
        this.armed = false;
        this.capture = this.capture.bind(this);
        this.restore = this.restore.bind(this);
        this.trackSubmit = this.trackSubmit.bind(this);

        document.addEventListener("turbo:before-render", this.capture);
        document.addEventListener("turbo:before-frame-render", this.capture);
        document.addEventListener("turbo:render", this.restore);
        document.addEventListener("turbo:frame-render", this.restore);
        document.addEventListener("turbo:submit-end", this.trackSubmit);
    }

    disconnect() {
        document.removeEventListener("turbo:before-render", this.capture);
        document.removeEventListener("turbo:before-frame-render", this.capture);
        document.removeEventListener("turbo:render", this.restore);
        document.removeEventListener("turbo:frame-render", this.restore);
        document.removeEventListener("turbo:submit-end", this.trackSubmit);

        // Frame replacement: hand the selection to the incoming instance.
        if (this.armed) this.#stashFiles();
    }

    trackSubmit(event) {
        const form = event.target;
        if (this.element === form || form?.contains(this.element)) {
            this.armed = true;
        }
    }

    capture() {
        if (this.armed) this.#stashFiles();
    }

    restore() {
        this.armed = false;
        if (!this.#hasStash()) return;

        requestAnimationFrame(() => {
            if (formHasErrors(this.element)) {
                this.#restoreStashed();
            } else {
                this.#clearStash();
            }
        });
    }

    #fileInputs() {
        // The controller may be mounted on the file input itself or on a
        // wrapper that contains one or more file inputs.
        if (this.element.matches?.('input[type="file"]')) return [this.element];
        return this.element.querySelectorAll('input[type="file"]');
    }

    #hasStash() {
        return [...this.#fileInputs()].some((input) => stash.has(input.name));
    }

    #stashFiles() {
        this.#fileInputs().forEach((input) => {
            if (input.name && input.files.length > 0) {
                stash.set(input.name, Array.from(input.files));
            }
        });
    }

    #restoreStashed() {
        this.#fileInputs().forEach((input) => {
            const files = stash.get(input.name);
            if (!files?.length) return;

            const dt = new DataTransfer();
            files.forEach((file) => dt.items.add(file));
            input.files = dt.files;
            stash.delete(input.name);
        });
    }

    #clearStash() {
        this.#fileInputs().forEach((input) => stash.delete(input.name));
    }
}
