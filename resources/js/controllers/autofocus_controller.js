// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { frameEventAffects } from "./_frame_events.js";

const FOCUSABLE_SELECTOR = "input, select, textarea, button";

export default class extends Controller {
    static targets = ["field"];

    static values = {
        strategy: { type: String, default: "autofocus-attribute" },
        scrollIntoView: { type: Boolean, default: false },
    };

    pendingRichText = null;

    connect() {
        this.handleFrameLoad = this.handleFrameLoad.bind(this);
        this.handleRichTextReady = this.handleRichTextReady.bind(this);

        this.element.addEventListener("rich-text:ready", this.handleRichTextReady);

        this.focusFirst();

        document.addEventListener("turbo:frame-load", this.handleFrameLoad);
    }

    disconnect() {
        document.removeEventListener("turbo:frame-load", this.handleFrameLoad);
        this.element.removeEventListener("rich-text:ready", this.handleRichTextReady);
        this.pendingRichText = null;
    }

    handleFrameLoad(event) {
        if (!frameEventAffects(this.element, event)) return;

        this.focusFirst();
    }

    handleRichTextReady(event) {
        if (this.strategyValue !== "first-invalid" || event.target !== this.pendingRichText) return;
        if (this.element.tagName === "FORM" && event.target.closest("form") !== this.element) return;

        this.focusFirst();
    }

    focusFirst() {
        this.pendingRichText = null;
        if (!this.shouldFocus()) return;

        const element = this.resolveTarget();
        if (!element) return;

        element.focus({ preventScroll: !this.scrollIntoViewValue });
    }

    shouldFocus() {
        const active = document.activeElement;
        if (!active || active === document.body) return true;
        if (this.strategyValue === "first-invalid") {
            // Validation may recover focus lost by a Frame swap, not take it from another form or overlay.
            return active === this.element.closest('[role="dialog"], [role="alertdialog"]');
        }
        return !this.element.contains(active);
    }

    resolveTarget() {
        switch (this.strategyValue) {
            case "first-invalid":
                return this.firstInvalid();
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

    firstInvalid() {
        for (const element of this.element.querySelectorAll('[aria-invalid="true"]')) {
            if (this.element.tagName === "FORM" && "form" in element && element.form !== this.element) continue;
            if (element.matches('[data-slot="rich-text"]') && isVisibleErrorRegion(element)) {
                const editorHost = element.querySelector('[data-slot="rich-text-editor"]');
                // Lazy editor registration must not let a later invalid field win the initial focus.
                if (editorHost && !editorHost.querySelector(".ProseMirror")) {
                    this.pendingRichText = element;
                    return null;
                }
            }
            if (isInvalidFocusCandidate(element)) return element;
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

function isInvalidFocusCandidate(element) {
    if (!isFocusable(element) || !isVisibleErrorRegion(element)) return false;
    if (
        !element.matches(
            'input, select, textarea, button, a[href], [tabindex], [contenteditable="true"], [contenteditable=""]',
        )
    )
        return false;
    if (element.tabIndex < 0 && !element.isContentEditable) return false;

    return true;
}

function isVisibleErrorRegion(element) {
    if (
        element.matches(":disabled") ||
        element.closest('[hidden], [aria-hidden="true"], [inert], [aria-disabled="true"]')
    )
        return false;
    return (
        typeof element.checkVisibility !== "function" ||
        element.checkVisibility({
            checkOpacity: false,
            checkVisibilityCSS: true,
            visibilityProperty: true,
        })
    );
}
