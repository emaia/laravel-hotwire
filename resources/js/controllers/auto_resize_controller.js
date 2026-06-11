// @hotwire-package
import { Controller } from "@hotwired/stimulus";

function debounce(fn, ms) {
    let id;
    return (...args) => {
        clearTimeout(id);
        id = setTimeout(() => fn(...args), ms);
    };
}

export default class extends Controller {
    static values = {
        resizeDebounceDelay: {
            type: Number,
            default: 100,
        },
    };

    initialize() {
        this.autogrow = this.autogrow.bind(this);
        this.resyncAfterMorph = this.resyncAfterMorph.bind(this);
    }

    connect() {
        const delay = this.resizeDebounceDelayValue;

        this.onResize =
            delay > 0 ? debounce(this.autogrow, delay) : this.autogrow;

        this.resyncAfterMorph();

        this.element.addEventListener("input", this.autogrow);
        window.addEventListener("resize", this.onResize);
        document.addEventListener("turbo:render", this.resyncAfterMorph);
    }

    disconnect() {
        window.removeEventListener("resize", this.onResize);
        document.removeEventListener("turbo:render", this.resyncAfterMorph);
    }

    resyncAfterMorph() {
        this.element.style.overflow = "hidden";
        this.autogrow();
    }

    autogrow() {
        this.element.style.height = "auto"; // Force re-print before calculating the scrollHeight value.
        this.element.style.height = `${this.element.scrollHeight}px`;
    }
}
