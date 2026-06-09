import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["trigger", "content"];

    static values = {
        open: { type: Boolean, default: false },
    };

    connect() {
        this.sync();
    }

    toggle() {
        if (!this.hasContentTarget) return;
        this.openValue ? this.close() : this.open();
    }

    open() {
        if (!this.hasContentTarget) return;
        if (this.openValue) return;

        this.openValue = true;
        this.sync();
        this.dispatch("change", { detail: { open: true } });
    }

    close() {
        if (!this.hasContentTarget) return;
        if (!this.openValue) return;

        this.openValue = false;
        this.sync();
        this.dispatch("change", { detail: { open: false } });
    }

    sync() {
        if (this.hasContentTarget) {
            this.contentTarget.hidden = !this.openValue;
        }

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute("aria-expanded", this.openValue ? "true" : "false");
        }
    }
}
