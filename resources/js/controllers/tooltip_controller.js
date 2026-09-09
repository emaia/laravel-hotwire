// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";
import { createFloating } from "./_floating.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

let tooltipId = 0;

export default class extends Controller {
    static targets = ["template"];

    static values = {
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
        motion: { type: String, default: "default" },
    };

    initialize() {
        this.onTriggerPointerEnter = this.onTriggerPointerEnter.bind(this);
        this.onTriggerPointerLeave = this.onTriggerPointerLeave.bind(this);
        this.onTooltipPointerEnter = this.onTooltipPointerEnter.bind(this);
        this.onTooltipPointerLeave = this.onTooltipPointerLeave.bind(this);
        this.onFocusIn = this.onFocusIn.bind(this);
        this.onFocusOut = this.onFocusOut.bind(this);
        this.onClick = this.onClick.bind(this);
        this.onWindowKeydown = this.onWindowKeydown.bind(this);
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
        this.presence = null;
        this.topLayer = null;
        this.observer = null;
        this.containmentObserver = null;
        this.ownsEscapeScope = false;
        this.sourceErrorReported = false;
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
        window.removeEventListener("keydown", this.onWindowKeydown, true);
        this.observer?.disconnect();
        this.observer = null;
        this.containmentObserver?.disconnect();
        this.containmentObserver = null;
        this.hoveredTrigger = false;
        this.hoveredTooltip = false;
        this.focused = false;
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
        if (!this.open) this.scheduleOpen();
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

    onWindowKeydown(event) {
        if (isComposing(event)) return;
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

        try {
            this.createTooltip();
        } catch (error) {
            this.hide({ immediate: true });
            if (!this.sourceErrorReported) {
                this.sourceErrorReported = true;
                this.application.handleError(error, "Error opening tooltip", {
                    controller: this,
                    element: this.element,
                });
            }

            return;
        }

        this.open = true;
        this.observeContainment();
        this.setEscapeScope(true);
        this.addDescribedBy();
        window.addEventListener("keydown", this.onWindowKeydown, true);

        this.floating ??= createFloating(this.element, this.tooltip, {
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

        const presence = this.presence;
        void presence.open({ beforeEnter: () => {
            if (!this.open || presence !== this.presence) return false;

            this.topLayer?.show();

            return this.floating?.start() ?? false;
        } }).then((opened) => {
            if (!opened) this.finishShow(presence);
        }).catch((error) => {
            this.finishShow(presence, error);
        });
    }

    hide({ immediate = false } = {}) {
        this.clearTimers();
        this.containmentObserver?.disconnect();
        this.containmentObserver = null;

        if (!this.open && !this.tooltip) return;

        this.open = false;
        this.setEscapeScope(false);
        window.removeEventListener("keydown", this.onWindowKeydown, true);
        this.removeDescribedBy();

        if (immediate) {
            this.presence?.sync(false);
            this.finishHide(this.presence);

            return;
        }

        const presence = this.presence;
        if (!presence) {
            this.destroyTooltip();

            return;
        }

        const closing = presence.close();
        if (!presence.isPresent) {
            this.finishHide(presence);

            return;
        }

        void closing.then((closed) => {
            if (closed) this.finishHide(presence);
        });
    }

    closeForCache() {
        this.hoveredTrigger = false;
        this.hoveredTooltip = false;
        this.focused = false;
        this.hide({ immediate: true });
    }

    isEnabled() {
        if (this.element.closest("[hidden], [inert]")) return false;
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

    observeContainment() {
        this.containmentObserver?.disconnect();
        this.containmentObserver = new MutationObserver(() => {
            if (this.element.closest("[hidden], [inert]")) this.hide({ immediate: true });
        });

        this.containmentObserver.observe(this.observerRoot, {
            attributes: true,
            subtree: true,
            attributeFilter: ["hidden", "inert"],
        });
    }

    syncEnabledState() {
        if (!this.isEnabled()) this.hide();
    }

    createTooltip() {
        if (this.tooltip) return;

        if (this.templateTargets.length !== 1) {
            throw new Error("Tooltip requires exactly one template target.");
        }

        const template = this.templateTarget;
        const source = template.tagName === "TEMPLATE" && template.content.children.length === 1
            ? template.content.firstElementChild
            : null;
        const sourceArrows = source
            ? Array.from(source.querySelectorAll("[data-tooltip-arrow]"))
            : [];

        if (!source || sourceArrows.length > 1) {
            throw new Error("Tooltip template requires one root and at most one arrow.");
        }

        this.tooltip = source.cloneNode(true);
        this.arrow = this.tooltip.querySelector("[data-tooltip-arrow]");
        this.tooltip.id = this.id;
        this.tooltip.setAttribute("role", "tooltip");
        this.tooltip.dataset.state = "closed";
        this.tooltip.dataset.motion = ["default", "none"].includes(this.motionValue) ? this.motionValue : "default";
        this.tooltip.hidden = true;
        this.tooltip.inert = true;
        this.tooltip.addEventListener("pointerenter", this.onTooltipPointerEnter);
        this.tooltip.addEventListener("pointerleave", this.onTooltipPointerLeave);

        document.body.append(this.tooltip);
        this.presence = createPresence(this.tooltip);
        this.presence.sync(false);
        this.topLayer = createTopLayer(this.tooltip);
        this.sourceErrorReported = false;
    }

    destroyTooltip() {
        if (!this.tooltip) return;

        this.presence?.cleanup();
        this.topLayer?.cleanup();
        this.tooltip.removeEventListener("pointerenter", this.onTooltipPointerEnter);
        this.tooltip.removeEventListener("pointerleave", this.onTooltipPointerLeave);
        this.tooltip.remove();
        this.hoveredTooltip = false;
        this.tooltip = null;
        this.arrow = null;
        this.presence = null;
        this.topLayer = null;
    }

    templateTargetConnected() {
        if (!this.open && (this.hoveredTrigger || this.focused)) this.scheduleOpen();
    }

    templateTargetDisconnected() {
        this.hide({ immediate: true });
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

    finishHide(presence) {
        if (presence !== this.presence || this.open || presence?.isPresent) return;

        this.cleanupFloating();
        this.topLayer?.hide();
        this.destroyTooltip();
    }

    finishShow(presence, error = null) {
        if (presence !== this.presence || !this.open) return;
        if (presence.phase !== "closed" || presence.isPresent) return;

        if (error) {
            this.application.handleError(error, "Error opening tooltip", {
                controller: this,
                element: this.element,
            });
        }

        this.open = false;
        this.setEscapeScope(false);
        window.removeEventListener("keydown", this.onWindowKeydown, true);
        this.removeDescribedBy();
        this.finishHide(presence);
    }

    clearTimers() {
        this.clearOpenTimer();
        this.clearCloseTimer();
    }

    setEscapeScope(active) {
        if (active) {
            if (!this.element.hasAttribute("data-hotwire-escape-scope")) {
                this.element.setAttribute("data-hotwire-escape-scope", "");
                this.ownsEscapeScope = true;
            }

            return;
        }

        if (this.ownsEscapeScope) this.element.removeAttribute("data-hotwire-escape-scope");
        this.ownsEscapeScope = false;
    }

    clearOpenTimer() {
        clearTimeout(this.openTimer);
        this.openTimer = null;
    }

    clearCloseTimer() {
        clearTimeout(this.closeTimer);
        this.closeTimer = null;
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
