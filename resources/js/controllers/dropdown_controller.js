// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { createTopLayer } from "./_top_layer.js";
import { cancel, enter, leave } from "./_transition.js";

export default class extends Controller {
    static targets = ["trigger", "menu"];
    static classes = ["hidden"];
    static values = {
        open: { type: Boolean, default: false },
        closeOnSelect: { type: Boolean, default: true },
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
        this.onMenuClick = this.onMenuClick.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.activeTrigger = null;
        this.toggleEvent = null;
        this.floating = null;
        this.topLayer = null;
    }

    connect() {
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);

        this.hiddenClassList.forEach((cls) => this.menuTarget.classList.toggle(cls, !this.openValue));
        this.syncState();
        if (this.openValue) this.startFloating();
    }

    disconnect() {
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.cleanupFloating();
    }

    menuTargetConnected(menu) {
        menu.addEventListener("click", this.onMenuClick);
    }

    menuTargetDisconnected(menu) {
        menu.removeEventListener("click", this.onMenuClick);
    }

    toggle(event) {
        this.toggleEvent = event;
        this.rememberTrigger(event);
        this.openValue ? this.close() : this.open();
    }

    open(event) {
        this.rememberTrigger(event);
        if (this.openValue) return;
        this.openValue = true;
        this.syncState();
        enter(this.menuTarget, { hidden: this.hiddenClassList });
        this.startFloating();
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;
        this.openValue = false;
        this.syncState();
        this.cleanupFloating();
        leave(this.menuTarget, { hidden: this.hiddenClassList });
        if (focusTrigger) (this.activeTrigger ?? (this.hasTriggerTarget ? this.triggerTarget : null))?.focus();
    }

    onOutsideClick(event) {
        if (event === this.toggleEvent) return;
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
        this.cleanupFloating();
        cancel(this.menuTarget);
        this.openValue = false;
        this.syncState();
        this.menuTarget.classList.add(...this.hiddenClassList);
    }

    rememberTrigger(event) {
        const trigger = event?.currentTarget && this.triggerTargets.includes(event.currentTarget)
            ? event.currentTarget
            : event?.target?.closest?.('[data-dropdown-target~="trigger"]');
        if (trigger && this.triggerTargets.includes(trigger)) this.activeTrigger = trigger;
    }

    syncState() {
        this.element.toggleAttribute("data-hotwire-escape-scope", this.openValue);
        this.triggerTargets.forEach((trigger) => trigger.setAttribute("aria-expanded", String(this.openValue)));
        this.menuTarget.dataset.open = String(this.openValue);
    }

    startFloating() {
        if (!this.hasMenuTarget || !this.hasTriggerTarget) return;

        const anchor = this.activeTrigger ?? this.triggerTarget;
        this.topLayer ??= createTopLayer(this.menuTarget);
        this.topLayer.show();
        this.floating ??= createFloating(anchor, this.menuTarget, {
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
        this.topLayer?.hideAfterTransition();
    }

    get hiddenClassList() {
        return this.hasHiddenClass ? this.hiddenClasses : ["hidden"];
    }
}
