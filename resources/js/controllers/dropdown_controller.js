import { Controller } from "@hotwired/stimulus";

import { enter, leave } from "./_transition.js";

export default class extends Controller {
    static targets = ["trigger", "menu"];
    static classes = ["hidden"];
    static values = {
        open: { type: Boolean, default: false },
        closeOnSelect: { type: Boolean, default: true },
    };

    initialize() {
        this.onOutsideClick = this.onOutsideClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.onMenuClick = this.onMenuClick.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
    }

    connect() {
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onKeydown);
        this.menuTarget.addEventListener("click", this.onMenuClick);
        document.addEventListener("turbo:before-cache", this.closeForCache);

        // Reflect the initial state without animating.
        this.hiddenClassList.forEach((cls) => this.menuTarget.classList.toggle(cls, !this.openValue));
        this.syncAria();
    }

    disconnect() {
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onKeydown);
        this.menuTarget.removeEventListener("click", this.onMenuClick);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
    }

    toggle() {
        this.openValue ? this.close() : this.open();
    }

    open() {
        if (this.openValue) return;
        this.openValue = true;
        this.syncAria();
        enter(this.menuTarget, { hidden: this.hiddenClassList });
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;
        this.openValue = false;
        this.syncAria();
        leave(this.menuTarget, { hidden: this.hiddenClassList });
        if (focusTrigger && this.hasTriggerTarget) this.triggerTarget.focus();
    }

    onOutsideClick(event) {
        if (this.openValue && !this.element.contains(event.target)) this.close();
    }

    onKeydown(event) {
        if (this.openValue && event.key === "Escape") {
            event.preventDefault();
            this.close({ focusTrigger: true });
        }
    }

    onMenuClick(event) {
        if (this.closeOnSelectValue && event.target.closest("a, button")) this.close();
    }

    closeForCache() {
        // Snapshot must not be cached open; skip the animation.
        this.openValue = false;
        this.syncAria();
        this.menuTarget.classList.add(...this.hiddenClassList);
    }

    syncAria() {
        this.triggerTargets.forEach((trigger) => trigger.setAttribute("aria-expanded", String(this.openValue)));
    }

    get hiddenClassList() {
        return this.hasHiddenClass ? this.hiddenClasses : ["hidden"];
    }
}
