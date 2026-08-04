// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { isComposing } from "./_composition.js";

export default class extends Controller {
    static values = {
        locale: { type: String, default: "en-US" },
        currency: String,
        prefix: String,
        suffix: String,
        fraction: { type: Number, default: 2 },
        unsigned: Boolean,
        eager: Boolean,
        hiddenId: String,
    };

    connect() {
        this.connected = true;
        this.resyncAfterMorph = this.resyncAfterMorph.bind(this);
        this.affixes = this.#resolveAffixes();
        this.digitBuffer = this.#digitBufferFromMinor(this.element.value);
        this.#bindEvents();
        this.#renderCurrentValue(false);
        document.addEventListener("turbo:render", this.resyncAfterMorph);
    }

    disconnect() {
        this.connected = false;
        this.#unbindEvents();
        document.removeEventListener("turbo:render", this.resyncAfterMorph);
        this.affixes = null;
        this.digitBuffer = null;
        this.rebuildQueued = false;
    }

    resyncAfterMorph() {
        this.digitBuffer = this.#digitBufferFromMinor(this.element.value);
        this.#renderCurrentValue(false);
    }

    localeValueChanged() {
        this.#scheduleRebuild();
    }

    currencyValueChanged() {
        this.#scheduleRebuild();
    }

    prefixValueChanged() {
        this.#scheduleRebuild();
    }

    suffixValueChanged() {
        this.#scheduleRebuild();
    }

    fractionValueChanged() {
        this.#scheduleRebuild();
    }

    unsignedValueChanged() {
        this.#scheduleRebuild();
    }

    eagerValueChanged() {
        this.#scheduleRebuild();
    }

    #bindEvents() {
        this.onKeydown = (event) => this.#handleKeydown(event);
        this.onInput = (event) => this.#handleInput(event);
        this.onCompositionEnd = () => this.#handleCompositionEnd();
        this.onPaste = (event) => this.#handlePaste(event);

