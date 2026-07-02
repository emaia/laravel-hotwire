// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        delay: {
            type: Number,
            default: 300,
        },
    };

    disconnect() {
        this.clearSubmitTimer(this.timeout);
    }

    submit() {
        this.clearSubmitTimer(this.timeout);
        this.element.requestSubmit();
    }

    debouncedSubmit() {
        if (this.delayValue <= 0) {
            this.submit();
            return;
        }

        this.clearSubmitTimer(this.timeout);
        this.timeout = this.setSubmitTimer(() => this.element.requestSubmit(), this.delayValue);
    }

    setSubmitTimer(callback, delay) {
        return setTimeout(callback, delay);
    }

    clearSubmitTimer(timeoutId) {
        clearTimeout(timeoutId);
    }
}
