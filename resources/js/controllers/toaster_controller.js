import { Controller } from "@hotwired/stimulus";
import { createToaster } from "@emaia/sonner/vanilla";

export default class extends Controller {
    static values = {
        autoDisconnect: { type: Boolean, default: false },
        closeButton: { type: Boolean, default: true },
        duration: { type: Number, default: 4000 },
        expand: { type: Boolean, default: false },
        invert: { type: Boolean, default: false },
        position: { type: String, default: "bottom-center" },
        richColors: { type: Boolean, default: true },
        theme: { type: String, default: "light" },
        visibleToasts: { type: Number, default: 3 },
        gap: { type: Number, default: 0 },
        hotkey: { type: String, default: "" },
        dir: { type: String, default: "" },
        offset: { type: String, default: "" },
        mobileOffset: { type: String, default: "" },
        className: { type: String, default: "" },
        containerAriaLabel: { type: String, default: "" },
        customAriaLabel: { type: String, default: "" },
        swipeDirections: { type: String, default: "" },
    };

    connect() {
        if (window.toaster) return;

        window.toaster = createToaster(this.#buildOptions());
    }

    disconnect() {
        if (this.autoDisconnectValue && window.toaster) {
            window.toaster.destroy();
            window.toaster = null;
        }
    }

    #buildOptions() {
        const options = {
            container: this.element,
            closeButton: this.closeButtonValue,
            duration: this.durationValue,
            expand: this.expandValue,
            invert: this.invertValue,
            position: this.positionValue,
            richColors: this.richColorsValue,
            theme: this.themeValue,
            visibleToasts: this.visibleToastsValue,
        };

        if (this.hasGapValue && this.gapValue > 0) {
            options.gap = this.gapValue;
        }
        if (this.hasHotkeyValue && this.hotkeyValue) {
            options.hotkey = this.#splitList(this.hotkeyValue);
        }
        if (this.hasDirValue && this.dirValue) {
            options.dir = this.dirValue;
        }
        if (this.hasOffsetValue && this.offsetValue) {
            options.offset = this.#parseOffset(this.offsetValue);
        }
        if (this.hasMobileOffsetValue && this.mobileOffsetValue) {
            options.mobileOffset = this.#parseOffset(this.mobileOffsetValue);
        }
        if (this.hasClassNameValue && this.classNameValue) {
            options.className = this.classNameValue;
        }
        if (this.hasContainerAriaLabelValue && this.containerAriaLabelValue) {
            options.containerAriaLabel = this.containerAriaLabelValue;
        }
        if (this.hasCustomAriaLabelValue && this.customAriaLabelValue) {
            options.customAriaLabel = this.customAriaLabelValue;
        }
        if (this.hasSwipeDirectionsValue && this.swipeDirectionsValue) {
            options.swipeDirections = this.#splitList(this.swipeDirectionsValue);
        }

        return options;
    }

    #splitList(value) {
        return value
            .split(/[,\s]+/)
            .map((item) => item.trim())
            .filter(Boolean);
    }

    #parseOffset(value) {
        const trimmed = value.trim();

        if (trimmed.startsWith("{")) {
            try {
                return JSON.parse(trimmed);
            } catch {
                return trimmed;
            }
        }

        return trimmed;
    }
}
