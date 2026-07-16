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

    debouncedSubmit(event) {
        const delay = this.submitDelay(event);

        if (delay <= 0) {
            this.submit();
            return;
        }

        this.clearSubmitTimer(this.timeout);
        this.timeout = this.setSubmitTimer(() => this.element.requestSubmit(), delay);
    }

    submitDelay(event) {
        const param = event?.params?.delay;
        if (param === undefined || param === null || param === "") return this.delayValue;

        const delay = Number(param);

        return Number.isNaN(delay) ? this.delayValue : delay;
    }

    setSubmitTimer(callback, delay) {
        return setTimeout(callback, delay);
    }

    clearSubmitTimer(timeoutId) {
        clearTimeout(timeoutId);
    }
}
