// @hotwire-package
import * as Turbo from "@hotwired/turbo";
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        frame: String,
        timeout: { type: Number, default: 5000 },
        enabled: { type: Boolean, default: true },
    };

    initialize() {
        this.timeoutId = null;
        this.isConnected = false;
    }

    connect() {
        this.isConnected = true;
        this.scheduleRefresh();
    }

    disconnect() {
        this.isConnected = false;
        this.cancelRefresh();
    }

    enabledValueChanged() {
        if (this.enabledValue) {
            this.scheduleRefresh();
        } else {
            this.cancelRefresh();
        }
    }

    timeoutValueChanged() {
        if (this.enabledValue && this.isConnected) {
            this.cancelRefresh();
            this.scheduleRefresh();
        }
    }

    scheduleRefresh() {
        if (!this.isConnected || !this.enabledValue || !this.frameIdentifier) {
            return;
        }

        this.cancelRefresh();

        this.timeoutId = this.setRefreshTimer(() => {
            this.timeoutId = null;

            if (this.isConnected && this.enabledValue) {
                this.performRefresh();
                this.scheduleRefresh();
            }
        }, this.timeoutValue);
    }

    cancelRefresh() {
        if (this.timeoutId) {
            this.clearRefreshTimer(this.timeoutId);
            this.timeoutId = null;
        }
    }

    setRefreshTimer(callback, timeout) {
        return setTimeout(callback, timeout);
    }

    clearRefreshTimer(timeoutId) {
        clearTimeout(timeoutId);
    }

    performRefresh() {
        try {
            if (this.targetFrame?.reload) {
                this.targetFrame.reload();
                return;
            }

            Turbo.visit(this.visitUrl, {
                frame: this.frameIdentifier,
                action: "replace",
            });
        } catch (error) {
            console.error("Turbo frame refresh failed:", error);
        }
    }

    refresh() {
        this.cancelRefresh();
        this.performRefresh();
        if (this.enabledValue) {
            this.scheduleRefresh();
        }
    }

    get frameIdentifier() {
        if (this.hasFrameValue && this.frameValue) return this.frameValue;
        if (this.isFrameElement) return this.element.id;

        return "";
    }

    get targetFrame() {
        if (!this.frameIdentifier) return null;
        if (this.isFrameElement && this.element.id === this.frameIdentifier) return this.element;

        return document.getElementById(this.frameIdentifier);
    }

    get visitUrl() {
        return this.targetFrame?.getAttribute("src") || window.location.href;
    }

    get isFrameElement() {
        return this.element.tagName === "TURBO-FRAME";
    }
}
