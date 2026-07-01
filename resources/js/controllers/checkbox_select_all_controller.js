// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["checkboxAll", "checkbox"];

    static values = {
        disableIndeterminate: {
            type: Boolean,
            default: false,
        },
    };

    initialize() {
        this.toggle = this.toggle.bind(this);
        this.refresh = this.refresh.bind(this);
    }

    connect() {
        document.addEventListener("turbo:render", this.refresh);
    }

    disconnect() {
        document.removeEventListener("turbo:render", this.refresh);
    }

    checkboxAllTargetConnected(checkbox) {
        checkbox.addEventListener("change", this.toggle);

        this.refresh();
    }

    checkboxTargetConnected(checkbox) {
        checkbox.addEventListener("change", this.refresh);

        this.refresh();
    }

    checkboxAllTargetDisconnected(checkbox) {
        checkbox.removeEventListener("change", this.toggle);

        this.refresh();
    }

    checkboxTargetDisconnected(checkbox) {
        checkbox.removeEventListener("change", this.refresh);

        this.refresh();
    }

    toggle(e) {
        e.preventDefault();

        this.checkboxTargets.forEach((checkbox) => {
            checkbox.checked = e.target.checked;
            this.triggerInputEvent(checkbox);
        });
    }

    refresh() {
        const checkboxesCount = this.checkboxTargets.length;
        const checkboxesCheckedCount = this.checked.length;

        if (this.disableIndeterminateValue) {
            this.checkboxAllTarget.checked = checkboxesCheckedCount === checkboxesCount;
        } else {
            this.checkboxAllTarget.checked = checkboxesCount > 0 && checkboxesCheckedCount === checkboxesCount;
            this.checkboxAllTarget.indeterminate =
                checkboxesCheckedCount > 0 && checkboxesCheckedCount < checkboxesCount;
        }
    }

    triggerInputEvent(checkbox) {
        const event = new Event("input", { bubbles: false, cancelable: true });

        checkbox.dispatchEvent(event);
    }

    get checked() {
        return this.checkboxTargets.filter((checkbox) => checkbox.checked);
    }

    get unchecked() {
        return this.checkboxTargets.filter((checkbox) => !checkbox.checked);
    }
}
