// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { isComposing } from "./_composition.js";

function normalizeDigits(value) {
    return (value.match(/\p{Nd}/gu) ?? []).map(digit => {
        const point = digit.codePointAt(0);
        if (point >= 48 && point <= 57) return digit;
        let start = point;
        // Unicode decimal runs contain one or more consecutive sets of ten ordered digits.
        while (/\p{Nd}/u.test(String.fromCodePoint(start - 1))) start--;
        return String((point - start) % 10);
    }).join("");
}

export default class extends Controller {
    static targets = ["input", "slots", "slot"];

    initialize() {
        this.onSelectionChange = () => {
            if (this.hasInputTarget && (this.element.ownerDocument.activeElement === this.inputTarget || this.selectionVisible)) this.render();
        };
        this.onReset = (event) => {
            clearTimeout(this.resetTimer);
            this.resetTimer = setTimeout(() => {
                if (!event.defaultPrevented) this.refresh();
            }, 0);
        };
    }

    connect() {
        this.refresh();
        this.element.ownerDocument.addEventListener("selectionchange", this.onSelectionChange);
    }

    disconnect() {
        this.endSelection();
        clearTimeout(this.resetTimer);
        this.form?.removeEventListener("reset", this.onReset);
        this.form = null;
        this.element.ownerDocument.removeEventListener("selectionchange", this.onSelectionChange);
        delete this.element.dataset.enhanced;
        if (this.hasSlotsTarget) this.slotsTarget.hidden = true;
        for (const slot of this.slotTargets) {
            slot.textContent = "";
            slot.removeAttribute("data-active");
            slot.removeAttribute("data-selected");
        }
    }

    inputTargetConnected() {
        if (this.element.dataset.enhanced === "true") this.refresh();
    }

    refresh(event) {
        if (event && event.target !== this.element) return;
        if (!this.hasInputTarget || !this.hasSlotsTarget) return;
        const form = this.inputTarget.form;
        if (form !== this.form) {
            this.form?.removeEventListener("reset", this.onReset);
            this.form = form;
            this.form?.addEventListener("reset", this.onReset);
        }
        this.previousValue = this.inputTarget.value;
        this.render();
        this.slotsTarget.hidden = false;
        this.element.dataset.enhanced = "true";
    }

    input(event) {
        if (event.target !== this.inputTarget || isComposing(event)) return;
        const input = this.inputTarget;
        if (input.disabled || input.readOnly) return;

        const value = normalizeDigits(input.value).slice(0, this.length);
        if (value !== input.value) {
            const start = normalizeDigits(input.value.slice(0, input.selectionStart)).length;
            const end = normalizeDigits(input.value.slice(0, input.selectionEnd)).length;
            input.value = value;
            input.setSelectionRange(Math.min(start, value.length), Math.min(end, value.length));
        }
        const changed = value !== this.previousValue;
        this.previousValue = value;
        this.render();
        if (changed && this.length > 0 && value.length === this.length) this.dispatch("complete");
    }

    paste(event) {
        const input = this.inputTarget;
        if (event.defaultPrevented || input.disabled || input.readOnly || !event.clipboardData) return;
        const digits = normalizeDigits(event.clipboardData.getData("text"));
        event.preventDefault();
        if (!digits) return;

        const fullCode = digits.length >= this.length;
        input.setRangeText(digits.slice(0, this.length), fullCode ? 0 : input.selectionStart, fullCode ? input.value.length : input.selectionEnd, "end");
        input.dispatchEvent(new Event("input", { bubbles: true }));
    }

    get length() {
        return this.inputTarget.maxLength >= 0 ? this.inputTarget.maxLength : this.slotTargets.length;
    }

