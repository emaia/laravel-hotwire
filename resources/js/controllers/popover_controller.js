// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

const POPOVER_FOCUSABLE_SELECTOR = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "[tabindex]:not([tabindex='-1'])",
].join(",");
const TRIGGER_KEY_ATTRIBUTE = "data-hotwire-popover-trigger-key";

export default class extends Controller {
    static targets = ["trigger", "content"];
    static values = {
        open: { type: Boolean, default: false },
        side: { type: String, default: "bottom" },
        align: { type: String, default: "start" },
        sideOffset: { type: Number, default: 4 },
        alignOffset: { type: Number, default: 0 },
        strategy: { type: String, default: "fixed" },
        flip: { type: Boolean, default: true },
        shift: { type: Boolean, default: true },
    };

    initialize() {
        this.onOutsideClick = this.onOutsideClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.onDocumentFocusIn = this.onDocumentFocusIn.bind(this);
        this.closeForCache = this.closeForCache.bind(this);
        this.activeTrigger = null;
        this.focusedTrigger = null;
        this.contentHasFocus = false;
        this.pendingTriggerReplacement = null;
        this.pendingContentReplacement = null;
        this.toggleEvent = null;
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
        this.positioningGeneration = 0;
        this.focusOnOpen = false;
        this.notifyOnOpen = false;
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
    }

    get isOpen() {
        return this.openValue;
    }

    connect() {
        document.addEventListener("click", this.onOutsideClick);
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("focusin", this.onDocumentFocusIn);
        document.addEventListener("turbo:before-cache", this.closeForCache);

        this.syncState();
        if (this.hasContentTarget && this.presenceElement !== this.contentTarget) this.setupContent(this.contentTarget);
    }

    disconnect() {
        document.removeEventListener("click", this.onOutsideClick);
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("focusin", this.onDocumentFocusIn);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.focusedTrigger = null;
        this.contentHasFocus = false;
        this.pendingTriggerReplacement = null;
        this.pendingContentReplacement = null;
        this.clearOpenIntent();
        this.teardownContent();
    }

    contentTargetConnected(content) {
        this.setupContent(content);
        this.syncState();
    }

    contentTargetDisconnected(content) {
        if (content !== this.presenceElement) return;

        if (this.openValue) {
            const replacement = { focus: this.contentHasFocus || this.focusOnOpen };
            this.pendingContentReplacement = replacement;
            queueMicrotask(() => {
                if (this.pendingContentReplacement === replacement) this.pendingContentReplacement = null;
            });
        }
        this.contentHasFocus = false;
        this.teardownContent();
        if (this.openValue && !this.hasContentTarget) this.close();
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
        this.toggleEvent = event;
        this.rememberTrigger(event);
        this.openValue ? this.close({ focusTrigger: true }) : this.open(event);
    }

    open(event) {
        this.rememberTrigger(event);
        if (this.openValue || !this.hasContentTarget) return;

        this.openValue = true;
        this.syncState();
        this.present({ focus: true, notify: true });
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;

        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.pendingContentReplacement = null;
        this.clearOpenIntent();
        this.openValue = false;
        this.syncState();
        this.dismiss();
        if (focusTrigger) this.currentTrigger?.focus();
        this.dispatch("closed");
    }

    onOutsideClick(event) {
        if (event === this.toggleEvent) return;
        if (this.openValue && !this.element.contains(event.target)) this.close();
    }

    onKeydown(event) {
        if (!this.openValue || event.key !== "Escape") return;

        event.preventDefault();
        event.stopImmediatePropagation();
        this.close({ focusTrigger: true });
    }

    onDocumentFocusIn(event) {
        this.contentHasFocus = Boolean(this.presenceElement?.contains(event.target));
        const trigger = event.target.closest?.('[data-popover-target~="trigger"]');
        this.focusedTrigger = trigger && this.element.contains(trigger) ? trigger : null;
    }