        this.element.addEventListener("keydown", this.onKeydown);
        this.element.addEventListener("input", this.onInput, true);
        this.element.addEventListener("compositionend", this.onCompositionEnd);
        this.element.addEventListener("paste", this.onPaste);
    }

    #unbindEvents() {
        this.element.removeEventListener("keydown", this.onKeydown);
        this.element.removeEventListener("input", this.onInput, true);
        this.element.removeEventListener("compositionend", this.onCompositionEnd);
        this.element.removeEventListener("paste", this.onPaste);
        this.#clearCompositionTimer();

        this.onKeydown = null;
        this.onInput = null;
        this.onCompositionEnd = null;
        this.onPaste = null;
    }

    #scheduleRebuild() {
        if (!this.connected || this.rebuildQueued) {
            return;
        }

        this.rebuildQueued = true;

        queueMicrotask(() => {
            this.rebuildQueued = false;
            if (!this.connected) return;

            this.affixes = this.#resolveAffixes();
            this.#renderCurrentValue(false);
        });
    }

    #handleKeydown(event) {
        if (isComposing(event)) return;

        if (event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }

        const allowedKeys = new Set([
            "Tab",
            "ArrowLeft",
            "ArrowRight",
            "ArrowUp",
            "ArrowDown",
            "Home",
            "End",
            "Enter",
            "Escape",
        ]);
        const selectedAll = this.#isAllSelected();

        if (/^\d$/.test(event.key)) {
            event.preventDefault();
            const currentDigits = selectedAll ? "" : this.digitBuffer ?? "";
            this.digitBuffer = `${currentDigits}${event.key}`;
            this.#renderFromDigitBuffer();

            return;
        }

        if (event.key === "Backspace") {
            event.preventDefault();
            this.digitBuffer = selectedAll ? "" : (this.digitBuffer ?? "").slice(0, -1);
            this.#renderFromDigitBuffer();

            return;
        }

        if (event.key === "Delete") {
            event.preventDefault();
            this.digitBuffer = "";
            this.#renderFromDigitBuffer();

            return;
        }

        if (allowedKeys.has(event.key)) {
            return;
        }

        if (event.key.length === 1) {
            event.preventDefault();
        }
    }

    #handleInput(event) {
        if (this.internalInputEvent || isComposing(event)) {
            return;
        }

        this.#clearCompositionTimer();
        event.stopImmediatePropagation();
        this.#commitInput();
    }

    #handleCompositionEnd() {
        this.#clearCompositionTimer();
        this.compositionTimeout = setTimeout(() => {
            this.compositionTimeout = null;
            if (this.element.isConnected) this.#commitInput();
        }, 0);
    }

    #clearCompositionTimer() {
        clearTimeout(this.compositionTimeout);
        this.compositionTimeout = null;
    }

    #commitInput() {
        const raw = this.#rawValueFromDisplay(this.element.value);

        this.digitBuffer = this.#digitBufferFromRaw(raw);
        this.#renderCurrentValue();
    }

    #handlePaste(event) {
        event.preventDefault();

        const pasted = event.clipboardData?.getData("text") ?? "";
        const raw = this.#rawValueFromDisplay(pasted);
        const pastedDigits = this.#digitBufferFromRaw(raw);
        this.digitBuffer = this.#isAllSelected()
            ? pastedDigits
            : `${this.digitBuffer ?? ""}${pastedDigits}`;
        this.#renderCurrentValue();
    }

    #renderCurrentValue(dispatch = true) {
        this.#renderFromDigitBuffer(dispatch);
    }

    #renderFromDigitBuffer(dispatch = true) {
        const digits = this.digitBuffer ?? "";

        if (digits === "") {
            this.#setElementValue("", dispatch);

            return;
        }

        const negative = !this.unsignedValue && digits.startsWith("-");
        const numericDigits = digits.replace(/\D/g, "");
        const fractionLength = this.fractionValue;
        const padded = numericDigits.padStart(fractionLength + 1, "0");
        const integerDigits = fractionLength > 0
            ? (padded.slice(0, -fractionLength) || "0")
            : padded;
        const fractionDigits = fractionLength > 0 ? padded.slice(-fractionLength) : "";
        const groupSep = this.#groupSeparator();
        const decimalSep = fractionLength > 0 ? this.#decimalSeparator() : "";
        const groupedInteger = integerDigits.replace(/\B(?=(\d{3})+(?!\d))/g, groupSep);
        const innerNumber = `${groupedInteger}${decimalSep}${fractionDigits}`;
        const visible = this.#wrap(`${negative ? "-" : ""}${innerNumber}`);

        this.#setElementValue(visible, dispatch);
    }

    #setElementValue(masked, dispatch = true) {
        this.internalInputEvent = true;
        this.element.value = masked;
        this.#syncHidden();
        this.#moveCaretToEnd();
        if (dispatch) this.#dispatchEvents(masked);
        this.internalInputEvent = false;
    }

    #syncHidden() {
        if (!this.hasHiddenIdValue) {
            return;
        }

        const target = document.getElementById(this.hiddenIdValue);

        if (target) {
            target.value = this.digitBuffer ?? "";
        }
    }

    #dispatchEvents(masked) {
        const minor = this.digitBuffer ?? "";
        const detail = {
            masked,
            unmasked: minor,
            completed: masked !== "",
        };

        this.element.dispatchEvent(new CustomEvent("money-input:change", { detail }));
        this.element.dispatchEvent(new CustomEvent("input", { bubbles: true, detail: masked }));
    }

    #rawValueFromDisplay(value) {
        const stripped = this.#strip(value);
        const decimal = this.#decimalSeparator();
        const group = this.#groupSeparator();
        const negative = !this.unsignedValue && stripped.trim().startsWith("-") ? "-" : "";
        const normalized = stripped
            .trim()
            .replace(/^-/, "")
            .split(group).join("")
            .replace(decimal, ".")
            .replace(/[^\d.]/g, "")
            .replace(/(\..*)\./g, "$1");

        if (normalized === "") {
            return "";
        }

        if (!normalized.includes(".")) {
            return `${negative}${normalized}`;
        }

        const [integer, fraction = ""] = normalized.split(".");

        return `${negative}${integer || "0"}.${fraction.slice(0, this.fractionValue)}`;
    }

    #digitBufferFromMinor(value) {
        if (value == null || value === "") {
            return "";
        }

        const trimmed = String(value).trim();

        if (trimmed === "" || trimmed === "-") {
            return "";
        }

        const negative = !this.unsignedValue && trimmed.startsWith("-") ? "-" : "";
        const digits = trimmed.replace(/\D/g, "").replace(/^0+(?=\d)/, "");

        return digits === "" ? "" : `${negative}${digits}`;
    }

    #digitBufferFromRaw(raw) {
        if (raw === "") {
            return "";
        }

        const negative = !this.unsignedValue && raw.startsWith("-") ? "-" : "";
        const unsigned = negative ? raw.slice(1) : raw;

        if (this.fractionValue <= 0) {
            const integerDigits = unsigned.replace(/\D/g, "").replace(/^0+(?=\d)/, "");

            return `${negative}${integerDigits}`;
        }

        const [integer = "0", fraction = ""] = unsigned.split(".");
        const integerDigits = integer.replace(/\D/g, "");
        const fractionDigits = fraction.replace(/\D/g, "").padEnd(this.fractionValue, "0").slice(0, this.fractionValue);
        const combined = `${integerDigits}${fractionDigits}`.replace(/^0+(?=\d)/, "");

        return `${negative}${combined}`;
    }

    #resolveAffixes() {
        if (this.hasPrefixValue || this.hasSuffixValue) {
            return {
                prefix: this.hasPrefixValue ? this.prefixValue : "",
                suffix: this.hasSuffixValue ? this.suffixValue : "",
            };
        }

        if (!this.hasCurrencyValue) {
            return { prefix: "", suffix: "" };
        }

        return this.#deriveFromCurrency();
    }

    #deriveFromCurrency() {
        let parts;

        try {
            parts = new Intl.NumberFormat(this.localeValue, {
                style: "currency",
                currency: this.currencyValue,
                currencyDisplay: "symbol",
            }).formatToParts(1);
        } catch {
            return { prefix: "", suffix: "" };
        }

        const symbolIndex = parts.findIndex((part) => part.type === "currency");

        if (symbolIndex === -1) {
            return { prefix: "", suffix: "" };
        }

        const symbol = parts[symbolIndex].value;
        const adjacent = (offset) => {
            const part = parts[symbolIndex + offset];
            return part?.type === "literal" ? part.value : "";
        };

        return symbolIndex === 0
            ? { prefix: symbol + adjacent(1), suffix: "" }
            : { prefix: "", suffix: adjacent(-1) + symbol };
    }

    #strip(value) {
        let out = value;
        const { prefix, suffix } = this.affixes;

        if (prefix && out.startsWith(prefix)) {
            out = out.slice(prefix.length);
        }

        if (suffix && out.endsWith(suffix)) {
            out = out.slice(0, -suffix.length);
        }

        return out;
    }

    #wrap(value) {
        if (value === "") {
            return "";
        }

        const { prefix, suffix } = this.affixes;
        const negative = value.startsWith("-");
        const unsigned = negative ? value.slice(1) : value;

        return `${negative ? "-" : ""}${prefix}${unsigned}${suffix}`;
    }

    #decimalSeparator() {
        const decimal = new Intl.NumberFormat(this.localeValue)
            .formatToParts(1.1)
            .find((part) => part.type === "decimal");

        return decimal?.value ?? ".";
    }

    #groupSeparator() {
        const group = new Intl.NumberFormat(this.localeValue)
            .formatToParts(1000)
            .find((part) => part.type === "group");

        return group?.value ?? ",";
    }

    #moveCaretToEnd() {
        const end = this.element.value.length;

        this.element.setSelectionRange?.(end, end);
    }

    #isAllSelected() {
        return this.element.selectionStart === 0
            && this.element.selectionEnd === this.element.value.length
            && this.element.value.length > 0;
    }
}