    navigate(event) {
        const input = this.inputTarget;
        if (event.defaultPrevented || isComposing(event) || event.ctrlKey || event.metaKey || event.altKey) return;
        if (input.value.length !== this.length) return;
        const arrow = event.key === "ArrowLeft" || event.key === "ArrowRight";
        if (!arrow && !(event.shiftKey && (event.key === "Home" || event.key === "End"))) return;

        const step = event.key === "ArrowLeft" ? -1 : 1;
        if (event.shiftKey) {
            event.preventDefault();
            const backward = input.selectionDirection === "backward";
            const last = Math.max(input.selectionStart, input.selectionEnd - 1);
            const anchor = backward ? last : input.selectionStart;
            const focus = backward ? input.selectionStart : last;
            const index = event.key === "Home" ? 0 : event.key === "End" ? input.value.length : focus + step;
            this.selectRange(anchor, Math.max(0, Math.min(index, input.value.length)));
            this.render();
            return;
        }
        if (input.selectionEnd - input.selectionStart > 1) return;

        event.preventDefault();
        const index = Math.max(0, Math.min(input.selectionStart + step, input.value.length));
        input.setSelectionRange(index, index);
        this.render();
    }

    placeCaret(event) {
        const input = this.inputTarget;
        if (event.defaultPrevented || event.button !== 0 || event.isPrimary === false || input.matches(":disabled")) return;
        const index = this.pointerIndex(event.clientX);
        if (index === null) return;

        // Resolve selection before native pointer focus can paint a caret from the transparent input's geometry.
        event.preventDefault();
        const start = Math.min(index, input.value.length);
        const anchor = event.shiftKey
            ? (input.selectionDirection === "backward" && input.selectionEnd > input.selectionStart ? input.selectionEnd - 1 : input.selectionStart)
            : start;
        this.pointerSelection = { id: event.pointerId, input, anchor, start };
        if (event.shiftKey) this.selectRange(anchor, start);
        else input.setSelectionRange(start, Math.min(start + 1, input.value.length));

        input.focus({ preventScroll: true });
        input.setPointerCapture(event.pointerId);
        this.render();
    }

    extendSelection(event) {
        const pointer = this.pointerSelection;
        if (!pointer || event.pointerId !== pointer.id) return;
        const index = this.pointerIndex(event.clientX);
        if (index === null || (index === pointer.start && !pointer.dragged)) return;

        pointer.dragged = true;
        this.selectRange(pointer.anchor, Math.min(index, pointer.input.value.length));
        this.render();
    }

    endSelection(event) {
        const pointer = this.pointerSelection;
        if (!pointer || (event && event.pointerId !== pointer.id)) return;

        this.pointerSelection = null;
        if (pointer.input.hasPointerCapture(pointer.id)) pointer.input.releasePointerCapture(pointer.id);
    }

    pointerIndex(clientX) {
        const rects = this.slotTargets.map(slot => slot.getBoundingClientRect()).filter(rect => rect.width > 0);
        if (!rects.length) return null;
        const index = rects.findIndex(rect => clientX <= rect.right);
        return index === -1 ? rects.length - 1 : index;
    }

    selectRange(anchor, index) {
        this.inputTarget.setSelectionRange(
            Math.min(anchor, index),
            Math.min(Math.max(anchor, index) + 1, this.inputTarget.value.length),
            index < anchor ? "backward" : "forward",
        );
    }

    render(event) {
        if (!this.hasInputTarget) return;
        const input = this.inputTarget;
        const focused = this.element.ownerDocument.activeElement === input;
        const start = input.selectionStart ?? 0;
        let end = input.selectionEnd ?? start;
        // Let native focus finish restoring selection before selecting a replacement digit.
        if (event?.type !== "focus" && focused && input.value.length === this.length && start === end && start < input.value.length) {
            end = start + 1;
            input.setSelectionRange(start, end);
        }
        const active = Math.min(start, this.slotTargets.length - 1);
        this.selectionVisible = focused;

        this.slotTargets.forEach((slot, index) => {
            slot.textContent = input.value[index] ?? "";
            slot.toggleAttribute("data-active", focused && index === active);
            slot.toggleAttribute("data-selected", focused && index >= start && index < end);
        });
    }
}
