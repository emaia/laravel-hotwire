// @hotwire-package
import { Controller } from "@hotwired/stimulus";

const DEFAULT_ROOT_MARGIN = "300px";
const DEFAULT_THRESHOLD = 1;

export default class extends Controller {
    static targets = ["next", "status"];

    static values = {
        appendTo: String,
        infinite: Boolean,
        loadingLabel: { type: String, default: "Loading more" },
        loadedLabel: { type: String, default: "More results loaded" },
        errorLabel: { type: String, default: "Loading failed" },
        scrollTo: String,
        rootMargin: { type: String, default: DEFAULT_ROOT_MARGIN },
        threshold: { type: Number, default: DEFAULT_THRESHOLD },
    };

    connect() {
        this.loading = false;
        this.originalLabels = new WeakMap();

        if (this.infiniteValue && typeof IntersectionObserver !== "undefined" && this.hasNextTarget) {
            this.observeNextLink();
        }
    }

    disconnect() {
        this.stopObserver();
        this.abortController?.abort();
        this.abortController = null;
    }

    nextTargetConnected() {
        if (this.infiniteValue && typeof IntersectionObserver !== "undefined") {
            this.stopObserver();
            this.observeNextLink();
        }
    }

    load(event) {
        event?.preventDefault();
        this.loadNext(event?.currentTarget || this.nextTarget, true);
    }

    async loadNext(link, manual = false) {
        if (this.loading || ! link?.href) return;

        this.loading = true;
        this.abortController = new AbortController();
        this.stopObserver();
        this.markBusy(link);

        const anchor = this.scrollAnchorFor(link);
        const before = this.positionOf(anchor);
        const shouldRestoreFocus = document.activeElement === link;

        try {
            const response = await fetch(link.href, {
                headers: { Accept: "text/html, application/xhtml+xml" },
                signal: this.abortController.signal,
            });

            if (!response.ok) throw new Error(`Pagination request failed with ${response.status}`);

            const replacement = this.appendPage(await response.text());
            this.restorePosition(anchor, before);
            this.restoreFocus(replacement, shouldRestoreFocus);
            this.announceLoaded(replacement);
            this.loading = false;

            if (manual) {
                this.scrollToTarget();
            }
        } catch (error) {
            if (error.name === "AbortError") return;

            this.element.dataset.state = "error";
            this.clearBusy(link);
            this.announce(this.errorLabelValue);
            this.dispatch("error", { detail: { error } });
            this.loading = false;
        } finally {
            this.abortController = null;
        }
    }

