// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        threshold: { type: Number, default: 400 },
    };

    connect() {
        this.onScroll = this.onScroll.bind(this);
        this.rafId = null;

        this.update();
        window.addEventListener("scroll", this.onScroll, { passive: true });
    }

    disconnect() {
        window.removeEventListener("scroll", this.onScroll);
        if (this.rafId !== null) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
    }

    scrollToTop() {
        const reduce = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false;
        window.scrollTo({ top: 0, left: 0, behavior: reduce ? "auto" : "smooth" });
    }

    onScroll() {
        if (this.rafId !== null) return;

        this.rafId = requestAnimationFrame(() => {
            this.rafId = null;
            this.update();
        });
    }

    update() {
        const visible = window.scrollY > this.thresholdValue;
        this.element.setAttribute("data-visible", visible ? "true" : "false");
    }
}
