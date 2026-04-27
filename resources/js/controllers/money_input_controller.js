import { Controller } from "@hotwired/stimulus";
import { MaskInput } from "maska";

export default class extends Controller {
    static values = {
        locale: { type: String, default: "en-US" },
        currency: String,
        prefix: String,
        suffix: String,
        fraction: { type: Number, default: 2 },
        unsigned: Boolean,
        eager: Boolean,
    };

    connect() {
        this.affixes = this.#resolveAffixes();
        this.mask = new MaskInput(this.element, this.#options());
    }

    disconnect() {
        this.mask?.destroy();
        this.mask = null;
        this.affixes = null;
    }

    localeValueChanged() {
        this.#rebuild();
    }

    currencyValueChanged() {
        this.#rebuild();
    }

    prefixValueChanged() {
        this.#rebuild();
    }

    suffixValueChanged() {
        this.#rebuild();
    }

    fractionValueChanged() {
        this.#rebuild();
    }

    unsignedValueChanged() {
        this.#rebuild();
    }

    eagerValueChanged() {
        this.mask?.update(this.#options());
    }

    #rebuild() {
        if (!this.mask) {
            return;
        }

        this.affixes = this.#resolveAffixes();
        this.mask.update(this.#options());
    }

    #options() {
        return {
            number: {
                locale: this.localeValue,
                fraction: this.fractionValue,
                unsigned: this.unsignedValue,
            },
            eager: this.eagerValue,
            preProcess: (value) => this.#strip(value),
            postProcess: (value) => this.#wrap(value),
        };
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

        const symbolIndex = parts.findIndex((p) => p.type === "currency");

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
        if (value === "" || value === "-") {
            return value;
        }

        const { prefix, suffix } = this.affixes;
        return `${prefix}${value}${suffix}`;
    }
}