    closeForCache() {
        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.pendingContentReplacement = null;
        this.clearOpenIntent();
        this.openValue = false;
        this.syncState();
        this.presence?.sync(false);
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    rememberTrigger(event) {
        const trigger = event?.currentTarget && this.triggerTargets.includes(event.currentTarget)
            ? event.currentTarget
            : event?.target?.closest?.('[data-popover-target~="trigger"]');
        if (trigger && this.triggerTargets.includes(trigger)) {
            ensureTriggerKey(trigger);
            this.activeTrigger = trigger;
        }
    }

    syncState() {
        this.element.toggleAttribute("data-hotwire-escape-scope", this.openValue);
        this.triggerTargets.forEach((trigger) => this.syncTrigger(trigger));
    }

    startFloating() {
        if (!this.openValue || !this.hasContentTarget || !this.hasTriggerTarget || !this.topLayer) {
            return Promise.resolve(false);
        }

        const anchor = this.currentTrigger;
        if (!anchor) return Promise.resolve(false);

        if (this.floating && (this.floatingAnchor !== anchor || this.floatingElement !== this.contentTarget)) {
            this.cleanupFloating();
        }

        this.topLayer.show();
        if (!this.floating) {
            this.floatingAnchor = anchor;
            this.floatingElement = this.contentTarget;
            this.floating = createFloating(anchor, this.contentTarget, {
                side: this.sideValue,
                align: this.alignValue,
                sideOffset: this.sideOffsetValue,
                alignOffset: this.alignOffsetValue,
                strategy: this.strategyValue,
                flip: this.flipValue,
                shift: this.shiftValue,
            });
        }

        return this.floating.start();
    }

    cleanupFloating() {
        this.floating?.cleanup();
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
    }

    focusContent() {
        if (!this.hasContentTarget) return;

        const target = this.contentTarget.querySelector(POPOVER_FOCUSABLE_SELECTOR) ?? this.contentTarget;
        target.focus?.({ preventScroll: true });
    }

    setupContent(content) {
        if (this.presenceElement === content) return;

        const restoreFocus = this.openValue && (
            this.pendingContentReplacement?.focus === true ||
            (Boolean(this.presenceElement) && (this.contentHasFocus || this.focusOnOpen))
        );
        this.pendingContentReplacement = null;
        this.contentHasFocus = false;
        this.teardownContent();
        this.presenceElement = content;
        this.topLayer = createTopLayer(content);
        this.presence = createPresence(content);

        if (this.openValue) {
            this.presence.sync(false);
            this.present({ animate: false, focus: restoreFocus });
        } else {
            this.presence.sync(false);
        }
    }

    teardownContent() {
        this.invalidatePositioning();
        this.presence?.cleanup();
        this.cleanupFloating();
        this.topLayer?.cleanup();
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
    }

    present({ animate = true, focus = false, notify = false } = {}) {
        const presence = this.presence;
        if (!presence) return;
        if (focus) this.focusOnOpen = true;
        if (notify) this.notifyOnOpen = true;
        const generation = ++this.positioningGeneration;

        void presence.open({
            beforeEnter: () => this.isPositioningCurrent(generation, presence) ? this.startFloating() : false,
            onEnter: () => this.finishEnter(presence, generation),
            immediate: !animate,
        }).then((opened) => {
            if (!this.isPositioningCurrent(generation, presence)) return;
            if (!opened) {
                this.finishPresent(presence, false, null, generation);

                return;
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

    finishEnter(presence, generation) {
        if (!this.isPositioningCurrent(generation, presence)) return;

        const shouldFocus = this.focusOnOpen;
        const shouldNotify = this.notifyOnOpen;
        this.clearOpenIntent();
        if (shouldFocus) this.focusContent();
        if (shouldNotify) this.dispatch("opened");
    }

    get currentTrigger() {
        if (this.activeTrigger?.isConnected && this.triggerTargets.includes(this.activeTrigger)) {
            return this.activeTrigger;
        }

        return this.hasTriggerTarget ? this.triggerTarget : null;
    }

    syncTrigger(trigger) {
        trigger.setAttribute("aria-expanded", String(this.openValue));
        trigger.dataset.popoverState = this.openValue ? "open" : "closed";
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
            this.application.handleError(error, "Error opening popover", {
                controller: this,
                element: this.element,
            });
        }

        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.pendingContentReplacement = null;
        this.clearOpenIntent();
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

    clearOpenIntent() {
        this.focusOnOpen = false;
        this.notifyOnOpen = false;
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
