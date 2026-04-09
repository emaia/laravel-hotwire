import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.handleBeforeRender = this.#beforeRender.bind(this);
        this.element.addEventListener(
            "turbo:before-frame-render",
            this.handleBeforeRender,
        );
    }

    disconnect() {
        this.element.removeEventListener(
            "turbo:before-frame-render",
            this.handleBeforeRender,
        );
    }

    #beforeRender(event) {
        if (!document.startViewTransition) return;

        const originalRender = event.detail.render;

        event.detail.render = (currentElement, newElement) => {
            document.startViewTransition(() =>
                originalRender(currentElement, newElement),
            );
        };
    }
}
