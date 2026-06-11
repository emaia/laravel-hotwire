// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { toast } from "@emaia/sonner/vanilla";

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
    };

    connect() {
        const options = { description: this.descriptionValue };

        if (this.positionValue) {
            options.position = this.positionValue;
        }

        if (this.typeValue === "default") {
            toast(this.messageValue, options);
        } else {
            toast[this.typeValue](this.messageValue, options);
        }

        this.element.remove();
    }
}
