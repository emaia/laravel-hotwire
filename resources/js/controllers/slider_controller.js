// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.rafId = null;
        this.boundReset = this.reset.bind(this);
        this.boundRefresh = this.refresh.bind(this);
        this.form = null;
        this.bindForm();
        this.element.addEventListener("turbo:morph-element", this.boundRefresh);

        this.update();
    }

    disconnect() {
        this.form?.removeEventListener("reset", this.boundReset);
        this.element.removeEventListener("turbo:morph-element", this.boundRefresh);

        if (this.rafId !== null) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
    }

    update() {
        const min = this.numberAttribute("min", 0);
        const max = this.numberAttribute("max", 100);
        const nativeValue = this.element.valueAsNumber;
        const value = Number.isFinite(nativeValue) ? nativeValue : (min + max) / 2;
        const percent = max <= min ? 0 : Math.min(100, Math.max(0, ((value - min) / (max - min)) * 100));

        this.element.style.setProperty("--slider-value", `${percent}%`);
    }

    refresh() {
        this.bindForm();
        this.update();
    }

    reset() {
        if (this.rafId !== null) cancelAnimationFrame(this.rafId);

        this.rafId = requestAnimationFrame(() => {
            this.rafId = null;
            this.update();
        });
    }

    bindForm() {
        const form = this.element.form;
        if (form === this.form) return;

        this.form?.removeEventListener("reset", this.boundReset);
        this.form = form;
        this.form?.addEventListener("reset", this.boundReset);
    }

    numberAttribute(name, fallback) {
        const value = this.element.getAttribute(name)?.trim();
        if (!value || !/^-?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i.test(value)) return fallback;

        const number = Number(value);

        return Number.isFinite(number) ? number : fallback;
    }
}
