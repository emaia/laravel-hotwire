import { Controller } from "@hotwired/stimulus";
import { MaskInput } from "maska";

export default class extends Controller {
    static values = {
        mask: String,
        reversed: Boolean,
        isMoney: {
            type: Boolean,
            default: false,
        },
    };

    connect() {
        this.element.addEventListener("maska", this.onMaska);

        let mask = null;

        try {
            mask = JSON.parse(this.maskValue);
        } catch (e) {
            mask = this.maskValue;
        }

        new MaskInput(this.element, {
            mask: mask,
            reversed: !!this.reversedValue,
            tokens: {
                9: { pattern: /9/ },
                S: { pattern: /[a-zA-ZÀ-ÿ\u00C0-\u00FF\s]/, repeated: true },
            },
            preProcess: (val) => {
                if (this.isMoneyValue) {
                    // return val.replace("R$ ", "");
                }
                return val;
            },
            postProcess: (val) => {
                if (this.isMoneyValue) {
                    // return val.replace("R$ ", "");
                }
                return val;
            },
        });
    }
}
