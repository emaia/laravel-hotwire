// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import { isComposing } from "./_composition.js";
import { rememberSnapshotState } from "./_snapshot.js";

const CONNECTION_ATTRIBUTE = "data-clear-input-connected";

export default class extends Controller {
    static targets = ["clearButton", "input"];

    #snapshotTargets = new WeakSet();

    initialize() {
        this.handleButtonClick = this.handleButtonClick.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.resyncAfterMorph = this.resyncAfterMorph.bind(this);
        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleFocusOut = this.handleFocusOut.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);
        this.preserveConnection = this.preserveConnection.bind(this);
    }

    connect() {
        rememberSnapshotState(this.element, { attributes: [CONNECTION_ATTRIBUTE] });
        this.resyncAfterMorph();

        this.element.addEventListener("click", this.handleButtonClick);
        this.element.addEventListener("input", this.handleInputChange);
        this.element.addEventListener("focusin", this.handleFocusIn);
        this.element.addEventListener("focusout", this.handleFocusOut);
        this.element.addEventListener("keydown", this.handleKeydown);
        this.element.addEventListener("turbo:before-morph-attribute", this.preserveConnection);
        document.addEventListener("turbo:render", this.resyncAfterMorph);
        window.addEventListener("pageshow", this.resyncAfterMorph);
        this.element.setAttribute(CONNECTION_ATTRIBUTE, "");
    }

    resyncAfterMorph() {
        if (!this.hasInputTarget || !this.hasClearButtonTarget) return;
        // Target callbacks can see siblings before their snapshot baselines have been recorded.
        if (!this.#snapshotTargets.has(this.inputTarget) || !this.#snapshotTargets.has(this.clearButtonTarget)) return;

        if (this.inputTarget.value.length > 0) {
            this.inputTarget.classList.add("clear-input--touched");
        } else {
            this.inputTarget.classList.remove("clear-input--touched");
        }

        this.#syncButtonVisibility();
    }

    handleInputChange(event) {
        if (this.hasInputTarget && event.target === this.inputTarget) {
            this.resyncAfterMorph();
        }
    }

    handleButtonClick(event) {
        if (!this.hasClearButtonTarget || !this.clearButtonTarget.contains(event.target)) return;

        this.#clear(event);
    }

    handleKeydown(event) {
        if (!this.hasInputTarget || event.target !== this.inputTarget) return;
        this.resyncAfterMorph();
        if (event.key !== "Escape" || event.defaultPrevented || isComposing(event)) return;
        if (this.inputTarget.type !== "search" || this.inputTarget.value === "") return;

        if (this.#clear(event)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }

    #clear(event) {
        if (!this.hasInputTarget || !this.hasClearButtonTarget) return false;
        if (this.inputTarget.matches(":disabled") || this.inputTarget.readOnly) return false;

        this.inputTarget.value = "";
        this.inputTarget.focus();
        this.inputTarget.classList.remove("clear-input--touched");
        this.clearButtonTarget.classList.add("hidden");

        this.inputTarget.dispatchEvent(new Event("input", { bubbles: true }));
        this.inputTarget.dispatchEvent(new CustomEvent("inputCleared", { detail: event.detail, bubbles: true }));
        return true;
    }

    handleFocusIn() {
        this.resyncAfterMorph();
    }

    handleFocusOut(event) {
        if (this.hasClearButtonTarget && !this.element.contains(event.relatedTarget)) {
            this.clearButtonTarget.classList.add("hidden");
        }
    }

    inputTargetConnected(input) {
        rememberSnapshotState(input, { classes: ["clear-input--touched"] });
        this.#snapshotTargets.add(input);
        this.resyncAfterMorph();
    }

    inputTargetDisconnected(input) {
        this.#snapshotTargets.delete(input);
        input.classList.remove("clear-input--touched");
        if (this.hasClearButtonTarget) this.clearButtonTarget.classList.add("hidden");
    }

    clearButtonTargetConnected(button) {
        rememberSnapshotState(button, { classes: ["hidden"] });
        this.#snapshotTargets.add(button);
        this.resyncAfterMorph();
    }

    clearButtonTargetDisconnected(button) {
        this.#snapshotTargets.delete(button);
        button.classList.add("hidden");
        if (this.hasInputTarget) this.inputTarget.classList.remove("clear-input--touched");
    }

    preserveConnection(event) {
        if (
            event.target === this.element &&
            event.detail?.attributeName === CONNECTION_ATTRIBUTE &&
            event.detail.mutationType === "remove"
        ) {
            event.preventDefault();
        }
    }

    #syncButtonVisibility() {
        if (!this.hasInputTarget || !this.hasClearButtonTarget) return;

        if (this.inputTarget.value.length > 0 && this.element.contains(document.activeElement)) {
            this.clearButtonTarget.classList.remove("hidden");
        } else if (this.inputTarget.value.length === 0) {
            this.clearButtonTarget.classList.add("hidden");
        }
    }

    disconnect() {
        this.element.removeAttribute(CONNECTION_ATTRIBUTE);
        this.element.removeEventListener("click", this.handleButtonClick);
        this.element.removeEventListener("input", this.handleInputChange);
        this.element.removeEventListener("focusin", this.handleFocusIn);
        this.element.removeEventListener("focusout", this.handleFocusOut);
        this.element.removeEventListener("keydown", this.handleKeydown);
        this.element.removeEventListener("turbo:before-morph-attribute", this.preserveConnection);

        document.removeEventListener("turbo:render", this.resyncAfterMorph);
        window.removeEventListener("pageshow", this.resyncAfterMorph);
    }
}
