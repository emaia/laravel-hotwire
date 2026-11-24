// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const PREV_DISABLED = "data-conditional-fields-prev-disabled";

export default class extends Controller {
    static targets = ["dependent"];

    connect() {
        this.handleTriggerEvent = this.handleTriggerEvent.bind(this);

        this.rules = this.dependentTargets.map((dependent) => ({
            element: dependent,
            conditions: this.parseConditions(dependent),
            unlessConditions: this.parseConditions(dependent, "data-unless-"),
        }));

        this.element.addEventListener("change", this.handleTriggerEvent);
        this.element.addEventListener("input", this.handleTriggerEvent);

        this.evaluateAll();
    }

    disconnect() {
        this.element.removeEventListener("change", this.handleTriggerEvent);
        this.element.removeEventListener("input", this.handleTriggerEvent);
    }

    handleTriggerEvent() {
        this.evaluateAll();
    }

    evaluateAll() {
        for (const rule of this.rules) {
            const hasUnlessTriggers = rule.unlessConditions.every(({ name }) => this.findFields(name).length > 0);
            const visible =
                this.matchesAll(rule.conditions) &&
                hasUnlessTriggers &&
                (rule.unlessConditions.length === 0 || !this.matchesAll(rule.unlessConditions));

            this.applyVisibility(rule.element, visible);
        }
    }

    parseConditions(dependent, prefix = "data-when-") {
        const conditions = [];
        for (const attr of dependent.attributes) {
            if (!attr.name.startsWith(prefix)) continue;

            const name = attr.name.slice(prefix.length);
            const expected = attr.value
                .split("|")
                .map((v) => v.trim())
                .filter(Boolean);
            conditions.push({ name, expected });
        }
        return conditions;
    }

    matchesAll(conditions) {
        return conditions.every(({ name, expected }) => this.matchesField(name, expected));
    }

    matchesField(name, expected) {
        const fields = this.findFields(name);
        if (fields.length === 0) return false;

        const checkboxes = this.checkboxesOf(fields);
        const values = this.currentValues(fields, checkboxes);

        for (const token of expected) {
            if (token === ":checked" && checkboxes) {
                if (checkboxes.some((f) => f.checked)) return true;
                continue;
            }
            if (token === ":unchecked" && checkboxes) {
                if (checkboxes.every((f) => !f.checked)) return true;
                continue;
            }

            if (values.includes(token)) return true;
        }
        return false;
    }

    // Checkbox and Switch components pair the checkbox with a same-named hidden input carrying `unchecked-value`.
    checkboxesOf(fields) {
        const checkboxes = fields.filter((f) => f.type === "checkbox");
        if (checkboxes.length === 0) return null;

        return fields.every((f) => f.type === "checkbox" || f.type === "hidden") ? checkboxes : null;
    }

    currentValues(fields, checkboxes) {
        if (checkboxes) {
            const checked = checkboxes.filter((f) => f.checked).map((f) => f.value);
            if (checked.length > 0) return checked;

            return fields.filter((f) => f.type === "hidden" && !f.disabled).map((f) => f.value);
        }

        if (fields.length === 1 && fields[0].tagName === "SELECT" && fields[0].multiple) {
            return Array.from(fields[0].options)
                .filter((option) => option.selected)
                .map((option) => option.value);
        }

        if (fields.length === 1 && fields[0].type !== "radio") {
            return [fields[0].value];
        }

        if (fields.every((f) => f.type === "radio")) {
            const checked = fields.find((f) => f.checked);
            return checked ? [checked.value] : [];
        }

        return fields.filter((f) => f.checked).map((f) => f.value);
    }

    findFields(name) {
        const variants = [...new Set([name, name.replace(/-/g, "_"), name.replace(/_/g, "-")])];

        for (const variant of variants) {
            const matches = [
                ...this.element.querySelectorAll(`[name="${cssEscape(variant)}"]`),
                ...this.element.querySelectorAll(`[name="${cssEscape(variant)}[]"]`),
            ];
            if (matches.length > 0) return matches;
        }
        return [];
    }

    applyVisibility(element, visible) {
        if (visible) {
            this.show(element);
        } else {
            this.hide(element);
        }
    }

    show(element) {
        element.hidden = false;

        if (element.tagName === "FIELDSET") {
            element.disabled = false;
            return;
        }

        for (const field of element.querySelectorAll("input, select, textarea, button, fieldset")) {
            const prev = field.getAttribute(PREV_DISABLED);
            if (prev !== null) {
                field.disabled = prev === "true";
                field.removeAttribute(PREV_DISABLED);
            }
        }
    }

    hide(element) {
        element.hidden = true;

        if (element.tagName === "FIELDSET") {
            element.disabled = true;
            return;
        }

        for (const field of element.querySelectorAll("input, select, textarea, button, fieldset")) {
            if (!field.hasAttribute(PREV_DISABLED)) {
                field.setAttribute(PREV_DISABLED, String(field.disabled));
            }
            field.disabled = true;
        }
    }
}

function cssEscape(value) {
    if (typeof CSS !== "undefined" && typeof CSS.escape === "function") {
        return CSS.escape(value);
    }
    return value.replace(/[^a-zA-Z0-9_-]/g, (c) => `\\${c}`);
}
