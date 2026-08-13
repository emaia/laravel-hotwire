// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const ITEM_SELECTOR = "[data-reveal-item]";
const CONTAINER_SELECTOR = '[data-controller~="reveal"]';
const REPLACING_ACTIONS = new Set(["replace", "update", "morph"]);
const finishes = (animation) => Number.isFinite(animation.effect?.getComputedTiming?.().iterations ?? Infinity);
const connectedControllers = new Set();
const handleVisit = () => {
    document.documentElement.dataset.revealBooted = "";
};
// Marks the window in which the Sidebar may carry a view transition name. A name is global, so
// structural.css keys it on this to stay out of transitions that are not navigation.
const handleRenderStart = () => {
    document.documentElement.dataset.revealRendering = "";
};
const handleRenderEnd = () => {
    delete document.documentElement.dataset.revealRendering;
};
const handleStream = (event) => {
    const stream = event.detail?.newStream;

    if (!stream || !REPLACING_ACTIONS.has(stream.getAttribute("action"))) return;

    const content = stream.templateElement?.content;

    if (!content) return;

    const target = stream.getAttribute("target");
    const selector = stream.getAttribute("targets");
    const targets = stream.targetElements ?? [
        ...(target ? [document.getElementById(target)].filter(Boolean) : []),
        ...(selector ? document.querySelectorAll(selector) : []),
    ];
    const relatedTargets = [...targets].filter((element) => element.closest(CONTAINER_SELECTOR));

    if (relatedTargets.length === 0) return;

    const replacesDirectChild = relatedTargets.some(
        (element) =>
            element.hasAttribute("data-reveal-children") || element.parentElement?.hasAttribute("data-reveal-children"),
    );
    const incoming = [...(replacesDirectChild ? content.children : []), ...content.querySelectorAll(ITEM_SELECTOR)];

    incoming.forEach((element) => {
        element.dataset.revealSkip = "";
    });
};

function connectDocumentListeners(controller) {
    if (connectedControllers.size === 0) {
        document.addEventListener("turbo:visit", handleVisit);
        document.addEventListener("turbo:before-stream-render", handleStream);
        document.addEventListener("turbo:before-render", handleRenderStart);
        document.addEventListener("turbo:load", handleRenderEnd);
    }

    connectedControllers.add(controller);
}

function disconnectDocumentListeners(controller) {
    connectedControllers.delete(controller);

    if (connectedControllers.size === 0) {
        document.removeEventListener("turbo:visit", handleVisit);
        document.removeEventListener("turbo:before-stream-render", handleStream);
        document.removeEventListener("turbo:before-render", handleRenderStart);
        document.removeEventListener("turbo:load", handleRenderEnd);
        handleRenderEnd();
    }
}

export default class extends Controller {
    static values = {
        trigger: { type: String, default: "load" },
        threshold: { type: Number, default: 0.15 },
        rootMargin: { type: String, default: "0px 0px -10% 0px" },
        once: { type: Boolean, default: true },
    };

    observer = null;
    mutationObserver = null;
    visibilityMutationObservers = [];
    visibilityIntersectionObserver = null;
    pendingFire = null;
    pendingCounterRetry = null;
    observedItems = new Set();
    pendingItems = new Set();
    pendingVisibilityItems = new Set();
    settleGeneration = 0;
    completedItems = new WeakSet();
    knownItems = new WeakSet();

    connect() {
        this.items.forEach((item) => delete item.dataset.revealArmed);
        this.completedItems = new WeakSet();
        this.knownItems = new WeakSet();
        this.observedItems = new Set();
        this.pendingItems = new Set();
        this.pendingVisibilityItems = new Set();
        this.teardownForCache = this.teardownForCache.bind(this);
        this.releaseAtDocumentEnd = this.releaseAtDocumentEnd.bind(this);
        this.checkDeferredVisibility = this.checkDeferredVisibility.bind(this);

        connectDocumentListeners(this);
        document.addEventListener("turbo:before-cache", this.teardownForCache);

        const visibleItems = this.refreshItems();
        this.observeMutations();

        if (visibleItems.length > 0 || this.items.length === 0) this.scheduleFire(visibleItems);
    }

    disconnect() {
        disconnectDocumentListeners(this);
        document.removeEventListener("turbo:before-cache", this.teardownForCache);
        window.removeEventListener("scroll", this.releaseAtDocumentEnd);

        if (this.pendingFire !== null) {
            cancelAnimationFrame(this.pendingFire);
            this.pendingFire = null;
        }

        if (this.pendingCounterRetry !== null) {
            cancelAnimationFrame(this.pendingCounterRetry);
            this.pendingCounterRetry = null;
        }

        this.settleGeneration++;
        this.observer?.disconnect();
        this.observer = null;
        this.stopWatchingVisibility();
        this.observedItems.clear();
        this.pendingItems.clear();
        this.pendingVisibilityItems.clear();
        this.mutationObserver?.disconnect();
        this.mutationObserver = null;
    }

