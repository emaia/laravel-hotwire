import { Controller } from "@hotwired/stimulus";

const PREV_DISABLED = "data-conditional-fields-prev-disabled";

export default class extends Controller {
    static targets = ["dependent"];

    connect() {
        this.handleTriggerEvent = this.handleTriggerEvent.bind(this);

        this.rules = this.dependentTargets.map((dependent) => ({
            element: dependent,
            conditions: this.parseConditions(dependent),
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
            this.applyVisibility(rule.element, this.matchesAll(rule.conditions));
        }
    }

    parseConditions(dependent) {
        const conditions = [];
        for (const attr of dependent.attributes) {
            if (!attr.name.startsWith("data-when-")) continue;

            const name = attr.name.slice("data-when-".length);
            const expected = attr.value.split("|").map((v) => v.trim()).filter(Boolean);
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

        const allCheckboxes = fields.every((f) => f.type === "checkbox");

        for (const token of expected) {
            if (token === ":checked" && allCheckboxes) {
                if (fields.some((f) => f.checked)) return true;
                continue;
            }
            if (token === ":unchecked" && allCheckboxes) {
                if (fields.every((f) => !f.checked)) return true;
                continue;
            }

            if (this.currentValues(fields).includes(token)) return true;
        }
        return false;
    }

    currentValues(fields) {
        if (fields.length === 1 && fields[0].type !== "checkbox" && fields[0].type !== "radio") {
            return [fields[0].value];
        }

        if (fields.every((f) => f.type === "radio")) {
            const checked = fields.find((f) => f.checked);
            return checked ? [checked.value] : [];
        }

        return fields.filter((f) => f.checked).map((f) => f.value);
    }

    findFields(name) {
        const variants = [...new Set([
            name,
            name.replace(/-/g, "_"),
            name.replace(/_/g, "-"),
        ])];

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
            } else {
                field.disabled = false;
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
