// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { cancel, enter, leave } from "./_transition.js";

export default class extends Controller {
    static targets = ["trigger", "content"];
    static classes = ["hidden"];
    static values = {
        open: { type: Boolean, default: false },
        openDelay: { type: Number, default: 10 },
        closeDelay: { type: Number, default: 100 },
        side: { type: String, default: "bottom" },
        align: { type: String, default: "start" },
        sideOffset: { type: Number, default: 4 },
        alignOffset: { type: Number, default: 0 },
        strategy: { type: String, default: "fixed" },
        flip: { type: Boolean, default: true },
        shift: { type: Boolean, default: true },
    };

    initialize() {
        this.onKeydown = this.onKeydown.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.activeTrigger = null;
        this.openTimer = null;
        this.closeTimer = null;
        this.pointerInside = false;
        this.focusInside = false;
        this.floating = null;
    }

    get isOpen() {
        return this.openValue;
    }

    connect() {
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);

        this.syncState();
        if (!this.hasContentTarget) return;

        this.hiddenClassList.forEach((cls) => this.contentTarget.classList.toggle(cls, !this.openValue));
        if (this.openValue) this.startFloating();
    }

    disconnect() {
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.clearTimers();
        this.cleanupFloating();
    }

    contentTargetConnected(content) {
        content.dataset.open = String(this.openValue);
        this.hiddenClassList.forEach((cls) => content.classList.toggle(cls, !this.openValue));
        if (this.openValue) this.startFloating();
    }

    pointerEnter(event) {
        this.pointerInside = true;
        this.scheduleOpen(event);
    }

    pointerLeave() {
        this.pointerInside = false;
        this.scheduleClose();
    }

    focusIn(event) {
        this.focusInside = true;
        this.scheduleOpen(event);
    }

    focusOut() {
        this.focusInside = false;
        this.scheduleClose();
    }

    scheduleOpen(event) {
        this.rememberTrigger(event);
        this.clearCloseTimer();
        if (this.openValue || !this.hasContentTarget) return;

        this.clearOpenTimer();
        if (this.openDelayValue <= 0) {
            this.open(event);

            return;
        }

        this.openTimer = setTimeout(() => this.open(event), this.openDelayValue);
    }

    scheduleClose() {
        this.clearOpenTimer();
        if (this.pointerInside || this.focusInside || !this.openValue) return;

        this.clearCloseTimer();
        if (this.closeDelayValue <= 0) {
            this.close();

            return;
        }

        this.closeTimer = setTimeout(() => this.close(), this.closeDelayValue);
    }

    open(event) {
        this.rememberTrigger(event);
        if (this.openValue || !this.hasContentTarget) return;

        this.clearOpenTimer();
        this.openValue = true;
        this.syncState();
        enter(this.contentTarget, { hidden: this.hiddenClassList });
        this.startFloating();
        this.dispatch("opened");
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;

        this.clearTimers();
        this.openValue = false;
        this.syncState();
        this.cleanupFloating();
        if (this.hasContentTarget) leave(this.contentTarget, { hidden: this.hiddenClassList });
        if (focusTrigger) (this.activeTrigger ?? (this.hasTriggerTarget ? this.triggerTarget : null))?.focus();
        this.dispatch("closed");
    }

    onKeydown(event) {
        if (!this.openValue || event.key !== "Escape") return;

        event.preventDefault();
        this.pointerInside = false;
        this.focusInside = false;
        this.close({ focusTrigger: true });
    }

    closeForCache() {
        this.clearTimers();
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
            : event?.target?.closest?.('[data-hover-card-target~="trigger"]');
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

    clearTimers() {
        this.clearOpenTimer();
        this.clearCloseTimer();
    }

    clearOpenTimer() {
        clearTimeout(this.openTimer);
        this.openTimer = null;
    }

    clearCloseTimer() {
        clearTimeout(this.closeTimer);
        this.closeTimer = null;
    }

    get hiddenClassList() {
        return this.hasHiddenClass ? this.hiddenClasses : ["hidden"];
    }
}
