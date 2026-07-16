// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { cancel, enter, leave } from "./_transition.js";

const POPOVER_FOCUSABLE_SELECTOR = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "[tabindex]:not([tabindex='-1'])",
].join(",");

export default class extends Controller {
    static targets = ["trigger", "content"];
    static classes = ["hidden"];
    static values = {
        open: { type: Boolean, default: false },
        side: { type: String, default: "bottom" },
        align: { type: String, default: "start" },
        sideOffset: { type: Number, default: 4 },
        alignOffset: { type: Number, default: 0 },
        strategy: { type: String, default: "absolute" },
        flip: { type: Boolean, default: true },
        shift: { type: Boolean, default: true },
    };

    initialize() {
        this.onOutsideClick = this.onOutsideClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.activeTrigger = null;
        this.toggleEvent = null;
        this.floating = null;
    }

    get isOpen() {
        return this.openValue;
    }

    connect() {
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);

        this.syncState();
        if (!this.hasContentTarget) return;

        this.hiddenClassList.forEach((cls) => this.contentTarget.classList.toggle(cls, !this.openValue));
        if (this.openValue) this.startFloating();
    }

    disconnect() {
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.cleanupFloating();
    }

    contentTargetConnected(content) {
        content.dataset.open = String(this.openValue);
        this.hiddenClassList.forEach((cls) => content.classList.toggle(cls, !this.openValue));
        if (this.openValue) this.startFloating();
    }

    toggle(event) {
        this.toggleEvent = event;
        this.rememberTrigger(event);
        this.openValue ? this.close({ focusTrigger: true }) : this.open(event);
    }

    open(event) {
        this.rememberTrigger(event);
        if (this.openValue || !this.hasContentTarget) return;

        this.openValue = true;
        this.syncState();
        enter(this.contentTarget, { hidden: this.hiddenClassList });
        this.startFloating();
        this.focusContent();
        this.dispatch("opened");
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;

        this.openValue = false;
        this.syncState();
        this.cleanupFloating();
        if (this.hasContentTarget) leave(this.contentTarget, { hidden: this.hiddenClassList });
        if (focusTrigger) (this.activeTrigger ?? (this.hasTriggerTarget ? this.triggerTarget : null))?.focus();
        this.dispatch("closed");
    }

    onOutsideClick(event) {
        if (event === this.toggleEvent) return;
        if (this.openValue && !this.element.contains(event.target)) this.close();
    }

    onKeydown(event) {
        if (!this.openValue || event.key !== "Escape") return;

        event.preventDefault();
        this.close({ focusTrigger: true });
    }

    closeForCache() {
        this.cleanupFloating();
        this.openValue = false;
        this.syncState();
        if (!this.hasContentTarget) return;

        cancel(this.contentTarget);
        this.contentTarget.classList.add(...this.hiddenClassList);
    }

    rememberTrigger(event) {
        const trigger = event?.currentTarget && this.triggerTargets.includes(event.currentTarget)
            ? event.currentTarget
            : event?.target?.closest?.('[data-popover-target~="trigger"]');
        if (trigger && this.triggerTargets.includes(trigger)) this.activeTrigger = trigger;
    }

    syncState() {
        this.element.toggleAttribute("data-hotwire-escape-scope", this.openValue);
        this.triggerTargets.forEach((trigger) => trigger.setAttribute("aria-expanded", String(this.openValue)));
        if (!this.hasContentTarget) return;

        this.contentTarget.dataset.open = String(this.openValue);
    }

    startFloating() {
        if (!this.hasContentTarget || !this.hasTriggerTarget) return;

        const anchor = this.activeTrigger ?? this.triggerTarget;
        this.floating ??= createFloating(anchor, this.contentTarget, {
            side: this.sideValue,
            align: this.alignValue,
            sideOffset: this.sideOffsetValue,
            alignOffset: this.alignOffsetValue,
            strategy: this.strategyValue,
            flip: this.flipValue,
            shift: this.shiftValue,
        });

        void this.floating.start();
    }

    cleanupFloating() {
        this.floating?.cleanup();
        this.floating = null;
    }

    focusContent() {
        if (!this.hasContentTarget) return;

        const target = this.contentTarget.querySelector(POPOVER_FOCUSABLE_SELECTOR) ?? this.contentTarget;
        target.focus?.({ preventScroll: true });
    }

    get hiddenClassList() {
        return this.hasHiddenClass ? this.hiddenClasses : ["hidden"];
    }
}
