// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";
import { createFloating } from "./_floating.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

const MOBILE_QUERY = "(max-width: 767px)";
const TRIGGER_KEY_ATTRIBUTE = "data-hotwire-dropdown-trigger-key";

export default class extends Controller {
    static targets = ["trigger", "menu"];
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
        this.onInternalClick = this.onInternalClick.bind(this);
        this.onTriggerClick = this.onTriggerClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.onMenuClick = this.onMenuClick.bind(this);
        this.onDocumentFocusIn = this.onDocumentFocusIn.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.onMediaChange = this.onMediaChange.bind(this);
        this.activeTrigger = null;
        this.focusedTrigger = null;
        this.pendingTriggerReplacement = null;
        this.toggleEvent = null;
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
        this.floatingProfile = null;
        this.positioningGeneration = 0;
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
        this.mediaQuery = null;
    }

    connect() {
        this.element.addEventListener("click", this.onInternalClick, true);
        this.element.addEventListener("click", this.onTriggerClick);
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("focusin", this.onDocumentFocusIn);
        document.addEventListener("turbo:before-cache", this.closeForCache);
        this.connectMediaQuery();
        this.syncState();
        if (this.hasMenuTarget && this.presenceElement !== this.menuTarget) this.setupMenu(this.menuTarget);
    }

    disconnect() {
        this.element.removeEventListener("click", this.onInternalClick, true);
        this.element.removeEventListener("click", this.onTriggerClick);
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("focusin", this.onDocumentFocusIn);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.disconnectMediaQuery();
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.focusedTrigger = null;
        this.pendingTriggerReplacement = null;
        if (this.hasMenuTarget) this.menuTarget.removeEventListener("click", this.onMenuClick);
        this.teardownMenu();
    }

    menuTargetConnected(menu) {
        menu.addEventListener("click", this.onMenuClick);
        this.connectMediaQuery();
        this.setupMenu(menu);
        this.syncState();
    }

    menuTargetDisconnected(menu) {
        menu.removeEventListener("click", this.onMenuClick);
        if (menu !== this.presenceElement) return;

        this.teardownMenu();
        if (this.openValue && !this.hasMenuTarget) this.close();
    }

    triggerTargetConnected(trigger) {
        if (matchesTrigger(trigger, this.pendingTriggerReplacement) && this.openValue) {
            const replacement = this.pendingTriggerReplacement;
            this.pendingTriggerReplacement = null;
            this.activeTrigger = trigger;
            if (replacement.focused) trigger.focus({ preventScroll: true });
        }
        ensureTriggerKey(trigger);
        this.syncTrigger(trigger);
        this.refreshTriggerAnchor();
    }

    triggerTargetDisconnected(trigger) {
        const wasActive = this.activeTrigger === trigger;
        const focused = this.focusedTrigger === trigger;
        if (focused) this.focusedTrigger = null;
        if (wasActive) {
            const identity = triggerIdentity(trigger);
            this.activeTrigger = null;
            if (this.openValue) {
                const replacement = { focused, ...identity };
                this.pendingTriggerReplacement = replacement;
                setTimeout(() => {
                    if (this.pendingTriggerReplacement !== replacement) return;

                    this.pendingTriggerReplacement = null;
                    if (!this.openValue) return;

                    this.hasTriggerTarget ? this.refreshTriggerAnchor() : this.close();
                }, 0);
            }
        }
        if (!this.openValue) return;

        if (wasActive) return;

        this.hasTriggerTarget ? this.refreshTriggerAnchor() : this.close();
    }

    toggle(event) {
        if (event) event.hotwireDropdownHandled = true;
        this.toggleEvent = event;
        this.rememberTrigger(event);
        this.openValue ? this.close() : this.open();
    }

    open(event) {
        this.rememberTrigger(event);
        if (this.openValue || !this.presence) return;
        this.openValue = true;
        this.syncState();
        this.present();
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;
        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.openValue = false;
        this.syncState();
        this.dismiss();
        if (focusTrigger) this.currentTrigger?.focus();
    }

    onOutsideClick(event) {
        if (event === this.toggleEvent) return;
        if (event.hotwireDropdownInside?.has?.(this.element)) return;
        const path = event.composedPath?.() ?? [];
        if (this.openValue && !path.includes(this.element) && !this.element.contains(event.target)) this.close();
    }

    onInternalClick(event) {
        if (!(event.hotwireDropdownInside instanceof Set)) event.hotwireDropdownInside = new Set();
        event.hotwireDropdownInside.add(this.element);
    }

    onTriggerClick(event) {
        if (event.hotwireDropdownHandled || event.defaultPrevented) return;

        const trigger = event.target.closest?.('[data-dropdown-target~="trigger"]');
        if (!trigger || !this.element.contains(trigger)) return;

        this.toggle(event);
    }

    onKeydown(event) {
        if (isComposing(event)) return;

        if (this.openValue && event.key === "Escape") {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.close({ focusTrigger: true });
        }
    }

    onDocumentFocusIn(event) {
        const trigger = event.target.closest?.('[data-dropdown-target~="trigger"]');
        this.focusedTrigger = trigger && this.element.contains(trigger) ? trigger : null;
    }

    onMenuClick(event) {
        if (this.closeOnSelectValue && event.target.closest("a, button")) this.close();
    }

    closeForCache() {
        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.openValue = false;
        this.syncState();
        this.presence?.sync(false);
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    rememberTrigger(event) {
        const trigger = event?.currentTarget && this.triggerTargets.includes(event.currentTarget)
            ? event.currentTarget
            : event?.target?.closest?.('[data-dropdown-target~="trigger"]');
        if (trigger && this.triggerTargets.includes(trigger)) {
            ensureTriggerKey(trigger);
            this.activeTrigger = trigger;
        }
    }

    syncState() {
        this.element.toggleAttribute("data-hotwire-escape-scope", this.openValue);
        this.triggerTargets.forEach((trigger) => this.syncTrigger(trigger));
        if (!this.hasMenuTarget) return;

        this.menuTarget.dataset.dropdownEffectiveSide = this.effectiveSide;
        this.menuTarget.dataset.dropdownEffectiveAlign = this.effectiveAlign;

        if (!this.presence?.isPresent || !this.floating) {
            this.menuTarget.dataset.side = this.effectiveSide;
            this.menuTarget.dataset.align = this.effectiveAlign;
        }
    }

    startFloating() {
        if (!this.openValue || !this.hasMenuTarget || !this.hasTriggerTarget || !this.topLayer) {
            return Promise.resolve(false);
        }

        const anchor = this.currentTrigger;
        if (!anchor) return Promise.resolve(false);

        const options = {
            side: this.effectiveSide,
            align: this.effectiveAlign,
            sideOffset: this.configNumber("sideOffset", this.sideOffsetValue),
            alignOffset: this.configNumber("alignOffset", this.alignOffsetValue),
            strategy: this.configString("strategy", this.strategyValue),
            flip: this.configBoolean("flip", this.flipValue),
            shift: this.configBoolean("shift", this.shiftValue),
        };
        const profile = JSON.stringify(options);
        if (this.floating && (
            this.floatingAnchor !== anchor ||
            this.floatingElement !== this.menuTarget ||
            this.floatingProfile !== profile
        )) {
            this.cleanupFloating();
        }

        this.topLayer.show();
        if (!this.floating) {
            this.floatingAnchor = anchor;
            this.floatingElement = this.menuTarget;
            this.floatingProfile = profile;
            this.floating = createFloating(anchor, this.menuTarget, options);
        }

        return this.floating.start();
    }

    cleanupFloating() {
        this.floating?.cleanup();
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
        this.floatingProfile = null;
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
        if (!this.openValue) {
            this.floatingProfile = null;

            return;
        }

        this.cleanupFloating();
        this.invalidatePositioning();
        if (this.presence?.phase === "opening") {
            this.present();
        } else {
            this.restartPositioning(this.presence);
        }
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
        const side = this.configString("side", this.sideValue);
        const mobileSide = this.configString("mobileSide", this.mobileSideValue);
        const collapsedSide = this.configString("collapsedSide", this.collapsedSideValue);

        if (this.mediaQuery?.matches) return mobileSide || side;
        if (this.isCollapsedContext) return collapsedSide || side;

        return side;
    }

    get effectiveAlign() {
        const align = this.configString("align", this.alignValue);
        const mobileAlign = this.configString("mobileAlign", this.mobileAlignValue);
        const collapsedAlign = this.configString("collapsedAlign", this.collapsedAlignValue);

        if (this.mediaQuery?.matches) return mobileAlign || align;
        if (this.isCollapsedContext) return collapsedAlign || align;

        return align;
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
            this.currentTrigger,
            this.hasTriggerTarget ? this.triggerTarget : null,
            this.hasMenuTarget ? this.menuTarget : null,
        ].filter(Boolean);
    }

    get currentTrigger() {
        if (this.activeTrigger?.isConnected && this.triggerTargets.includes(this.activeTrigger)) {
            return this.activeTrigger;
        }

        return this.hasTriggerTarget ? this.triggerTarget : null;
    }

    setupMenu(menu) {
        if (this.presenceElement === menu) return;

        this.teardownMenu();
        this.presenceElement = menu;
        this.topLayer = createTopLayer(menu);
        this.presence = createPresence(menu);

        if (this.openValue) {
            this.presence.sync(false);
            this.present({ animate: false });
        } else {
            this.presence.sync(false);
        }
    }

    teardownMenu() {
        this.invalidatePositioning();
        this.presence?.cleanup();
        this.cleanupFloating();
        this.topLayer?.cleanup();
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
    }

    present({ animate = true } = {}) {
        const presence = this.presence;
        if (!presence) return;
        const generation = ++this.positioningGeneration;

        void presence.open({
            beforeEnter: () => this.isPositioningCurrent(generation, presence) ? this.startFloating() : false,
            immediate: !animate,
        }).then((opened) => {
            if (this.isPositioningCurrent(generation, presence)) {
                this.finishPresent(presence, opened, null, generation);
            }
        }).catch((error) => {
            if (this.isPositioningCurrent(generation, presence)) {
                this.finishPresent(presence, false, error, generation);
            }
        });
    }

    dismiss() {
        const presence = this.presence;
        if (!presence) return;

        const closing = presence.close();
        if (!presence.isPresent) {
            this.finishDismiss(presence);

            return;
        }

        void closing.then((closed) => {
            if (closed) this.finishDismiss(presence);
        });
    }

    finishDismiss(presence) {
        if (presence !== this.presence || this.openValue || presence.isPresent) return;

        this.invalidatePositioning();
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    syncTrigger(trigger) {
        trigger.setAttribute("aria-expanded", String(this.openValue));
        trigger.dataset.dropdownState = this.openValue ? "open" : "closed";
    }

    refreshTriggerAnchor() {
        if (!this.openValue || !this.presence?.isPresent || !this.currentTrigger) return;
        if (this.floating && this.floatingAnchor === this.currentTrigger) return;

        this.invalidatePositioning();
        this.cleanupFloating();
        if (this.presence.phase === "opening") {
            this.present();
        } else {
            this.restartPositioning(this.presence);
        }
    }

    restartPositioning(presence) {
        if (!presence) return;
        const generation = ++this.positioningGeneration;

        void Promise.resolve().then(() => {
            return this.isPositioningCurrent(generation, presence) ? this.startFloating() : false;
        }).then((started) => {
            if (!this.isPositioningCurrent(generation, presence)) return;
            if (!started) this.rollbackOpen(presence, null, generation);
        }).catch((error) => {
            if (this.isPositioningCurrent(generation, presence)) {
                this.rollbackOpen(presence, error, generation);
            }
        });
    }

    finishPresent(presence, opened, error, generation) {
        if (opened || presence !== this.presence || !this.openValue) return;
        if (presence.phase !== "closed" || presence.isPresent) return;

        this.rollbackOpen(presence, error, generation);
    }

    rollbackOpen(presence, error, generation) {
        if (!this.isPositioningCurrent(generation, presence)) return;

        if (error) {
            this.application.handleError(error, "Error opening dropdown", {
                controller: this,
                element: this.element,
            });
        }

        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.openValue = false;
        this.syncState();
        presence.sync(false);
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    isPositioningCurrent(generation, presence) {
        return generation === this.positioningGeneration && presence === this.presence && this.openValue;
    }

    invalidatePositioning() {
        this.positioningGeneration++;
    }
}

function matchesTrigger(trigger, identity) {
    if (!identity) return false;

    const key = trigger.getAttribute(TRIGGER_KEY_ATTRIBUTE);

    return Boolean((key && key === identity.key) || (trigger.id && trigger.id === identity.id));
}

function triggerIdentity(trigger) {
    return { id: trigger.id || null, key: ensureTriggerKey(trigger) };
}

function ensureTriggerKey(trigger) {
    let key = trigger.getAttribute(TRIGGER_KEY_ATTRIBUTE);
    if (key) return key;

    key = globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`;
    trigger.setAttribute(TRIGGER_KEY_ATTRIBUTE, key);

    return key;
}
