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
        clearTimeout(this.timeout);
    }

    submit() {
        clearTimeout(this.timeout);
        this.element.requestSubmit();
    }

    debouncedSubmit() {
        if (this.delayValue <= 0) {
            this.submit();
            return;
        }

        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.element.requestSubmit(), this.delayValue);
    }
}
