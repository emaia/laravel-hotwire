// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";

export default class extends Controller {
    static values = {
        delay: {
            type: Number,
            default: 300,
        },
    };

    initialize() {
        this.handleCompositionStart = this.handleCompositionStart.bind(this);
        this.handleCompositionEnd = this.handleCompositionEnd.bind(this);
    }

    connect() {
        this.compositions = new Map();
        this.element.addEventListener("compositionstart", this.handleCompositionStart);
        this.element.addEventListener("compositionend", this.handleCompositionEnd);
    }

    disconnect() {
        this.clearSubmitTimer(this.timeout);
        this.element.removeEventListener("compositionstart", this.handleCompositionStart);
        this.element.removeEventListener("compositionend", this.handleCompositionEnd);
        this.cancelAllCompositions();
    }

    submit(event) {
        if (this.deferForComposition(event, { type: "submit" })) return;

        if (event?.defaultPrevented) {
            this.cancelComposition(event.target);

            return;
        }

        this.cancelAllCompositions();
        this.clearSubmitTimer(this.timeout);
        this.element.requestSubmit();
    }

    debouncedSubmit(event) {
        const delay = this.submitDelay(event);
        if (this.deferForComposition(event, { type: "debounced", delay })) return;

        if (event?.defaultPrevented) {
            this.cancelComposition(event.target);

            return;
        }

        this.cancelAllCompositions();
        this.clearSubmitTimer(this.timeout);

        if (delay <= 0) {
            this.submit();
            return;
        }

        this.timeout = this.setSubmitTimer(() => this.element.requestSubmit(), delay);
    }

    handleCompositionEnd(event) {
        const composition = this.compositions.get(event.target);
        if (!composition) return;

        clearTimeout(composition.timer);
        composition.timer = setTimeout(() => {
            if (this.compositions.get(event.target) !== composition) return;

            this.compositions.delete(event.target);
            this.cancelAllCompositions();
            if (!this.element.contains(event.target)) return;

            this.runCompositionAction(composition.action);
        }, 0);
    }

    handleCompositionStart(event) {
        this.cancelComposition(event.target);
    }

    deferForComposition(event, action) {
        if (!isComposing(event)) return false;

        this.clearSubmitTimer(this.timeout);
        if (event.target) {
            const composition = this.compositions.get(event.target);

            if (event.defaultPrevented) this.cancelComposition(event.target);
            else if (composition) composition.action = action;
            else this.compositions.set(event.target, { action, timer: null });
        }

        return true;
    }

    runCompositionAction(action) {
        if (action.type === "submit" || action.delay <= 0) {
            this.element.requestSubmit();
        } else {
            this.clearSubmitTimer(this.timeout);
            this.timeout = this.setSubmitTimer(() => this.element.requestSubmit(), action.delay);
        }
    }

    cancelComposition(target) {
        if (!target) return;

        clearTimeout(this.compositions.get(target)?.timer);
        this.compositions.delete(target);
    }

    cancelAllCompositions() {
        for (const target of this.compositions.keys()) this.cancelComposition(target);
    }

    submitDelay(event) {
        const param = event?.params?.delay;
        if (param === undefined || param === null || param === "") return this.delayValue;

        const delay = Number(param);

        return Number.isNaN(delay) ? this.delayValue : delay;
    }

    setSubmitTimer(callback, delay) {
        return setTimeout(callback, delay);
    }

    clearSubmitTimer(timeoutId) {
        clearTimeout(timeoutId);
    }
}
