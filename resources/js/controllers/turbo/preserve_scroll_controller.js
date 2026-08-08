// @hotwire-package
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
        const originalRender = event.detail.render;

        event.detail.render = (currentElement, newElement) => {
            const scrollTop = window.scrollY;
            const activeElement = document.activeElement;

            if (currentElement.contains(activeElement)) activeElement.blur();

            originalRender(currentElement, newElement);
            this.#restoreScroll(scrollTop);
        };
    }

    #restoreScroll(scrollTop) {
        const root = document.scrollingElement;
        const maxScrollTop = root ? root.scrollHeight - root.clientHeight : scrollTop;

        window.scrollTo(0, Math.max(0, Math.min(scrollTop, maxScrollTop)));
    }
}
