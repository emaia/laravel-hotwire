// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["button", "source"];
    static values = {
        successContent: String,
        successDuration: {
            type: Number,
            default: 2000,
        },
    };

    connect() {
        if (!this.hasButtonTarget) return;

        this.originalContent = this.buttonTarget.innerHTML;
    }

    copy(event) {
        event.preventDefault();

        const text = this.sourceTarget.innerHTML || this.sourceTarget.value;

        navigator.clipboard.writeText(text).then(() => this.copied());
    }

    copied() {
        if (!this.hasButtonTarget) return;

        if (this.timeout) {
            this.clearFeedbackTimer(this.timeout);
        }

        this.buttonTarget.innerHTML = this.successContentValue;

        this.timeout = this.setFeedbackTimer(() => {
            this.buttonTarget.innerHTML = this.originalContent;
        }, this.successDurationValue);
    }

    setFeedbackTimer(callback, duration) {
        return setTimeout(callback, duration);
    }

    clearFeedbackTimer(timeoutId) {
        clearTimeout(timeoutId);
    }
}
