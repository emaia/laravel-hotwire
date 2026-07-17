// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createFloating } from "./_floating.js";

const ANIMATION_DURATION = 150;

let tooltipId = 0;

export default class extends Controller {
    static values = {
        content: { type: String, default: "Tooltip" },
        side: { type: String, default: "top" },
        align: { type: String, default: "center" },
        sideOffset: { type: Number, default: 8 },
        alignOffset: { type: Number, default: 0 },
        strategy: { type: String, default: "fixed" },
        flip: { type: Boolean, default: true },
        shift: { type: Boolean, default: true },
        delay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 100 },
        enabledWhen: { type: String, default: "" },
    };

    initialize() {
        this.onTriggerPointerEnter = this.onTriggerPointerEnter.bind(this);
        this.onTriggerPointerLeave = this.onTriggerPointerLeave.bind(this);
        this.onTooltipPointerEnter = this.onTooltipPointerEnter.bind(this);
        this.onTooltipPointerLeave = this.onTooltipPointerLeave.bind(this);
        this.onFocusIn = this.onFocusIn.bind(this);
        this.onFocusOut = this.onFocusOut.bind(this);
        this.onClick = this.onClick.bind(this);
        this.onDocumentKeydown = this.onDocumentKeydown.bind(this);
        this.closeForCache = this.closeForCache.bind(this);

        this.id = `hw-tooltip-${++tooltipId}`;
        this.open = false;
        this.hoveredTrigger = false;
        this.hoveredTooltip = false;
        this.focused = false;
        this.openTimer = null;
        this.closeTimer = null;
        this.tooltip = null;
        this.arrow = null;
        this.floating = null;
        this.observer = null;
        this.openAnimationFrame = null;
        this.closeAnimationTimer = null;
    }

    get isOpen() {
        return this.open;
    }

    connect() {
        this.disconnect();

        this.element.addEventListener("pointerenter", this.onTriggerPointerEnter);
        this.element.addEventListener("pointerleave", this.onTriggerPointerLeave);
        this.element.addEventListener("focusin", this.onFocusIn);
        this.element.addEventListener("focusout", this.onFocusOut);
        this.element.addEventListener("click", this.onClick);
        document.addEventListener("turbo:before-cache", this.closeForCache);
        this.observeEnablement();
    }

    disconnect() {
        this.element.removeEventListener("pointerenter", this.onTriggerPointerEnter);
        this.element.removeEventListener("pointerleave", this.onTriggerPointerLeave);
        this.element.removeEventListener("focusin", this.onFocusIn);
        this.element.removeEventListener("focusout", this.onFocusOut);
        this.element.removeEventListener("click", this.onClick);
        document.removeEventListener("turbo:before-cache", this.closeForCache);
        document.removeEventListener("keydown", this.onDocumentKeydown, true);
        this.observer?.disconnect();
        this.observer = null;
        this.hide({ immediate: true });
    }

    onTriggerPointerEnter(event) {
        if (event.pointerType === "touch") return;

        this.hoveredTrigger = true;
        this.scheduleOpen();
    }

    onTriggerPointerLeave() {
        this.hoveredTrigger = false;
        this.scheduleHide();
    }

    onTooltipPointerEnter() {
        this.hoveredTooltip = true;
        this.clearCloseTimer();
    }

    onTooltipPointerLeave() {
        this.hoveredTooltip = false;
        this.scheduleHide();
    }

    onFocusIn() {
        this.focused = true;
        this.scheduleOpen();
    }

    onFocusOut() {
        this.focused = false;
        this.scheduleHide();
    }

    onClick() {
        this.hoveredTrigger = false;
        this.focused = false;
        this.hide();
    }

    onDocumentKeydown(event) {
        if (!this.open || event.key !== "Escape") return;

        event.preventDefault();
        event.stopPropagation();
        this.hoveredTrigger = false;
        this.hoveredTooltip = false;
        this.focused = false;
        this.hide();
    }

    scheduleOpen() {
        this.clearCloseTimer();
        if (this.open || !this.isEnabled()) return;

        this.clearOpenTimer();
        if (this.delayValue <= 0) {
            this.show();

            return;
        }

        this.openTimer = setTimeout(() => this.show(), this.delayValue);
    }

    scheduleHide() {
        this.clearOpenTimer();
        if (!this.open || this.hoveredTrigger || this.hoveredTooltip || this.focused) return;

        this.clearCloseTimer();
        if (this.closeDelayValue <= 0) {
            this.hide();

            return;
        }

        this.closeTimer = setTimeout(() => this.hide(), this.closeDelayValue);
    }

    show() {
        this.clearOpenTimer();
        if (this.open || !this.isEnabled()) return;

        this.open = true;
        this.clearCloseAnimationTimer();
        this.createTooltip();
        this.addDescribedBy();
        document.addEventListener("keydown", this.onDocumentKeydown, true);

        this.floating = createFloating(this.element, this.tooltip, {
            side: this.sideValue,
            align: this.alignValue,
            sideOffset: this.sideOffsetValue,
            alignOffset: this.alignOffsetValue,
            strategy: this.strategyValue,
            flip: this.flipValue,
            shift: this.shiftValue,
            size: false,
            hideWhenDetached: true,
            arrowElement: this.arrow,
            arrowPadding: 4,
        });

        void this.floating.start().then(() => this.queueOpenState());
    }

    hide({ immediate = false } = {}) {
        this.clearTimers();

        if (!this.open && !this.tooltip) return;

        this.open = false;
        document.removeEventListener("keydown", this.onDocumentKeydown, true);
        this.removeDescribedBy();
        this.cleanupFloating();
        this.clearOpenAnimationFrame();

        if (immediate) {
            this.clearCloseAnimationTimer();
            this.destroyTooltip();

            return;
        }

        if (this.tooltip) this.tooltip.dataset.state = "closed";
        this.clearCloseAnimationTimer();
        this.closeAnimationTimer = setTimeout(() => this.destroyTooltip(), ANIMATION_DURATION);
    }

    closeForCache() {
        this.hoveredTrigger = false;
        this.hoveredTooltip = false;
        this.focused = false;
        this.hide({ immediate: true });
    }

    isEnabled() {
        if (!this.enabledWhenValue) return true;

        try {
            if (this.element.closest(this.enabledWhenValue)) return true;

            return Array.from(document.querySelectorAll(this.enabledWhenValue))
                .some((element) => element.contains(this.element));
        } catch (_error) {
            return false;
        }
    }

    observeEnablement() {
        this.observer?.disconnect();
        this.observer = null;

        if (!this.enabledWhenValue) return;

        this.observer = new MutationObserver(() => {
            this.syncEnabledState();
        });

        this.observer.observe(this.observerRoot, {
            attributes: true,
            subtree: true,
        });
    }

    syncEnabledState() {
        if (!this.isEnabled()) this.hide();
    }

    createTooltip() {
        if (this.tooltip) return;

        this.tooltip = document.createElement("div");
        this.tooltip.id = this.id;
        this.tooltip.setAttribute("role", "tooltip");
        this.tooltip.dataset.slot = "tooltip";
        this.tooltip.dataset.state = "closed";
        this.tooltip.innerHTML = this.contentValue;
        this.tooltip.addEventListener("pointerenter", this.onTooltipPointerEnter);
        this.tooltip.addEventListener("pointerleave", this.onTooltipPointerLeave);

        this.arrow = document.createElement("div");
        this.arrow.dataset.slot = "tooltip-arrow";
        this.tooltip.append(this.arrow);

        document.body.append(this.tooltip);
    }

    destroyTooltip() {
        if (!this.tooltip) return;

        this.clearCloseAnimationTimer();
        this.tooltip.dataset.state = "closed";
        this.tooltip.removeEventListener("pointerenter", this.onTooltipPointerEnter);
        this.tooltip.removeEventListener("pointerleave", this.onTooltipPointerLeave);
        this.tooltip.remove();
        this.tooltip = null;
        this.arrow = null;
    }

    queueOpenState() {
        if (!this.open || !this.tooltip) return;

        this.clearOpenAnimationFrame();
        this.openAnimationFrame = requestAnimationFrame(() => {
            if (this.open && this.tooltip) this.tooltip.dataset.state = "open";
            this.openAnimationFrame = null;
        });
    }

    addDescribedBy() {
        const tokens = this.describedByTokens;
        if (!tokens.includes(this.id)) tokens.push(this.id);

        this.element.setAttribute("aria-describedby", tokens.join(" "));
    }

    removeDescribedBy() {
        const tokens = this.describedByTokens.filter((token) => token !== this.id);

        if (tokens.length === 0) {
            this.element.removeAttribute("aria-describedby");

            return;
        }

        this.element.setAttribute("aria-describedby", tokens.join(" "));
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

    clearOpenAnimationFrame() {
        if (this.openAnimationFrame === null) return;

        cancelAnimationFrame(this.openAnimationFrame);
        this.openAnimationFrame = null;
    }

    clearCloseAnimationTimer() {
        clearTimeout(this.closeAnimationTimer);
        this.closeAnimationTimer = null;
    }

    get describedByTokens() {
        return (this.element.getAttribute("aria-describedby") ?? "")
            .split(/\s+/)
            .map((token) => token.trim())
            .filter(Boolean);
    }

    get observerRoot() {
        const root = this.element.getRootNode?.();

        return root?.body ?? root?.host ?? this.element;
    }
}
