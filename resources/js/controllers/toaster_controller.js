// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createToaster, flushPending, isDetached } from "./_toaster.js";
import { createTopLayer } from "./_top_layer.js";

export default class extends Controller {
    static values = {
        autoDisconnect: { type: Boolean, default: false },
        className: { type: String, default: "" },
        closeButton: { type: Boolean, default: true },
        containerAriaLabel: { type: String, default: "" },
        duration: { type: Number, default: 4000 },
        expand: { type: Boolean, default: false },
        position: { type: String, default: "bottom-center" },
        visibleToasts: { type: Number, default: 3 },
    };

    #topLayer;

    connect() {
        this.#topLayer = createTopLayer(this.element);
        this.#topLayer.show();
        document.addEventListener("hotwire:top-layer:show", this.#handleTopLayerShow);

        // A viewport rendered without data-turbo-permanent is a different element after every Drive
        // visit, and the manager it left behind is still live. Reusing it would hand every later
        // toast to a detached node, so nothing would ever appear again.
        if (isStale(window.toaster)) {
            window.toaster.destroy();
            window.toaster = null;
        }

        if (!isToaster(window.toaster)) {
            window.toaster = this.createToaster(this.#buildOptions());
        }

        flushPending();
    }

    createToaster(options) {
        return createToaster(this.element, options);
    }

    disconnect() {
        document.removeEventListener("hotwire:top-layer:show", this.#handleTopLayerShow);
        this.#topLayer?.cleanup();
        this.#topLayer = null;

        if (this.autoDisconnectValue && isToaster(window.toaster)) {
            window.toaster.destroy();
            window.toaster = null;
        }
    }

    #handleTopLayerShow = (event) => {
        if (event.detail?.element === this.element) return;

        this.#topLayer?.bringToFront();
    };

    #buildOptions() {
        return {
            className: this.classNameValue,
            closeButton: this.closeButtonValue,
            containerAriaLabel: this.containerAriaLabelValue,
            duration: this.durationValue,
            expand: this.expandValue,
            position: this.positionValue,
            visibleToasts: this.visibleToastsValue,
        };
    }
}

/**
 * The container carries id="toaster", and named access on the Window object publishes that element
 * as window.toaster before any script runs. A truthiness check would read the div and skip creating
 * the real instance, leaving every toast to vanish without an error.
 */
function isToaster(value) {
    return Boolean(value) && typeof value.destroy === "function" && value.destroyed !== true;
}

/** An instance whose viewport was removed from the document. Absent element means never stale. */
function isStale(value) {
    return isToaster(value) && isDetached(value);
}
