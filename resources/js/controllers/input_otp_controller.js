// @hotwire-package
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "slots", "slot"];

    initialize() {
        this.onSelectionChange = () => this.render();
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

    refresh() {
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
        if (event.target !== this.inputTarget || event.isComposing) return;
        const input = this.inputTarget;
        if (input.disabled || input.readOnly) return;

        const value = input.value.replace(/[^0-9]/g, "").slice(0, input.maxLength);
        if (value !== input.value) {
            const start = input.value.slice(0, input.selectionStart).replace(/[^0-9]/g, "").length;
            const end = input.value.slice(0, input.selectionEnd).replace(/[^0-9]/g, "").length;
            input.value = value;
            input.setSelectionRange(Math.min(start, value.length), Math.min(end, value.length));
        }
        const changed = value !== this.previousValue;
        this.previousValue = value;
        this.render();
        if (changed && value.length === input.maxLength) this.dispatch("complete");
    }

    paste(event) {
        const input = this.inputTarget;
        if (event.defaultPrevented || input.disabled || input.readOnly || !event.clipboardData) return;
        const digits = event.clipboardData.getData("text").replace(/[^0-9]/g, "");
        event.preventDefault();
        if (!digits) return;

        input.setRangeText(digits, input.selectionStart, input.selectionEnd, "end");
        input.dispatchEvent(new Event("input", { bubbles: true }));
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
            ? (input.selectionDirection === "backward" ? input.selectionEnd : input.selectionStart)
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

    render() {
        if (!this.hasInputTarget) return;
        const input = this.inputTarget;
        const focused = this.element.ownerDocument.activeElement === input;
        const start = input.selectionStart ?? 0;
        const end = input.selectionEnd ?? start;
        const active = Math.min(start, this.slotTargets.length - 1);

        this.slotTargets.forEach((slot, index) => {
            slot.textContent = input.value[index] ?? "";
            slot.toggleAttribute("data-active", focused && index === active);
            slot.toggleAttribute("data-selected", focused && index >= start && index < end);
        });
    }
}
