import { Controller } from "@hotwired/stimulus";

const FOCUSABLE_SELECTOR = "input, select, textarea, button";

export default class extends Controller {
    static targets = ["field"];

    static values = {
        strategy: { type: String, default: "autofocus-attribute" },
        scrollIntoView: { type: Boolean, default: false },
    };

    connect() {
        this.handleFrameLoad = this.handleFrameLoad.bind(this);

        this.focusFirst();

        document.addEventListener("turbo:frame-load", this.handleFrameLoad);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-load", this.handleFrameLoad);
    }

    handleFrameLoad() {
        this.focusFirst();
    }

    focusFirst() {
        if (!this.shouldFocus()) return;

        const element = this.resolveTarget();
        if (!element) return;

        element.focus({ preventScroll: !this.scrollIntoViewValue });
    }

    shouldFocus() {
        const active = document.activeElement;
        if (!active || active === document.body) return true;
        return !this.element.contains(active);
    }

    resolveTarget() {
        switch (this.strategyValue) {
            case "target":
                return this.hasFieldTarget && isFocusable(this.fieldTarget) ? this.fieldTarget : null;
            case "first-focusable":
                return this.findFirst(FOCUSABLE_SELECTOR);
            case "autofocus-attribute":
            default:
                return this.findFirst("[autofocus]");
        }
    }

    findFirst(selector) {
        const candidates = this.element.querySelectorAll(selector);
        for (const candidate of candidates) {
            if (isFocusable(candidate)) return candidate;
        }
        return null;
    }
}

function isFocusable(element) {
    if (element.disabled) return false;
    if (element.type === "hidden") return false;
    if (element.getAttribute("tabindex") === "-1") return false;
    if (element.closest("[hidden]")) return false;
    if (element.closest('[aria-hidden="true"]')) return false;
    return true;
}
