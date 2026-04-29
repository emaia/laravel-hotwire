import { Controller } from "@hotwired/stimulus";

function throttle(fn, ms) {
    let id;
    return (...args) => {
        if (!id) {
            fn(...args);
            id = setTimeout(() => {
                id = null;
            }, ms);
        }
    };
}

export default class extends Controller {
    static values = {
        throttleDelay: {
            type: Number,
            default: 15,
        },
    };

    initialize() {
        this.onScroll = this.onScroll.bind(this);
    }

    connect() {
        this.update =
            this.throttleDelayValue > 0
                ? throttle(this.onScroll, this.throttleDelayValue)
                : this.onScroll;

        window.addEventListener("scroll", this.update, { passive: true });
        this.onScroll();
    }

    disconnect() {
        window.removeEventListener("scroll", this.update);
    }

    onScroll() {
        const height =
            document.documentElement.scrollHeight -
            document.documentElement.clientHeight;
        const width = (window.scrollY / height) * 100;

        this.element.style.width = `${width}%`;
    }
}