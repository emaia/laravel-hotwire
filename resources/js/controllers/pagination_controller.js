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

    #request = null;

    connect() {
        this.observeNextLink();
    }

    disconnect() {
        this.stopObserver();
        if (this.#request) this.#cancelRequest(this.#request, false);
    }

    nextTargetConnected() {
        this.observeNextLink();
    }

    nextTargetDisconnected(link) {
        if (this.#request?.link === link) this.#cancelRequest(this.#request);
        if (this.observedLink !== link) return;

        this.stopObserver();
        this.observeNextLink();
    }

    appendToValueChanged() {
        if (this.#request) this.#isCurrentRequest(this.#request);
    }

    load(event) {
        event?.preventDefault();
        this.loadNext(event?.currentTarget || this.nextTarget, true);
    }

    async loadNext(link, manual = false) {
        if (this.#request || !link?.href) return;

        const request = {
            link,
            url: link.href,
            destination: this.#appendDestination(),
            abort: new AbortController(),
            originalLabel: link.getAttribute("aria-label"),
            loadingLabel: this.loadingLabelValue,
        };
        this.#request = request;
        this.stopObserver();
        this.markBusy(link);
        this.#watchRequest(request);

        const anchor = this.scrollAnchorFor(link);
        const before = this.positionOf(anchor);
        const shouldRestoreFocus = document.activeElement === link;

        let replacement;
        try {
            const response = await fetch(request.url, {
                headers: { Accept: "text/html, application/xhtml+xml" },
                signal: request.abort.signal,
            });

            if (!this.#isCurrentRequest(request)) return;
            if (!response.ok) throw new Error(`Pagination request failed with ${response.status}`);

            const html = await response.text();
            if (!this.#isCurrentRequest(request)) return;

            replacement = this.appendPage(html);
        } catch (error) {
            if (!this.#isCurrentRequest(request)) return;
            if (error.name === "AbortError") {
                this.#cancelRequest(request, false);

                return;
            }

            this.#finishRequest(request);
            this.element.dataset.state = "error";
            this.clearBusy(request);
            this.announce(this.errorLabelValue);
            this.dispatch("error", { detail: { error } });

            return;
        }

        this.#finishRequest(request);
        try {
            this.restorePosition(anchor, before);
            this.restoreFocus(replacement, shouldRestoreFocus);
            this.announceLoaded(replacement);
            if (manual) this.scrollToTarget();
        } catch (error) {
            // The page is already appended; retrying the request could duplicate results.
            this.application.handleError(error, "Error restoring pagination UI", { controller: this });
        }
    }

    #appendDestination() {
        return this.explicitAppendTarget() || this.appendTargetFrom(this.currentPaginationBoundary(null));
    }

    #watchRequest(request) {
        const { link, destination } = request;
        request.onMorph = ({ target }) => {
            if (target === destination) this.#cancelRequest(request);
        };
        request.onStream = ({ detail }) => {
            const { newStream, render } = detail ?? {};
            if (typeof render !== "function" || newStream?.getAttribute("action") !== "update"
                || !newStream.targetElements.includes(destination)) return;

            // Stream updates morph children without emitting a morph event on the list itself.
            // Invalidate at render time so a prevented Stream leaves the request alone.
            const controller = this;
            detail.render = function (stream) {
                if (stream.targetElements.includes(destination)) {
                    controller.#cancelRequest(request);
                }

                return render.call(this, stream);
            };
        };
        if (destination) {
            destination.addEventListener("turbo:morph-element", request.onMorph);
            destination.ownerDocument.addEventListener("turbo:before-stream-render", request.onStream);
        }

        request.observer = new MutationObserver(() => {
            this.#isCurrentRequest(request);
        });
        request.observer.observe(link, { attributeFilter: ["href"] });
    }

    #isCurrentRequest(request) {
        if (this.#request !== request) return false;
        if (this.element.isConnected
            && this.nextTargets.includes(request.link)
            && request.link.href === request.url
            && this.#appendDestination() === request.destination) return true;

        this.#cancelRequest(request);

        return false;
    }

    #finishRequest(request) {
        this.#request = null;
        request.observer.disconnect();
        const { destination } = request;
        if (destination) {
            destination.removeEventListener("turbo:morph-element", request.onMorph);
            destination.ownerDocument.removeEventListener("turbo:before-stream-render", request.onStream);
        }
    }

    #cancelRequest(request, resume = true) {
        if (this.#request !== request) return;

        this.#finishRequest(request);
        this.clearBusy(request);
        if (this.element.dataset.state === "loading") delete this.element.dataset.state;
        if (this.hasStatusTarget && this.statusTarget.textContent === request.loadingLabel) this.announce("");

        request.abort.abort();
        if (resume) this.observeNextLink();
    }

    observeNextLink() {
        if (this.observer || this.#request || !this.element.isConnected
            || !this.infiniteValue || typeof IntersectionObserver === "undefined" || !this.hasNextTarget) return;

        const link = this.nextTarget;
        const observer = new IntersectionObserver((entries) => {
            if (this.observer !== observer || link !== this.nextTargets[0]) return;
            if (entries.some((entry) => entry.isIntersecting)) this.loadNext(link);
        }, {
            rootMargin: this.rootMarginValue,
            threshold: this.thresholdValue,
        });

        this.observer = observer;
        this.observedLink = link;
        observer.observe(link);
    }

    stopObserver() {
        this.observer?.disconnect();
        this.observer = null;
        this.observedLink = null;
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
            && !parent.contains(appendTarget)
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
        if (this.loadingLabelValue !== "") {
            link.setAttribute("aria-label", this.loadingLabelValue);
        }
        this.announce(this.loadingLabelValue);
    }

    clearBusy({ link, originalLabel, loadingLabel }) {
        this.element.removeAttribute("aria-busy");
        link.removeAttribute("aria-busy");

        // A morph may already have installed a different label.
        if (loadingLabel === "" || link.getAttribute("aria-label") !== loadingLabel) return;
        if (originalLabel === null) link.removeAttribute("aria-label");
        else link.setAttribute("aria-label", originalLabel);
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
