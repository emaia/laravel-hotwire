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
        if (!this.isConnected || !this.enabledValue || !this.frameValue) {
            return;
        }

        this.cancelRefresh();

        this.timeoutId = setTimeout(() => {
            if (this.isConnected && this.enabledValue) {
                this.performRefresh();
            }
        }, this.timeoutValue);
    }

    cancelRefresh() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    }

    performRefresh() {
        try {
            Turbo.visit(window.location.href, {
                frame: this.frameValue,
                action: "replace",
            });
        } catch (error) {
            console.error("Turbo frame refresh failed:", error);
            if (this.isConnected) {
                this.scheduleRefresh();
            }
        }
    }

    refresh() {
        this.cancelRefresh();
        this.performRefresh();
        if (this.enabledValue) {
            this.scheduleRefresh();
        }
    }
}
