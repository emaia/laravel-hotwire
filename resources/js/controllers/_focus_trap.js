// @hotwire-package
const FOCUSABLE_SELECTOR =
    'a[href], area[href], input:not([disabled]):not([type="hidden"]), ' +
    "select:not([disabled]), textarea:not([disabled]), " +
    'button:not([disabled]), details:not([aria-disabled="true"]):not([data-disabled="true"]) > summary:not([aria-disabled="true"]):not([tabindex="-1"]), ' +
    '[tabindex]:not([tabindex="-1"])';

export class FocusTrap {
    constructor(container) {
        this.container = container;
        this.active = false;
        this.handleKey = this.handleKey.bind(this);
    }

    activate() {
        if (this.active) return;

        this.active = true;

        if (!this.container.hidden) {
            const active = document.activeElement;
            const alreadyInside =
                active &&
                this.container.contains(active) &&
                active.matches(FOCUSABLE_SELECTOR);

            if (!alreadyInside) {
                const focusable = this.container.querySelectorAll(FOCUSABLE_SELECTOR);
                focusWithVisibleRing(focusable[0]);
            }
        }

        document.addEventListener("keydown", this.handleKey);
    }

    deactivate() {
        if (!this.active) return;

        this.active = false;
        document.removeEventListener("keydown", this.handleKey);
    }

    handleKey(event) {
        if (event.key !== "Tab") return;
        if (!this.active) return;
        if (this.container.hidden) return;

        const focusable = this.container.querySelectorAll(FOCUSABLE_SELECTOR);
        if (focusable.length === 0) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;

        if (!active || !this.container.contains(active)) {
            event.preventDefault();
            focusWithVisibleRing(event.shiftKey ? last : first);
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            focusWithVisibleRing(first);
        } else if (event.shiftKey && active === first) {
            event.preventDefault();
            focusWithVisibleRing(last);
        }
    }
}

function focusWithVisibleRing(element) {
    if (!element) return;

    try {
        element.focus({ focusVisible: true });
    } catch (_error) {
        element.focus();
    }
}