    observeNextLink() {
        if (this.observer) return;

        this.observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                this.loadNext(this.nextTarget);
            }
        }, {
            rootMargin: this.rootMarginValue,
            threshold: this.thresholdValue,
        });

        this.observer.observe(this.nextTarget);
    }

    stopObserver() {
        this.observer?.disconnect();
        this.observer = null;
    }

    appendPage(html) {
        const responseDocument = new window.DOMParser().parseFromString(html, "text/html");
        const sourceContainer = this.sourceContainerFrom(responseDocument);
        const explicitAppendTarget = this.explicitAppendTarget();
        const currentBoundary = this.currentPaginationBoundary(explicitAppendTarget);
        const sourceBoundary = this.sourcePaginationBoundaryFrom(sourceContainer, currentBoundary);
        const sourcePagination = this.sourcePaginationFrom(sourceBoundary || sourceContainer);
        const appendTarget = explicitAppendTarget || this.appendTargetFrom(currentBoundary);
        const sourceAppendTarget = this.sourceAppendTargetFrom(sourceContainer, appendTarget);

        if (!appendTarget || !sourceAppendTarget) {
            throw new Error("Pagination response is missing a matching append target. Pass append-to or place the pagination after a stable container with an id.");
        }

        for (const node of this.appendableNodes(sourceAppendTarget, sourceBoundary || sourcePagination)) {
            this.insertPageNode(appendTarget, currentBoundary, responseDocument.importNode(node, true));
        }

        return this.replacePaginationBoundary(currentBoundary, sourceBoundary || sourcePagination, responseDocument);
    }

    sourceContainerFrom(responseDocument) {
        const frame = this.element.closest("turbo-frame[id]");

        if (frame) {
            return responseDocument.querySelector(`turbo-frame#${this.escapeIdentifier(frame.id)}`) || responseDocument.body;
        }

        return responseDocument.body;
    }

    currentPaginationBoundary(appendTarget) {
        const parent = this.element.parentElement;

        if (
            parent
            && !["BODY", "TURBO-FRAME"].includes(parent.tagName)
            && (parent.id || parent.children.length === 1)
            && !appendTarget?.contains(parent)
        ) {
            return parent;
        }

        return this.element;
    }

    sourcePaginationBoundaryFrom(container, currentBoundary) {
        if (!currentBoundary.id) return null;

        return container.querySelector(`#${this.escapeIdentifier(currentBoundary.id)}`);
    }

    sourcePaginationFrom(container) {
        if (this.element.id) {
            return container.querySelector(`#${this.escapeIdentifier(this.element.id)}`);
        }

        return container.querySelector('[data-slot="pagination"]');
    }

    explicitAppendTarget() {
        if (!this.hasAppendToValue) return null;

        return document.querySelector(this.appendToValue);
    }

    appendTargetFrom(currentBoundary) {
        if (this.hasAppendToValue) {
            return document.querySelector(this.appendToValue);
        }

        if (currentBoundary === this.element) return null;

        const target = currentBoundary.previousElementSibling;

        return target?.id ? target : null;
    }

    sourceAppendTargetFrom(container, appendTarget) {
        if (!appendTarget?.id) return null;

        return container.querySelector(`#${this.escapeIdentifier(appendTarget.id)}`);
    }

    appendableNodes(sourceAppendTarget, sourceBoundary) {
        return [...sourceAppendTarget.childNodes].filter((node) => {
            if (!sourceBoundary || node.nodeType !== Node.ELEMENT_NODE) return true;

            return node !== sourceBoundary && !node.contains(sourceBoundary);
        });
    }

    insertPageNode(appendTarget, currentBoundary, node) {
        if (appendTarget.contains(currentBoundary)) {
            appendTarget.insertBefore(node, currentBoundary);

            return;
        }

        appendTarget.appendChild(node);
    }

    replacePaginationBoundary(currentBoundary, sourceBoundary, responseDocument) {
        if (!sourceBoundary) {
            currentBoundary.remove();

            return null;
        }

        const replacement = responseDocument.importNode(sourceBoundary, true);
        currentBoundary.replaceWith(replacement);

        return replacement;
    }

    markBusy(link) {
        this.element.dataset.state = "loading";
        this.element.setAttribute("aria-busy", "true");
        link.setAttribute("aria-busy", "true");
        this.originalLabels.set(link, link.getAttribute("aria-label"));
        if (this.loadingLabelValue !== "") {
            link.setAttribute("aria-label", this.loadingLabelValue);
        }
        this.announce(this.loadingLabelValue);
    }

    clearBusy(link) {
        this.element.removeAttribute("aria-busy");
        link.removeAttribute("aria-busy");

        if (this.originalLabels.has(link)) {
            const label = this.originalLabels.get(link);

            if (label === null) {
                link.removeAttribute("aria-label");
            } else {
                link.setAttribute("aria-label", label);
            }
        }

        this.originalLabels.delete(link);
    }

    announceLoaded(replacement) {
        const status = replacement?.querySelector?.('[data-pagination-target~="status"]');

        if (status instanceof HTMLElement) {
            const schedule = typeof requestAnimationFrame === "function" ? requestAnimationFrame : (callback) => setTimeout(callback, 0);

            schedule(() => {
                status.textContent = this.loadedLabelValue;
            });
        }
    }

    announce(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }

    scrollAnchorFor(link) {
        return link.previousElementSibling || this.element.previousElementSibling || this.element.parentElement;
    }

    positionOf(element) {
        return element?.getBoundingClientRect();
    }

    restorePosition(element, before) {
        if (!element || !before || !element.isConnected) return;

        const after = element.getBoundingClientRect();
        const scrollable = this.scrollableAncestorFor(element);
        scrollable.scrollTop += after.top - before.top;
        scrollable.scrollLeft += after.left - before.left;
    }

    scrollableAncestorFor(element) {
        let current = element.parentElement;

        while (current) {
            const style = getComputedStyle(current);
            const scrollableY = current.scrollHeight > current.clientHeight && ["auto", "scroll"].includes(style.overflowY);
            const scrollableX = current.scrollWidth > current.clientWidth && ["auto", "scroll"].includes(style.overflowX);

            if (scrollableY || scrollableX) return current;

            current = current.parentElement;
        }

        return document.documentElement;
    }

    scrollToTarget() {
        if (!this.hasScrollToValue) return;

        const target = document.querySelector(this.scrollToValue);

        target?.scrollIntoView();
    }

    restoreFocus(replacement, shouldRestoreFocus) {
        if (!shouldRestoreFocus) return;

        const next = replacement?.querySelector?.('[data-pagination-target~="next"]');

        if (next instanceof HTMLElement) {
            next.focus({ preventScroll: true });

            return;
        }

        const appendTarget = this.hasAppendToValue ? document.querySelector(this.appendToValue) : null;

        if (appendTarget instanceof HTMLElement) {
            if (!appendTarget.hasAttribute("tabindex")) appendTarget.setAttribute("tabindex", "-1");
            appendTarget.focus({ preventScroll: true });
        }
    }

    escapeIdentifier(value) {
        return window.CSS?.escape ? window.CSS.escape(value) : value.replace(/[^a-zA-Z0-9_-]/g, "\\$&");
    }
}
