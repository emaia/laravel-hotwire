// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { createTopLayer } from "./_top_layer.js";
import { cancel, enter, leave } from "./_transition.js";

const MOBILE_QUERY = "(max-width: 767px)";

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
        mobileSide: { type: String, default: "" },
        mobileAlign: { type: String, default: "" },
        mobileMedia: { type: String, default: MOBILE_QUERY },
        collapsedSide: { type: String, default: "" },
        collapsedAlign: { type: String, default: "" },
        collapsedWhen: { type: String, default: "[data-slot=sidebar][data-collapsible=icon], [data-slot=sidebar][data-state=collapsed], [data-slot=sidebar-wrapper][data-state=collapsed], [data-sidebar-collapsible=icon][data-state=collapsed]" },
    };

    initialize() {
        this.onOutsideClick = this.onOutsideClick.bind(this);
        this.onTriggerClick = this.onTriggerClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.onMenuClick = this.onMenuClick.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.onMediaChange = this.onMediaChange.bind(this);
        this.activeTrigger = null;
        this.toggleEvent = null;
        this.floating = null;
        this.topLayer = null;
        this.mediaQuery = null;
    }

    connect() {
        this.element.addEventListener("click", this.onTriggerClick);
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);
        this.connectMediaQuery();

        if (!this.hasMenuTarget) return;

        this.hiddenClassList.forEach((cls) => this.menuTarget.classList.toggle(cls, !this.openValue));
        this.syncState();
        if (this.openValue) this.startFloating();
    }

    disconnect() {
        this.element.removeEventListener("click", this.onTriggerClick);
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.disconnectMediaQuery();
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.cleanupFloating();
    }

    menuTargetConnected(menu) {
        menu.addEventListener("click", this.onMenuClick);
        this.connectMediaQuery();
        this.hiddenClassList.forEach((cls) => menu.classList.toggle(cls, !this.openValue));
        this.syncState();
        if (this.openValue) this.startFloating();
    }

    menuTargetDisconnected(menu) {
        menu.removeEventListener("click", this.onMenuClick);
    }

    toggle(event) {
        if (event) event.hotwireDropdownHandled = true;
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

    onTriggerClick(event) {
        if (event.hotwireDropdownHandled || event.defaultPrevented) return;

        const trigger = event.target.closest?.('[data-dropdown-target~="trigger"]');
        if (!trigger || !this.element.contains(trigger)) return;

        this.toggle(event);
    }

    onKeydown(event) {
        if (this.openValue && event.key === "Escape") {
            event.preventDefault();
            event.stopImmediatePropagation();
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
        this.triggerTargets.forEach((trigger) => {
            trigger.setAttribute("aria-expanded", String(this.openValue));
            trigger.dataset.state = this.openValue ? "open" : "closed";
        });
        if (!this.hasMenuTarget) return;

        this.menuTarget.dataset.open = String(this.openValue);
        this.menuTarget.dataset.side = this.effectiveSide;
        this.menuTarget.dataset.align = this.effectiveAlign;
        this.menuTarget.dataset.dropdownEffectiveSide = this.effectiveSide;
        this.menuTarget.dataset.dropdownEffectiveAlign = this.effectiveAlign;
    }

    startFloating() {
        if (!this.hasMenuTarget || !this.hasTriggerTarget) return;

        const anchor = this.activeTrigger ?? this.triggerTarget;
        this.topLayer ??= createTopLayer(this.menuTarget);
        this.topLayer.show();
        this.floating ??= createFloating(anchor, this.menuTarget, {
            side: this.effectiveSide,
            align: this.effectiveAlign,
            sideOffset: this.configNumber("sideOffset", this.sideOffsetValue),
            alignOffset: this.configNumber("alignOffset", this.alignOffsetValue),
            strategy: this.configString("strategy", this.strategyValue),
            flip: this.configBoolean("flip", this.flipValue),
            shift: this.configBoolean("shift", this.shiftValue),
        });

        void this.floating.start();
    }

    cleanupFloating({ hideTopLayer = true } = {}) {
        this.floating?.cleanup();
        this.floating = null;
        if (hideTopLayer) this.topLayer?.hideAfterTransition();
    }

    connectMediaQuery() {
        this.disconnectMediaQuery();
        if (!window.matchMedia) return;

        this.mediaQuery = window.matchMedia(this.configString("mobileMedia", this.mobileMediaValue));
        this.mediaQuery.addEventListener?.("change", this.onMediaChange);
    }

    disconnectMediaQuery() {
        this.mediaQuery?.removeEventListener?.("change", this.onMediaChange);
        this.mediaQuery = null;
    }

    onMediaChange() {
        this.syncState();
        if (!this.openValue) return;

        this.cleanupFloating({ hideTopLayer: false });
        this.startFloating();
    }

    configString(name, fallback = "") {
        const value = this.hasMenuTarget ? this.menuTarget.dataset[`dropdown${this.capitalize(name)}Value`] : undefined;

        return value === undefined || value === "" ? fallback : value;
    }

    configNumber(name, fallback = 0) {
        const value = Number(this.configString(name, fallback));

        return Number.isFinite(value) ? value : fallback;
    }

    configBoolean(name, fallback = true) {
        const value = this.configString(name, String(fallback));

        return value === "false" ? false : Boolean(value);
    }

    capitalize(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    get effectiveSide() {
        const mobileSide = this.configString("mobileSide", this.mobileSideValue);
        const collapsedSide = this.configString("collapsedSide", this.collapsedSideValue);

        if (this.isCollapsedContext && collapsedSide !== "") return collapsedSide;
        return this.mediaQuery?.matches && mobileSide !== "" ? mobileSide : this.configString("side", this.sideValue);
    }

    get effectiveAlign() {
        const mobileAlign = this.configString("mobileAlign", this.mobileAlignValue);
        const collapsedAlign = this.configString("collapsedAlign", this.collapsedAlignValue);

        if (this.isCollapsedContext && collapsedAlign !== "") return collapsedAlign;
        return this.mediaQuery?.matches && mobileAlign !== "" ? mobileAlign : this.configString("align", this.alignValue);
    }

    get isCollapsedContext() {
        if (this.isInsideCollapsedSidebar) return true;

        const selector = this.configString("collapsedWhen", this.collapsedWhenValue);
        if (selector === "") return false;

        try {
            return this.contextElements
                .some((element) => Boolean(element.closest(selector)) || element.matches(selector));
        } catch (_error) {
            return false;
        }
    }

    get isInsideCollapsedSidebar() {
        return this.contextElements.some((element) => {
            const sidebar = element.closest?.('[data-slot="sidebar"]');
            const wrapper = element.closest?.('[data-slot="sidebar-wrapper"]');

            return sidebar?.dataset.state === "collapsed" ||
                sidebar?.dataset.collapsible === "icon" ||
                (sidebar?.dataset.sidebarCollapsible === "icon" && wrapper?.dataset.state === "collapsed") ||
                wrapper?.dataset.state === "collapsed";
        });
    }

    get contextElements() {
        return [
            this.element,
            this.activeTrigger,
            this.hasTriggerTarget ? this.triggerTarget : null,
            this.hasMenuTarget ? this.menuTarget : null,
        ].filter(Boolean);
    }

    get hiddenClassList() {
        return this.hasHiddenClass ? this.hiddenClasses : ["hidden"];
    }
}
