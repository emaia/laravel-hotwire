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
    };

    connect() {
        if (this.typeValue === "default") {
            toast(this.messageValue, {
                description: this.descriptionValue,
            });
        } else {
            toast[this.typeValue](this.messageValue, {
                description: this.descriptionValue,
            });
        }

        this.element.remove();
    }
}
