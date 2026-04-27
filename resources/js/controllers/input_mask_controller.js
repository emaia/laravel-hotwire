import { Controller } from "@hotwired/stimulus";
import { MaskInput } from "maska";

export default class extends Controller {
    static values = {
        mask: String,
        reversed: Boolean,
        eager: Boolean,
        tokens: String,
        tokensReplace: Boolean,
    };

    connect() {
        this.mask = new MaskInput(this.element, this.#options());
    }

    disconnect() {
        this.mask?.destroy();
        this.mask = null;
    }

    maskValueChanged() {
        this.mask?.update(this.#options());
    }

    reversedValueChanged() {
        this.mask?.update(this.#options());
    }

    eagerValueChanged() {
        this.mask?.update(this.#options());
    }

    tokensValueChanged() {
        this.mask?.update(this.#options());
    }

    tokensReplaceValueChanged() {
        this.mask?.update(this.#options());
    }

    #options() {
        const options = {
            mask: this.#resolveMask(),
            reversed: this.reversedValue,
            eager: this.eagerValue,
        };

        const tokens = this.#resolveTokens();

        if (tokens) {
            options.tokens = tokens;
            options.tokensReplace = this.tokensReplaceValue;
        }

        return options;
    }

    #resolveMask() {
        const raw = this.maskValue;

        if (raw.startsWith("[") && raw.endsWith("]")) {
            try {
                return JSON.parse(raw);
            } catch {
                return raw;
            }
        }

        return raw;
    }

    #resolveTokens() {
        if (!this.hasTokensValue) {
            return null;
        }

        try {
            const parsed = JSON.parse(this.tokensValue);
            return this.#materializeTokens(parsed);
        } catch {
            return null;
        }
    }

    #materializeTokens(parsed) {
        const tokens = {};

        for (const [key, definition] of Object.entries(parsed)) {
            if (!definition || typeof definition.pattern !== "string") {
                continue;
            }

            tokens[key] = {
                ...definition,
                pattern: new RegExp(definition.pattern),
            };
        }

        return tokens;
    }
}
