// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { emitToast } from "./_toaster.js";

export default class extends Controller {
    static values = {
        message: {
            type: String,
            required: true,
        },
        description: {
            type: String,
            default: null,
        },
        type: {
            type: String,
            default: "default",
        },
        position: {
            type: String,
            default: "",
        },
        className: {
            type: String,
            default: "",
        },
    };

    connect() {
        const payload = {
            message: this.messageValue,
            type: this.typeValue,
        };

        if (this.descriptionValue) {
            payload.description = this.descriptionValue;
        }

        if (this.positionValue) {
            payload.position = this.positionValue;
        }

        if (this.hasClassNameValue && this.classNameValue) {
            payload.className = this.classNameValue;
        }

        this.emit(payload);
        this.element.remove();
    }

    emit(payload) {
        return emitToast(payload);
    }
}
