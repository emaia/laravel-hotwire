// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["item"];

    static values = {
        type: {
            type: String,
            default: "multiple",
        },
    };

    connect() {
        this.sync();
    }

    sync(event) {
        const changedItem = event?.target;

        if (event && !this.itemTargets.includes(changedItem)) return;

        if (this.typeValue === "single") {
            this.syncSingle(changedItem);
        }

        this.syncInputs();
    }

    syncSingle(changedItem) {
        const pressedItems = this.itemTargets.filter((item) => this.isPressed(item));
        const keepItem = changedItem && this.itemTargets.includes(changedItem) && this.isPressed(changedItem)
            ? changedItem
            : pressedItems[0] ?? null;

        for (const item of this.itemTargets) {
            if (item !== keepItem) {
                this.setPressed(item, false);
            }
        }
    }

    syncInputs() {
        for (const item of this.itemTargets) {
            const input = this.inputFor(item);

            if (!input) continue;

            input.value = item.dataset.toggleValueValue || "on";
            input.disabled = this.isDisabled(item) || !this.isPressed(item);
        }
    }

    setPressed(item, pressed) {
        item.setAttribute("aria-pressed", pressed ? "true" : "false");
        item.dataset.state = pressed ? "on" : "off";
        item.dataset.togglePressedValue = pressed ? "true" : "false";
    }

    inputFor(item) {
        const inputId = item.dataset.toggleInputIdValue;

        if (!inputId) return null;

        return item.ownerDocument.getElementById(inputId);
    }

    isPressed(item) {
        return item.getAttribute("aria-pressed") === "true" || item.dataset.togglePressedValue === "true";
    }

    isDisabled(item) {
        return item.disabled || item.getAttribute("aria-disabled") === "true" || item.dataset.disabled === "true";
    }
}