    indexItems(items = this.items) {
        items.forEach((item, index) => {
            const declared = item.style.getPropertyValue("--reveal-index").trim();

            if (declared === "") item.style.setProperty("--reveal-index", String(index));
        });
    }

    observeMutations() {
        this.mutationObserver = new MutationObserver(() => {
            const visibleItems = this.refreshItems();

            if (visibleItems.length > 0) this.scheduleFire(visibleItems);
        });
        this.mutationObserver.observe(this.element, { childList: true, subtree: true });
    }

    refreshItems() {
        const items = this.items;
        const currentItems = new Set(items);
        this.observedItems.forEach((item) => {
            if (currentItems.has(item)) return;

            this.observer?.unobserve(item);
            this.observedItems.delete(item);
        });
        const added = items.filter((item) => !this.knownItems.has(item));
        added.forEach((item) => this.knownItems.add(item));
        this.indexItems(items);

        if (this.prefersReducedMotion) {
            items.forEach((item) => delete item.dataset.revealArmed);

            return added.filter((item) => !item.hasAttribute("data-reveal-skip"));
        }

        if (this.triggerValue === "scroll") {
            this.armOffscreenItems();

            const visible = added.filter((item) => !item.hasAttribute("data-reveal-armed"));
            if (this.onceValue) visible.forEach((item) => this.completedItems.add(item));

            return visible.filter((item) => !item.hasAttribute("data-reveal-skip"));
        }

        return added.filter((item) => !item.hasAttribute("data-reveal-skip"));
    }

    armOffscreenItems() {
        const pending = this.items.filter(
            (item) =>
                !this.completedItems.has(item) && !item.hasAttribute("data-reveal-armed") && !this.inViewport(item),
        );

        if (pending.length === 0 && this.onceValue) return;

        if (!this.observer) {
            if (typeof IntersectionObserver !== "function") return;

            try {
                this.observer = new IntersectionObserver((entries) => this.releaseItems(entries), {
                    threshold: this.thresholds,
                    rootMargin: this.rootMarginValue,
                });
            } catch {
                return;
            }
        }

        pending.forEach((item) => {
            item.dataset.revealArmed = "";
            this.observer.observe(item);
            this.observedItems.add(item);
        });

        if (!this.onceValue) {
            this.items
                .filter((item) => !pending.includes(item))
                .forEach((item) => {
                    this.observer.observe(item);
                    this.observedItems.add(item);
                });
        }

        window.addEventListener("scroll", this.releaseAtDocumentEnd, { passive: true });
    }

    releaseItems(entries) {
        let batchIndex = 0;
        const released = [];

        entries.forEach((entry) => {
            const itemHeight = entry.boundingClientRect?.height || entry.target.getBoundingClientRect().height;
            const rootHeight = entry.rootBounds?.height || window.innerHeight;
            const maximumRatio = itemHeight > 0 ? Math.min(1, rootHeight / itemHeight) : 1;
            const effectiveThreshold = this.thresholds.findLast((threshold) => threshold <= maximumRatio) ?? 0;
            const meetsThreshold =
                entry.isIntersecting &&
                (entry.intersectionRatio === undefined ||
                    entry.intersectionRatio + Number.EPSILON >= effectiveThreshold);

            if (!entry.isIntersecting) {
                if (!this.onceValue && !entry.target.hasAttribute("data-reveal-armed")) {
                    entry.target.dataset.revealArmed = "";
                }

                return;
            }

            if (!meetsThreshold) return;

            const wasArmed = entry.target.hasAttribute("data-reveal-armed");
            if (!wasArmed) return;

            entry.target.style.setProperty("--reveal-index", String(batchIndex++));
            delete entry.target.dataset.revealArmed;
            released.push(entry.target);

            if (this.onceValue) {
                this.completedItems.add(entry.target);
                this.observer?.unobserve(entry.target);
                this.observedItems.delete(entry.target);
            }
        });

        if (batchIndex > 0) {
            this.scheduleFire(released);
        }
    }

    releaseAtDocumentEnd() {
        const root = document.documentElement;

        if (window.scrollY + window.innerHeight < root.scrollHeight - 2) return;

        const pending = this.items.filter(
            (item) => item.hasAttribute("data-reveal-armed") && item.getBoundingClientRect().bottom > 0,
        );
        pending.forEach((item, index) => {
            item.style.setProperty("--reveal-index", String(index));
            delete item.dataset.revealArmed;

            if (this.onceValue) {
                this.completedItems.add(item);
                this.observer?.unobserve(item);
                this.observedItems.delete(item);
            }
        });

        if (pending.length > 0) this.scheduleFire(pending);
    }

