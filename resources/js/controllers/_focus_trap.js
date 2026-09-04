// @hotwire-package
import { isComposing } from "./_composition.js";

const FOCUSABLE_SELECTOR =
    'a[href], area[href], input:not([disabled]):not([type="hidden"]), ' +
    "select:not([disabled]), textarea:not([disabled]), " +
    'button:not([disabled]), details:not([aria-disabled="true"]):not([data-disabled="true"]) > summary:not([aria-disabled="true"]):not([tabindex="-1"]), ' +
    '[tabindex]:not([tabindex="-1"])';
const FOCUS_CANDIDATE_SELECTOR = `${FOCUSABLE_SELECTOR}, [contenteditable]`;
const INITIAL_FOCUS_STRATEGIES = new Set(["auto", "dialog", "first-focusable", "none"]);
const DIALOG_SELECTOR = '[role="dialog"], [role="alertdialog"]';
const DOCUMENT_POSITION_PRECEDING = 2;
const DOCUMENT_POSITION_FOLLOWING = 4;

export class FocusTrap {
    constructor(container, { initialFocus = "first-focusable", fallback = null } = {}) {
        this.container = container;
        this.initialFocus = initialFocus;
        this.fallback = fallback;
        this.active = false;
        this.handleKey = this.handleKey.bind(this);
    }

    activate({ moveFocus = true } = {}) {
        if (this.active) return;

        this.active = true;

        if (!this.container.hidden && moveFocus) {
            this.#applyInitialFocus();
        }

        document.addEventListener("keydown", this.handleKey);
    }

    deactivate() {
        if (!this.active) return;

        this.active = false;
        document.removeEventListener("keydown", this.handleKey);
    }

    handleKey(event) {
        if (isComposing(event)) return;
        if (event.key !== "Tab") return;
        if (!this.active) return;
        if (this.container.hidden) return;

        const focusable = getFocusableElements(this.container);
        if (focusable.length === 0) {
            event.preventDefault();
            focusFirst([getDialogElement(this.container)]);

            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;
        const dialog = getDialogElement(this.container);

        if (!active || active === dialog || !this.container.contains(active)) {
            event.preventDefault();
            focusElement(event.shiftKey ? last : first);
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            focusElement(first);
        } else if (event.shiftKey && active === first) {
            event.preventDefault();
            focusElement(last);
        } else if (!focusable.includes(active) && !hasFocusableInDirection(active, focusable, event.shiftKey)) {
            event.preventDefault();
            focusElement(event.shiftKey ? last : first);
        }
    }

    #applyInitialFocus() {
        const configuredStrategy = typeof this.initialFocus === "function" ? this.initialFocus() : this.initialFocus;
        const strategy = INITIAL_FOCUS_STRATEGIES.has(configuredStrategy) ? configuredStrategy : "auto";

        if (strategy === "none") return;

        const dialog = getDialogElement(this.container);
        const focusable = getFocusableElements(this.container);

        if (strategy === "first-focusable") {
            focusFirst([...focusable, dialog]);

            return;
        }

        if (strategy === "dialog") {
            focusFirst([dialog, ...focusable]);

            return;
        }

        const autofocus = [...this.container.querySelectorAll("[autofocus]")];
        const fallback = typeof this.fallback === "function" ? this.fallback() : this.fallback;
        focusFirst([...autofocus, fallback, dialog, ...focusable]);
    }
}

function getFocusableElements(container) {
    return [...container.querySelectorAll(FOCUS_CANDIDATE_SELECTOR)].filter(
        (element) => (element.tabIndex >= 0 || isEditingHost(element)) && isFocusableElement(element),
    );
}

function getDialogElement(container) {
    return container.matches(DIALOG_SELECTOR) ? container : container.querySelector(DIALOG_SELECTOR);
}

function isFocusableElement(element) {
    if (!element || typeof element.focus !== "function") return false;
    if (element.closest("[hidden], [inert]") || isDisabledFormControl(element)) return false;
    if (element.matches('input[type="hidden"]')) return false;
    if (
        !element.hasAttribute("tabindex") &&
        !element.matches(FOCUSABLE_SELECTOR) &&
        !element.isContentEditable
    ) {
        return false;
    }
    if (
        typeof element.checkVisibility === "function" &&
        !element.checkVisibility({
            checkOpacity: false,
            checkVisibilityCSS: true,
            visibilityProperty: true,
        })
    )
        return false;

    return true;
}

function isDisabledFormControl(element) {
    if (element.matches(":disabled") || element.disabled === true) return true;
    if (!element.matches("button, input, select, textarea")) return false;

    for (let ancestor = element.parentElement; ancestor; ancestor = ancestor.parentElement) {
        if (!ancestor.matches("fieldset[disabled]")) continue;

        const firstLegend = [...ancestor.children].find((child) => child.matches("legend"));
        if (!firstLegend?.contains(element)) return true;
    }

    return false;
}

function isEditingHost(element) {
    return element.isContentEditable && !element.parentElement?.isContentEditable;
}

function hasFocusableInDirection(active, focusable, backwards) {
    const direction = backwards ? DOCUMENT_POSITION_PRECEDING : DOCUMENT_POSITION_FOLLOWING;

    return focusable.some((element) => active.compareDocumentPosition(element) & direction);
}

function isEligibleInitialFocusTarget(element) {
    if (!isFocusableElement(element)) return false;

    return !element.closest('[aria-disabled="true"], [data-disabled="true"]');
}

function focusFirst(candidates) {
    for (const element of new Set(candidates)) {
        if (isEligibleInitialFocusTarget(element) && focusElement(element, true)) return true;
    }

    return false;
}

function focusElement(element, preventScroll = false) {
    if (!element) return false;

    const options = preventScroll ? { focusVisible: true, preventScroll: true } : { focusVisible: true };
    const fallbackOptions = preventScroll ? { preventScroll: true } : null;

    try {
        element.focus(options);
    } catch (_error) {
        fallbackOptions ? element.focus(fallbackOptions) : element.focus();
    }

    return document.activeElement === element;
}
