// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const transitionProperties = new Set(["max-block-size", "max-height"]);
const transitionTimeout = 750;

export default class extends Controller {
    static targets = ["viewport", "content", "trigger", "fade", "moreLabel", "lessLabel", "icon"];

    static values = {
        collapsedHeight: { type: Number, default: 320 },
        expanded: { type: Boolean, default: false },
    };

    connect() {
        this.overflowing = false;
        this.measuredHeight = null;
        this.observedContent = null;
        this.refreshRafId = null;
        this.transitionRafId = null;
        this.transitionTimeoutId = null;
        this.transitionGeneration = 0;
        this.transitionExpandedValue = this.expandedValue;
        this.resizeObserver =
            typeof ResizeObserver === "function" ? new ResizeObserver(() => this.scheduleRefresh()) : null;

        this.observeContent();
        this.element.style.setProperty("--read-more-collapsed-height", `${this.collapsedHeightValue}px`);
        this.refresh();
    }

    disconnect() {
        this.resizeObserver?.disconnect();
        this.resizeObserver = null;
        this.observedContent = null;
        this.cancelRefresh();
        this.cancelTransition();
    }

    contentTargetConnected() {
        if (this.resizeObserver) this.observeContent();
        if (this.overflowing === undefined) return;

        this.refresh();
    }

    contentTargetDisconnected(element) {
        if (this.resizeObserver && this.observedContent === element) {
            this.resizeObserver.disconnect();
            this.observedContent = null;
        }

        if (this.overflowing !== undefined) this.refresh();
    }

    viewportTargetConnected() {
        if (this.overflowing !== undefined) this.refresh();
    }

    viewportTargetDisconnected() {
        if (this.overflowing !== undefined) this.refresh();
    }

    toggle() {
        this.expandedValue ? this.collapse() : this.expand();
    }

    expand() {
        if (!this.overflowing || this.expandedValue) return;

        this.transitionTo(true);
        this.dispatch("change", { detail: { expanded: true } });
    }

    collapse() {
        if (!this.overflowing || !this.expandedValue) return;

        this.transitionTo(false);
        this.dispatch("change", { detail: { expanded: false } });
    }

    refresh() {
        if (!this.hasViewportTarget || !this.hasContentTarget) {
            this.overflowing = false;
            this.measuredHeight = null;
            this.cancelTransition();
            this.sync();

            return;
        }

        const transitioning = this.element.hasAttribute("data-transitioning");
        const viewportHeight = this.viewportTarget.scrollHeight;
        this.measuredHeight = viewportHeight;
        this.element.style.setProperty("--read-more-expanded-height", `${viewportHeight}px`);
        this.overflowing = viewportHeight > this.collapsedHeightValue + 1;

        if (!this.overflowing) this.cancelTransition();

        this.sync();

        if (transitioning && this.overflowing) this.waitForTransition();
    }

    expandedValueChanged() {
        if (this.overflowing === undefined) return;

        const expanded = this.expandedValue;
        if (this.transitionExpandedValue === expanded) return;

        if (this.overflowing) {
            this.transitionTo(expanded, false);

            return;
        }

        this.transitionExpandedValue = expanded;
        this.sync();
    }

    collapsedHeightValueChanged() {
        if (this.overflowing === undefined) return;

        this.element.style.setProperty("--read-more-collapsed-height", `${this.collapsedHeightValue}px`);
        this.refresh();
    }

    observeContent() {
        if (!this.resizeObserver || !this.hasContentTarget || this.observedContent === this.contentTarget) return;

        if (this.observedContent) this.resizeObserver.disconnect();
        this.observedContent = this.contentTarget;
        this.resizeObserver.observe(this.observedContent, { box: "border-box" });
    }

    scheduleRefresh() {
        if (!this.hasViewportTarget) return;

        const viewportHeight = this.viewportTarget.scrollHeight;
        if (viewportHeight === this.measuredHeight || this.refreshRafId !== null) return;

        this.refreshRafId = requestAnimationFrame(() => {
            this.refreshRafId = null;
            this.refresh();
        });
    }

