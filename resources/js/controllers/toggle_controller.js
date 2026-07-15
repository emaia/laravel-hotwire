// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        pressed: {
            type: Boolean,
            default: false,
        },
        value: {
            type: String,
            default: "on",
        },
        inputId: String,
    };

    connect() {
        this.sync();
    }

    pressedValueChanged() {
        this.sync();
    }

    toggle() {
        if (this.isDisabled) return;

        this.pressedValue = !this.pressedValue;
        this.sync();
        this.dispatchChange();
    }

    sync() {
        const pressed = this.pressedValue;

        this.element.setAttribute("aria-pressed", pressed ? "true" : "false");
        this.element.dataset.state = pressed ? "on" : "off";

        if (!this.inputElement) return;

        this.inputElement.value = this.valueValue;
        this.inputElement.disabled = this.isDisabled || !pressed;
    }

    dispatchChange() {
        this.element.dispatchEvent(
            new CustomEvent("change", {
                bubbles: true,
                detail: {
                    pressed: this.pressedValue,
                    value: this.valueValue,
                },
            }),
        );
    }

    get inputElement() {
        if (!this.hasInputIdValue) return null;

        return this.element.ownerDocument.getElementById(this.inputIdValue);
    }

    get isDisabled() {
        return this.element.disabled || this.element.getAttribute("aria-disabled") === "true" || this.element.dataset.disabled === "true";
    }
}
