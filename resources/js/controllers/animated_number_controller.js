import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        start: Number,
        end: Number,
        duration: Number,
        lazyThreshold: Number,
        lazyRootMargin: {
            type: String,
            default: "0px",
        },
        lazy: Boolean,
    };

    connect() {
        this.lazyValue ? this.lazyAnimate() : this.animate();
    }

    animate() {
        let startTimestamp = null;

        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;

            const elapsed = timestamp - startTimestamp;
            const progress = Math.min(elapsed / this.durationValue, 1);

            this.element.innerHTML = Math.floor(
                progress * (this.endValue - this.startValue) + this.startValue,
            ).toString();

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    }

    lazyAnimate() {
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.animate();

                    observer.unobserve(entry.target);
                }
            });
        }, this.lazyAnimateOptions);

        observer.observe(this.element);
    }

    get lazyAnimateOptions() {
        return {
            threshold: this.lazyThresholdValue,
            rootMargin: this.lazyRootMarginValue,
        };
    }
}
