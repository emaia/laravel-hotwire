import { Controller } from "@hotwired/stimulus";
import { createToaster } from "@emaia/sonner/vanilla";

export default class extends Controller {
    static values = {
        autoDisconnect: false,
        closeButton: {
            type: Boolean,
            default: true,
        },
        duration: {
            type: Number,
            default: 4000,
        },
        expand: {
            type: Boolean,
            default: false,
        },
        invert: {
            type: Boolean,
            default: false,
        },
        position: {
            type: String,
            default: "bottom-center",
        },
        richColors: {
            type: Boolean,
            default: true,
        },
        theme: {
            type: String,
            default: "light",
        },
        visibleToasts: {
            type: Number,
            default: 3,
        },
    };

    connect() {
        if (!window.toaster) {
            window.toaster = createToaster({
                closeButton: this.closeButtonValue,
                container: this.element,
                duration: this.durationValue,
                expand: this.expandValue,
                invert: this.invertValue,
                position: this.positionValue,
                richColors: this.richColorsValue,
                theme: this.themeValue,
                visibleToasts: this.visibleToastsValue,
            });
        }
    }

    disconnect() {
        if (this.autoDisconnectValue && window.toaster) {
            window.toaster.destroy();
        }
    }
}