    scheduleFire(items = []) {
        items.forEach((item) => this.pendingItems.add(item));
        if (this.pendingFire !== null) return;

        this.pendingFire = requestAnimationFrame(() => {
            this.pendingFire = null;
            const batch = [...this.pendingItems];
            this.pendingItems.clear();
            this.fire(batch);
        });
    }

    fire(items = this.items) {
        if (!this.isRendered) {
            this.deferUntilRendered(items);

            return;
        }

        this.dispatch("shown");

        if (!this.prefersReducedMotion) this.restartCounters(items);

        if (!this.items.some((item) => item.hasAttribute("data-reveal-armed"))) {
            void this.settle();
        }
    }

    async settle() {
        const generation = ++this.settleGeneration;

        const animations = this.items.flatMap((item) => {
            void getComputedStyle(item).animationName;

            return (item.getAnimations?.({ subtree: true }) ?? []).filter(finishes);
        });
        await Promise.allSettled(animations.map((animation) => animation.finished));

        if (generation === this.settleGeneration && this.element.isConnected) {
            this.element.dataset.revealState = "done";
        }
    }

    restartCounters(items, retry = true) {
        let pending = false;
        const elements = items
            .flatMap((item) => [
                ...(item.matches?.('[data-controller~="animated-number"]') ? [item] : []),
                ...(item.querySelectorAll?.('[data-controller~="animated-number"]') ?? []),
            ])
            .filter((element) => element.closest(CONTAINER_SELECTOR) === this.element);

        elements.forEach((element) => {
            const controller = this.application.getControllerForElementAndIdentifier(element, "animated-number");

            if (controller) {
                if (controller.lazyValue && controller.observer) return;

                controller.animate();
            } else {
                pending = true;
            }
        });

        if (pending && retry) {
            if (this.pendingCounterRetry !== null) cancelAnimationFrame(this.pendingCounterRetry);

            this.pendingCounterRetry = requestAnimationFrame(() => {
                this.pendingCounterRetry = null;
                this.restartCounters(items, false);
            });
        }
    }

    deferUntilRendered(items) {
        items.forEach((item) => this.pendingVisibilityItems.add(item));
        if (this.visibilityMutationObservers.length > 0) return;

        this.visibilityMutationObservers = this.ancestors.map((element) => {
            const observer = new MutationObserver(this.checkDeferredVisibility);
            observer.observe(element, {
                attributes: true,
                attributeFilter: ["class", "hidden", "open", "style"],
            });

            return observer;
        });
        window.addEventListener("resize", this.checkDeferredVisibility);
        document.addEventListener("toggle", this.checkDeferredVisibility, true);

        if (typeof IntersectionObserver === "function") {
            this.visibilityIntersectionObserver = new IntersectionObserver(this.checkDeferredVisibility);
            this.visibilityIntersectionObserver.observe(this.element);
        }
    }

    checkDeferredVisibility() {
        if (!this.element.isConnected || !this.isRendered) return;

        const batch = [...this.pendingVisibilityItems];
        this.pendingVisibilityItems.clear();
        this.stopWatchingVisibility();
        this.fire(batch);
    }

    stopWatchingVisibility() {
        this.visibilityMutationObservers.forEach((observer) => observer.disconnect());
        this.visibilityMutationObservers = [];
        this.visibilityIntersectionObserver?.disconnect();
        this.visibilityIntersectionObserver = null;
        window.removeEventListener("resize", this.checkDeferredVisibility);
        document.removeEventListener("toggle", this.checkDeferredVisibility, true);
    }

    teardownForCache() {
        delete this.element.dataset.revealState;
        this.items.forEach((item) => delete item.dataset.revealArmed);
    }

    get items() {
        if (this.element.hasAttribute("data-reveal-children")) {
            return Array.from(this.element.children);
        }

        return Array.from(this.element.querySelectorAll(ITEM_SELECTOR)).filter(
            (item) => item.closest(CONTAINER_SELECTOR) === this.element,
        );
    }

    inViewport(element) {
        const { top, bottom, left, right } = element.getBoundingClientRect();

        return top < window.innerHeight && bottom > 0 && left < window.innerWidth && right > 0;
    }

    get isRendered() {
        if (typeof this.element.checkVisibility === "function") {
            return this.element.checkVisibility({ checkOpacity: false, checkVisibilityCSS: true });
        }

        const style = getComputedStyle(this.element);

        return !this.element.hidden && style.display !== "none" && style.visibility !== "hidden";
    }

    get prefersReducedMotion() {
        return window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false;
    }

    get thresholds() {
        return Array.from({ length: 21 }, (_, index) => (index * this.thresholdValue) / 20);
    }

    get ancestors() {
        const elements = [];
        let element = this.element;

        while (element) {
            elements.push(element);
            element = element.parentElement;
        }

        return elements;
    }
}
