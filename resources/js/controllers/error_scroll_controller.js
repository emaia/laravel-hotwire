// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { frameEventAffects } from "./_frame_events.js";

export default class extends Controller {
    static values = {
        behavior: { type: String, default: "smooth" },
        block: { type: String, default: "center" },
        selector: { type: String, default: "[aria-invalid]" },
    };

    connect() {
        this.scrollToError = this.scrollToError.bind(this);
        document.addEventListener("turbo:frame-render", this.scrollToError);
        document.addEventListener("turbo:render", this.scrollToError);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-render", this.scrollToError);
        document.removeEventListener("turbo:render", this.scrollToError);
    }

    scrollToError(event) {
        if (event?.type?.includes("frame") && !frameEventAffects(this.element, event)) return;

        requestAnimationFrame(() => {
            const target = this.element.querySelector(this.selectorValue);
            if (target) {
                target.scrollIntoView({
                    behavior: this.behaviorValue,
                    block: this.blockValue,
                });
            }
        });
    }
}
