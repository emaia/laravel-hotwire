// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { skipInitial: Boolean };

    connect() {
        // Server-rendered content counts as the initial render, so a host that
        // already holds markup transitions on its very next navigation.
        this.hasRenderedFrame = this.element.innerHTML.trim() !== "";
        this.handleBeforeRender = this.#beforeRender.bind(this);
        this.handleBeforeFetchRequest = this.#beforeFetchRequest.bind(this);
        this.element.addEventListener(
            "turbo:before-frame-render",
            this.handleBeforeRender,
        );
        this.element.addEventListener(
            "turbo:before-fetch-request",
            this.handleBeforeFetchRequest,
        );
    }

    disconnect() {
        this.element.removeEventListener(
            "turbo:before-frame-render",
            this.handleBeforeRender,
        );
        this.element.removeEventListener(
            "turbo:before-fetch-request",
            this.handleBeforeFetchRequest,
        );
    }

    #beforeFetchRequest(event) {
        if (event.target === this.element && this.element.innerHTML.trim() === "") {
            this.hasRenderedFrame = false;
        }
    }

    #beforeRender(event) {
        const skipTransition = this.skipInitialValue && !this.hasRenderedFrame;
        this.hasRenderedFrame = true;

        if (skipTransition) return;
        if (!document.startViewTransition) return;

        const originalRender = event.detail.render;

        event.detail.render = (currentElement, newElement) => {
            document.startViewTransition(() =>
                originalRender(currentElement, newElement),
            );
        };
    }
}
