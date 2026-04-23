import { Controller } from "@hotwired/stimulus";

const STATE_TEXT = {
    idle: "",
    dirty: "Unsaved changes",
    saving: "Saving...",
    saved: "Saved",
    error: "Could not save",
};

export default class extends Controller {
    static targets = ["status", "submitter"];
    static classes = ["dirty", "saving", "saved", "error"];
    static values = {
        delay: { type: Number, default: 750 },
        changeDelay: { type: Number, default: 0 },
        statusDuration: { type: Number, default: 2000 },
    };

    initialize() {
        this.handleInput = this.handleInput.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.handleSubmitStart = this.handleSubmitStart.bind(this);
        this.handleSubmitEnd = this.handleSubmitEnd.bind(this);
    }

    connect() {
        this.timeout = null;
        this.statusTimeout = null;
        this.saving = false;
        this.saveQueued = false;
        this.state = null;
        this.submittedSignature = null;
        this.lastSavedSignature = this.signature;

        this.element.addEventListener("input", this.handleInput);
        this.element.addEventListener("change", this.handleChange);
        this.element.addEventListener("turbo:submit-start", this.handleSubmitStart);
        this.element.addEventListener("turbo:submit-end", this.handleSubmitEnd);

        this.setState("idle");
    }

    disconnect() {
        clearTimeout(this.timeout);
        clearTimeout(this.statusTimeout);

        this.element.removeEventListener("input", this.handleInput);
        this.element.removeEventListener("change", this.handleChange);
        this.element.removeEventListener("turbo:submit-start", this.handleSubmitStart);
        this.element.removeEventListener("turbo:submit-end", this.handleSubmitEnd);
    }

    save() {
        clearTimeout(this.timeout);

        if (!this.isDirty) {
            return;
        }

        if (this.saving) {
            this.saveQueued = true;
            return;
        }

        this.submittedSignature = this.signature;
        this.submit();
    }

    cancel() {
        clearTimeout(this.timeout);
        this.saveQueued = false;

        if (!this.saving) {
            this.setState(this.isDirty ? "dirty" : "idle");
        }
    }

    handleInput(event) {
        this.schedule(event, this.delayValue);
    }

    handleChange(event) {
        this.schedule(event, this.changeDelayValue);
    }

    handleSubmitStart() {
        this.saving = true;
        this.saveQueued = false;
        clearTimeout(this.timeout);

        if (this.setState("saving")) {
            this.dispatch("saving");
        }
    }

    handleSubmitEnd(event) {
        this.saving = false;

        if (event.detail.success) {
            this.lastSavedSignature = this.submittedSignature ?? this.signature;
            this.submittedSignature = null;

            if (this.saveQueued || this.isDirty) {
                this.saveQueued = false;
                this.markDirty();
                this.queue(this.delayValue);
                return;
            }

            if (this.setState("saved")) {
                this.dispatch("saved");
            }

            this.resetStatusLater();
            return;
        }

        if (this.setState("error")) {
            this.dispatch("error", { detail: { event } });
        }
    }

    schedule(event, delay) {
        if (this.shouldIgnore(event.target)) {
            return;
        }

        if (!this.isDirty) {
            clearTimeout(this.timeout);

            if (!this.saving) {
                this.setState("idle");
            }

            return;
        }

        this.markDirty();
        this.queue(delay);
    }

    queue(delay) {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.save(), delay);
    }

    markDirty() {
        if (this.setState("dirty")) {
            this.dispatch("dirty");
        }

        if (this.saving) {
            this.saveQueued = true;
        }
    }

    submit() {
        if (this.hasSubmitterTarget) {
            this.element.requestSubmit(this.submitterTarget);
            return;
        }

        this.element.requestSubmit();
    }

    setState(state) {
        if (this.state === state) {
            return false;
        }

        this.state = state;
        this.element.dataset.autoSaveState = state;
        this.updateClasses(state);
        this.updateStatus(state);

        return true;
    }

    updateClasses(state) {
        for (const name of ["dirty", "saving", "saved", "error"]) {
            const className = `${name}Class`;
            const hasClassName = `has${name[0].toUpperCase()}${name.slice(1)}Class`;

            if (this[hasClassName]) {
                this.element.classList.toggle(this[className], state === name);
            }
        }
    }

    updateStatus(state) {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = STATE_TEXT[state] ?? "";
    }

    resetStatusLater() {
        clearTimeout(this.statusTimeout);
        this.statusTimeout = setTimeout(() => {
            if (!this.isDirty && !this.saving) {
                this.setState("idle");
            }
        }, this.statusDurationValue);
    }

    shouldIgnore(element) {
        return element.closest("[data-auto-save-ignore]") !== null;
    }

    get isDirty() {
        return this.signature !== this.lastSavedSignature;
    }

    get signature() {
        const formData = new FormData(this.element);

        for (const element of this.element.querySelectorAll("[data-auto-save-ignore]")) {
            if (element.name) {
                formData.delete(element.name);
            }
        }

        return JSON.stringify([...formData.entries()]);
    }
}
