// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { frameEventAffects } from "./_frame_events.js";

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
        this.handleReset = this.handleReset.bind(this);
        this.connected = false;
        this.form = null;
    }

    connect() {
        this.connected = true;
        this.syncForm();
        document.addEventListener("turbo:frame-render", this.refresh);
        document.addEventListener("turbo:render", this.refresh);
    }

    disconnect() {
        this.connected = false;
        this.form?.removeEventListener("reset", this.handleReset);
        this.form = null;
        document.removeEventListener("turbo:frame-render", this.refresh);
        document.removeEventListener("turbo:render", this.refresh);
    }

    checkboxAllTargetConnected(checkbox) {
        checkbox.addEventListener("change", this.toggle);

        this.syncForm();
        this.refresh();
    }

    checkboxTargetConnected(checkbox) {
        checkbox.addEventListener("change", this.refresh);

        this.syncForm();
        this.refresh();
    }

    checkboxAllTargetDisconnected(checkbox) {
        checkbox.removeEventListener("change", this.toggle);

        this.syncForm();
        this.refresh();
    }

    checkboxTargetDisconnected(checkbox) {
        checkbox.removeEventListener("change", this.refresh);

        this.syncForm();
        this.refresh();
    }

    disableIndeterminateValueChanged() {
        this.refresh();
    }

    handleReset(event) {
        const form = this.form;

        queueMicrotask(() => {
            if (event.defaultPrevented || !form || this.form !== form || !this.element.isConnected) return;

            this.refresh();
        });
    }

    syncForm() {
        if (!this.connected) return;

        const controls = [this.hasCheckboxAllTarget ? this.checkboxAllTarget : null, ...this.checkboxTargets];
        const form = this.element.closest("form") ?? controls.find((control) => control?.form)?.form ?? null;
        if (form === this.form) return;

        this.form?.removeEventListener("reset", this.handleReset);
        this.form = form;
        this.form?.addEventListener("reset", this.handleReset);
    }

    toggle(e) {
        e.preventDefault();

        this.checkboxTargets.forEach((checkbox) => {
            checkbox.checked = e.target.checked;
            this.triggerInputEvent(checkbox);
        });
    }

    refresh(event) {
        if (event?.type === "turbo:frame-render" && !frameEventAffects(this.element, event)) return;
        if (!this.hasCheckboxAllTarget) return;

        const checkboxesCount = this.checkboxTargets.length;
        const checkboxesCheckedCount = this.checked.length;
        const allChecked = checkboxesCount > 0 && checkboxesCheckedCount === checkboxesCount;
        const someChecked = checkboxesCheckedCount > 0 && checkboxesCheckedCount < checkboxesCount;

        this.checkboxAllTarget.checked = allChecked;
        this.checkboxAllTarget.indeterminate = !this.disableIndeterminateValue && someChecked;
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
