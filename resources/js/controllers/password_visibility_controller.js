// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "button"];

    static values = {
        showLabel: { type: String, default: "Show password" },
        hideLabel: { type: String, default: "Hide password" },
    };

    connect() {
        this.hide();
    }

    toggle() {
        this.isVisible() ? this.hide() : this.show();
    }

    show() {
        if (!this.hasInputTarget || this.isVisible()) return;

        this.inputTarget.type = "text";
        this.syncButton(true);
        this.dispatch("change", { detail: { visible: true } });
    }

    hide() {
        if (!this.hasInputTarget) return;

        if (!this.isVisible()) {
            this.syncButton(false);
            return;
        }

        this.inputTarget.type = "password";
        this.syncButton(false);
        this.dispatch("change", { detail: { visible: false } });
    }

    isVisible() {
        return this.hasInputTarget && this.inputTarget.type === "text";
    }

    syncButton(visible) {
        if (!this.hasButtonTarget) return;

        this.buttonTarget.setAttribute("aria-pressed", visible ? "true" : "false");
        this.buttonTarget.setAttribute("aria-label", visible ? this.hideLabelValue : this.showLabelValue);
    }
}
