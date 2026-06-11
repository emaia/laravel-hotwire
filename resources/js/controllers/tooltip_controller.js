import { Controller } from "@hotwired/stimulus";
import tippy from "tippy.js"; // https://atomiks.github.io/tippyjs
import "tippy.js/dist/tippy.css";

export default class extends Controller {
    static values = {
        content: {
            type: String,
            default: "Tooltip",
        },
        placement: {
            type: String,
            default: "top",
        },
    };

    connect() {
        this.tippy?.destroy();
        this.tippy = tippy(this.element, {
            content: this.contentValue,
            placement: this.placementValue,
            allowHTML: true,
        });
    }

    disconnect() {
        if (this.tippy) {
            this.tippy.destroy();
        }
    }
}
