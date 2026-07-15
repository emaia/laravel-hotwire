// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        indeterminate: {
            type: Boolean,
            default: false,
        },
    };

    initialize() {
        this.sync = this.sync.bind(this);
    }

    connect() {
        this.sync();
        document.addEventListener("turbo:render", this.sync);
    }

    disconnect() {
        document.removeEventListener("turbo:render", this.sync);
    }

    indeterminateValueChanged() {
        this.sync();
    }

    sync() {
        this.element.indeterminate = this.indeterminateValue;
    }
}
