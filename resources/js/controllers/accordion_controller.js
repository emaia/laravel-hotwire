// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["item"];

    static values = {
        type: { type: String, default: "single" },
        value: { type: String, default: "" },
    };

    initialize() {
        this.toggleItem = this.toggleItem.bind(this);
        this.preventDisabledToggle = this.preventDisabledToggle.bind(this);
    }

    connect() {
        this.element.addEventListener("click", this.preventDisabledToggle);
        this.element.addEventListener("keydown", this.preventDisabledToggle);
        this.applyInitialValue();
    }

    disconnect() {
        this.element.removeEventListener("click", this.preventDisabledToggle);
        this.element.removeEventListener("keydown", this.preventDisabledToggle);
    }

    itemTargetConnected(item) {
        item.addEventListener("toggle", this.toggleItem);
    }

    itemTargetDisconnected(item) {
        item.removeEventListener("toggle", this.toggleItem);
    }

    toggleItem(event) {
        const item = event.target;
        if (!this.itemTargets.includes(item)) return;

        if (this.isDisabled(item)) {
            item.open = false;
            return;
        }

        if (this.single && item.open) {
            this.itemTargets.forEach((current) => {
                if (current !== item) current.open = false;
            });
        }

        this.dispatch("change", {
            detail: {
                value: item.dataset.value ?? null,
                open: item.open,
                item,
            },
        });
    }

    preventDisabledToggle(event) {
        if (event.type === "keydown" && event.key !== "Enter" && event.key !== " ") return;

        const summary = event.target.closest("summary");
        const item = summary?.closest("details");
        if (!item || !this.itemTargets.includes(item) || !this.isDisabled(item)) return;

        event.preventDefault();
    }

    applyInitialValue() {
        const values = this.values;
        if (values.length === 0) return;

        this.itemTargets.forEach((item) => {
            item.open = !this.isDisabled(item) && values.includes(item.dataset.value);
        });

        if (this.single) {
            const open = this.itemTargets.filter((item) => item.open);
            open.slice(1).forEach((item) => (item.open = false));
        }
    }

    isDisabled(item) {
        return item.dataset.disabled === "true" || item.getAttribute("aria-disabled") === "true";
    }

    get single() {
        return this.typeValue !== "multiple";
    }

    get values() {
        if (!this.valueValue) return [];

        const value = this.valueValue.trim();
        if (value === "") return [];

        if (value.startsWith("[")) {
            try {
                const values = JSON.parse(value);

                return Array.isArray(values) ? values.map(String) : [];
            } catch {
                return [];
            }
        }

        return [value];
    }
}
