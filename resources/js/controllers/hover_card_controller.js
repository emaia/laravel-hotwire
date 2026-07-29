// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

const TRIGGER_KEY_ATTRIBUTE = "data-hotwire-hover-card-trigger-key";

export default class extends Controller {
    static targets = ["trigger", "content"];
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
        this.pointerSources = new Set();
        this.focusSources = new Set();
        this.detachedFocusCandidate = null;
        this.pendingTriggerReplacement = null;
        this.floating = null;
        this.floatingAnchor = null;
        this.floatingElement = null;
        this.positioningGeneration = 0;
        this.notifyOnOpen = false;
        this.presence = null;
        this.presenceElement = null;
        this.topLayer = null;
    }

    get isOpen() {
        return this.openValue;
    }

    connect() {
        document.addEventListener("keydown", this.onKeydown);
        document.addEventListener("turbo:before-cache", this.closeForCache);

        this.syncState();
        if (this.hasContentTarget && this.presenceElement !== this.contentTarget) this.setupContent(this.contentTarget);
    }

    disconnect() {
        document.removeEventListener("keydown", this.onKeydown);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        this.element.removeAttribute("data-hotwire-escape-scope");
        this.clearTimers();
        this.pointerSources.clear();
        this.focusSources.clear();
        this.detachedFocusCandidate = null;
        this.pendingTriggerReplacement = null;
        this.notifyOnOpen = false;
        this.teardownContent();
    }

    contentTargetConnected(content) {
        this.setupContent(content);
        this.syncState();
    }

    contentTargetDisconnected(content) {
        if (content !== this.presenceElement) return;

        this.pointerSources.delete(content);
        this.focusSources.delete(content);
        this.teardownContent();
        if (this.openValue && !this.hasContentTarget) this.close();
    }

    triggerTargetConnected(trigger) {
        if (matchesTrigger(trigger, this.pendingTriggerReplacement) && this.openValue) {
            const replacement = this.pendingTriggerReplacement;
            this.pendingTriggerReplacement = null;
            this.activeTrigger = trigger;
            if (replacement.pointed) this.pointerSources.add(trigger);
            if (replacement.focused) trigger.focus({ preventScroll: true });
            if (replacement.pointed || replacement.focused) this.clearCloseTimer();
        }
        ensureTriggerKey(trigger);
        this.syncTrigger(trigger);
        this.refreshTriggerAnchor();
    }

    triggerTargetDisconnected(trigger) {
        const wasActive = this.activeTrigger === trigger;
        const pointed = this.pointerSources.has(trigger);
        const focused = this.focusSources.has(trigger) || trigger.contains(document.activeElement) ||
            this.detachedFocusCandidate?.trigger === trigger;
        if (this.detachedFocusCandidate?.trigger === trigger) this.detachedFocusCandidate = null;
        this.pointerSources.delete(trigger);
        this.focusSources.delete(trigger);
        if (wasActive) {
            const replacement = { focused, pointed, ...triggerIdentity(trigger) };
            this.pendingTriggerReplacement = this.openValue ? replacement : null;
            if (this.pendingTriggerReplacement) {
                setTimeout(() => {
                    if (this.pendingTriggerReplacement === replacement) this.pendingTriggerReplacement = null;
                }, 0);
            }
            this.activeTrigger = null;
            this.clearOpenTimer();
        }
        if (!this.openValue) return;

        this.hasTriggerTarget ? this.refreshTriggerAnchor() : this.close();
        if (wasActive && this.openValue) this.scheduleClose();
    }

    pointerEnter(event) {
        this.pointerSources.add(event.currentTarget);
        this.scheduleOpen(event);
    }

    pointerLeave(event) {
        this.pointerSources.delete(event.currentTarget);
        this.scheduleClose();
    }

    focusIn(event) {
        this.detachedFocusCandidate = null;
        this.focusSources.add(event.currentTarget);
        this.scheduleOpen(event);
    }

    focusOut(event) {
        if (event.relatedTarget === null && event.currentTarget.matches?.('[data-hover-card-target~="trigger"]')) {
            const candidate = { trigger: event.currentTarget };
            this.detachedFocusCandidate = candidate;
            setTimeout(() => {
                if (this.detachedFocusCandidate === candidate) this.detachedFocusCandidate = null;
            }, 0);
        }
        this.focusSources.delete(event.currentTarget);
        this.scheduleClose();
    }

    scheduleOpen(event) {
        this.rememberTrigger(event);
        this.clearCloseTimer();
        if (this.openValue || !this.hasContentTarget || !this.hasTriggerTarget) return;

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
            this.closeTimer = setTimeout(() => {
                this.closeTimer = null;
                if (!this.pointerInside && !this.focusInside) this.close();
            }, 0);

            return;
        }

        this.closeTimer = setTimeout(() => this.close(), this.closeDelayValue);
    }

    open(event) {
        this.rememberTrigger(event);
        if (this.openValue || !this.hasContentTarget || !this.hasTriggerTarget) return;

        this.clearOpenTimer();
        this.openValue = true;
        this.syncState();
        this.present({ notify: true });
    }

    close({ focusTrigger = false } = {}) {
        if (!this.openValue) return;

        this.clearTimers();
        this.detachedFocusCandidate = null;
        this.pendingTriggerReplacement = null;
        this.invalidatePositioning();
        this.notifyOnOpen = false;
        this.openValue = false;
        this.syncState();
        this.dismiss();
        if (focusTrigger) this.currentTrigger?.focus();
        this.dispatch("closed");
    }

    onKeydown(event) {
        if (!this.openValue || event.key !== "Escape") return;

        event.preventDefault();
        event.stopImmediatePropagation();
        this.pointerSources.clear();
        this.focusSources.clear();
        this.close({ focusTrigger: true });
    }

    closeForCache() {
        this.clearTimers();
        this.pointerSources.clear();
        this.focusSources.clear();
        this.detachedFocusCandidate = null;
        this.pendingTriggerReplacement = null;
        this.invalidatePositioning();
        this.notifyOnOpen = false;
        this.openValue = false;
        this.syncState();
        this.presence?.sync(false);
        this.cleanupFloating();
        this.topLayer?.hide();
    }

    rememberTrigger(event) {
        const trigger = event?.currentTarget && this.triggerTargets.includes(event.currentTarget)
            ? event.currentTarget
            : event?.target?.closest?.('[data-hover-card-target~="trigger"]');
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

    setupContent(content) {
        if (this.presenceElement === content) return;

        this.teardownContent();
        this.presenceElement = content;
        this.topLayer = createTopLayer(content);
        this.presence = createPresence(content);

        if (this.openValue) {
            this.presence.sync(false);
            this.present({ animate: false });
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

    present({ animate = true, notify = false } = {}) {
        const presence = this.presence;
        if (!presence) return;
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

        const shouldNotify = this.notifyOnOpen;
        this.notifyOnOpen = false;
        if (shouldNotify) this.dispatch("opened");
    }

    get currentTrigger() {
        if (this.activeTrigger?.isConnected && this.triggerTargets.includes(this.activeTrigger)) {
            return this.activeTrigger;
        }

        return this.hasTriggerTarget ? this.triggerTarget : null;
    }

    get pointerInside() {
        return this.pointerSources.size > 0;
    }

    get focusInside() {
        return this.focusSources.size > 0;
    }

    syncTrigger(trigger) {
        trigger.setAttribute("aria-expanded", String(this.openValue));
        trigger.dataset.hoverCardState = this.openValue ? "open" : "closed";
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
            this.application.handleError(error, "Error opening hover card", {
                controller: this,
                element: this.element,
            });
        }

        this.invalidatePositioning();
        this.pendingTriggerReplacement = null;
        this.notifyOnOpen = false;
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