    cancelRefresh() {
        if (this.refreshRafId === null) return;

        cancelAnimationFrame(this.refreshRafId);
        this.refreshRafId = null;
    }

    transitionTo(expanded, writeValue = true) {
        const pinnedHeight =
            !expanded && this.hasViewportTarget ? this.viewportTarget.getBoundingClientRect().height : null;

        this.cancelTransition();
        this.transitionExpandedValue = expanded;
        this.element.setAttribute("data-transitioning", "");

        if (pinnedHeight !== null) {
            this.element.style.setProperty("--read-more-pinned-height", `${pinnedHeight}px`);
            this.element.setAttribute("data-pinning", "");
            this.sync();
            void this.viewportTarget.offsetHeight;
            this.element.removeAttribute("data-pinning");
        }

        if (writeValue) {
            this.expandedValue = expanded;
        }

        this.sync();
        this.waitForTransition();
    }

    waitForTransition() {
        this.cancelTransitionWait();
        const generation = this.transitionGeneration;

        this.transitionRafId = requestAnimationFrame(() => {
            this.transitionRafId = requestAnimationFrame(() => {
                this.transitionRafId = null;
                if (generation !== this.transitionGeneration || !this.hasViewportTarget) return;

                const animations = (this.viewportTarget.getAnimations?.() ?? []).filter((animation) =>
                    transitionProperties.has(animation.transitionProperty),
                );

                if (animations.length === 0) {
                    this.settleTransition(generation);

                    return;
                }

                this.transitionTimeoutId = setTimeout(() => {
                    this.transitionTimeoutId = null;
                    this.settleTransition(generation);
                }, transitionTimeout);

                Promise.allSettled(animations.map((animation) => animation.finished)).then(() => {
                    this.settleTransition(generation);
                });
            });
        });
    }

    settleTransition(generation) {
        if (generation !== this.transitionGeneration) return;

        this.transitionGeneration += 1;
        this.clearTransitionTimeout();
        this.element.removeAttribute("data-transitioning");
        this.element.removeAttribute("data-pinning");
        this.element.style.removeProperty("--read-more-pinned-height");
    }

    cancelTransition() {
        this.cancelTransitionWait();

        this.element.removeAttribute("data-transitioning");
        this.element.removeAttribute("data-pinning");
        this.element.style.removeProperty("--read-more-pinned-height");
    }

    cancelTransitionWait() {
        this.transitionGeneration += 1;
        this.clearTransitionTimeout();

        if (this.transitionRafId === null) return;

        cancelAnimationFrame(this.transitionRafId);
        this.transitionRafId = null;
    }

    clearTransitionTimeout() {
        if (this.transitionTimeoutId === null) return;

        clearTimeout(this.transitionTimeoutId);
        this.transitionTimeoutId = null;
    }

    sync() {
        const expanded = this.overflowing && this.expandedValue;

        this.element.dataset.state = !this.overflowing ? "static" : expanded ? "expanded" : "collapsed";
        this.element.setAttribute("data-ready", "");

        if (this.hasTriggerTarget) {
            if (!this.overflowing && document.activeElement === this.triggerTarget && this.hasContentTarget) {
                this.contentTarget.focus({ preventScroll: true });
            }

            this.triggerTarget.hidden = !this.overflowing;
            this.triggerTarget.setAttribute("aria-expanded", String(expanded));
        }

        if (this.hasFadeTarget) this.fadeTarget.hidden = !this.overflowing || expanded;
        if (this.hasMoreLabelTarget) this.moreLabelTarget.hidden = expanded;
        if (this.hasLessLabelTarget) this.lessLabelTarget.hidden = !expanded;
        if (this.hasIconTarget) this.iconTarget.dataset.state = expanded ? "expanded" : "collapsed";
    }
}
