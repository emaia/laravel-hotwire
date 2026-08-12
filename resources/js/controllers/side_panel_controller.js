// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

export default class extends Controller {
    static targets = ["panel", "trigger"];

    static values = {
        open: { type: Boolean, default: true },
        persist: { type: Boolean, default: true },
        name: String,
        cookieName: String,
    };

    connected = false;
    addedRootTabindex = false;

    connect() {
        this.connected = true;
        this.sync();
    }

    disconnect() {
        this.connected = false;
        if (this.addedRootTabindex) this.element.removeAttribute("tabindex");
        this.addedRootTabindex = false;
    }

    toggle() {
        this.setOpen(!this.openValue);
    }

    open() {
        this.setOpen(true);
    }

    close() {
        this.setOpen(false);
    }

    preserveStateForRender(event) {
        const nextRoot = this.nextRootForRender(event.detail?.newBody);
        if (!nextRoot) return;

        this.applyStateTo(nextRoot, this.openValue, { writeValue: true });
    }

    openValueChanged(value) {
        if (!this.connected) return;
        if (this.element.dataset.state === (value ? "expanded" : "collapsed")) return;

        if (!value) this.moveFocusOutOfPanel();
        this.sync();
    }

    setOpen(open) {
        if (this.openValue === open) return;

        if (!open) this.moveFocusOutOfPanel();
        this.openValue = open;
        this.sync();
        // Stimulus coalesces same-turn value reversals, so reconcile after its observer reads the final attribute.
        queueMicrotask(() => {
            if (this.connected && this.element.dataset.state !== this.state) {
                if (!this.openValue) this.moveFocusOutOfPanel();
                this.sync();
            }
        });

        if (this.persistValue && this.hasCookieNameValue) {
            document.cookie = `${this.cookieNameValue}=${open}; path=/; max-age=${COOKIE_MAX_AGE}; samesite=lax`;
        }

        this.dispatch("change", { detail: { open, state: this.state } });
    }

    sync() {
        this.applyStateTo(this.element, this.openValue);
    }

    applyStateTo(root, open, { writeValue = false } = {}) {
        root.dataset.state = open ? "expanded" : "collapsed";
        if (writeValue) root.setAttribute(`data-${this.identifier}-open-value`, open ? "true" : "false");
        this.targetsFor(root, "panel").forEach((panel) => panel.toggleAttribute("inert", !open));
        this.targetsFor(root, "trigger").forEach((trigger) =>
            trigger.setAttribute("aria-expanded", open ? "true" : "false"),
        );
    }

    moveFocusOutOfPanel() {
        if (!this.panelTargets.some((panel) => panel.contains(document.activeElement))) return;

        const destination = this.triggerTargets[0] ?? this.element;
        if (destination === this.element && !destination.hasAttribute("tabindex")) {
            destination.setAttribute("tabindex", "-1");
            this.addedRootTabindex = true;
        }
        destination.focus();
    }

    targetsFor(root, target) {
        return Array.from(root.querySelectorAll(`[data-${this.identifier}-target~="${target}"]`)).filter(
            (element) => element.closest(`[data-controller~="${this.identifier}"]`) === root,
        );
    }

    nextRootForRender(newBody) {
        if (!newBody) return null;

        const selector = `[data-controller~="${this.identifier}"]`;
        const nextRoots = Array.from(newBody.querySelectorAll(selector));

        if (this.hasNameValue) {
            const matchingName = nextRoots.find(
                (root) => root.getAttribute(`data-${this.identifier}-name-value`) === this.nameValue,
            );
            if (matchingName) return matchingName;
        }

        if (this.element.id) {
            const matchingId = nextRoots.find((root) => root.id === this.element.id);
            if (matchingId) return matchingId;
        }

        const currentRoots = Array.from(document.querySelectorAll(selector));
        return nextRoots[currentRoots.indexOf(this.element)] ?? null;
    }

    get state() {
        return this.openValue ? "expanded" : "collapsed";
    }
}
