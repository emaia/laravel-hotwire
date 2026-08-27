// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";

export default class AlertDialogController extends Controller {
    static targets = ["modal", "backdrop", "dialog"];

    static classes = ["lockScroll"];

    static values = {
        lockScroll: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    pendingElement = null;
    confirmed = false;
    overlay = null;
    connected = false;
    overlayRefreshQueued = false;

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.connected = true;
        this.#setupOverlay();
    }

    disconnect() {
        this.connected = false;
        this.overlayRefreshQueued = false;
        this.overlay?.cleanup();
        this.overlay = null;
    }

    modalTargetConnected() {
        this.#queueOverlayRefresh();
    }

    modalTargetDisconnected() {
        this.#queueOverlayRefresh();
    }

    backdropTargetConnected() {
        this.#queueOverlayRefresh();
    }

    backdropTargetDisconnected() {
        this.#queueOverlayRefresh();
    }

    dialogTargetConnected() {
        this.#queueOverlayRefresh();
    }

    dialogTargetDisconnected() {
        this.#queueOverlayRefresh();
    }

    #setupOverlay(forceOpen = false, stackPosition = null, topLayerPosition = null) {
        this.overlay = createOverlay(this, {
            modalTarget: this.modalTarget,
            backdropTarget: this.backdropTarget,
            dialogTarget: this.dialogTarget,
            accessibilityPrefix: this.identifier,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            closeOnEscape: true,
            escapeCapture: true,
            stopEscapePropagation: true,
            onEscape: () => this.cancel(),
            getTriggerElement: () => this.pendingElement,
        });

        if (forceOpen) {
            this.overlay.setOpen({ notify: false, stackPosition, topLayerPosition });
        } else if (this.modalTarget.dataset.state === "open" && !this.modalTarget.hidden) {
            this.overlay.setOpen();
        }
    }

    intercept(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.button !== undefined && event.button !== 0) return;

        if (this.confirmed) {
            this.confirmed = false;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        this.pendingElement = event.target.closest("a, button") ?? event.target;
        this.overlay?.open();
    }

    async confirm() {
        const element = this.pendingElement;
        const closed = await this.overlay?.close();
        if (!closed || this.pendingElement !== element) return;

        this.confirmed = true;
        element?.click();
        this.pendingElement = null;
    }

    cancel() {
        this.pendingElement = null;
        return this.overlay?.close();
    }

    closeForCache() {
        this.pendingElement = null;
        this.overlay?.closeNow({ restoreFocus: false });
    }

    #queueOverlayRefresh() {
        if (!this.connected) return;

        if (this.overlayRefreshQueued) return;

        this.overlayRefreshQueued = true;
        queueMicrotask(() => {
            this.overlayRefreshQueued = false;
            if (!this.connected) return;
            if (this.overlay?.isOpening || this.overlay?.isClosing) {
                void this.overlay.settled.then(
                    () => this.#queueOverlayRefresh(),
                    () => this.#queueOverlayRefresh(),
                );

                return;
            }

            const reopen = this.overlay?.isOpen ?? false;
            const stackPosition = this.overlay?.stackPosition ?? -1;
            const topLayerPosition = this.overlay?.topLayerPosition ?? -1;
            this.overlay?.cleanup();
            this.overlay = null;
            if (!this.hasModalTarget || !this.hasBackdropTarget || !this.hasDialogTarget) return;

            this.#setupOverlay(reopen, stackPosition, topLayerPosition);
        });
    }

    clickOutside(event) {
        event.stopPropagation();

        if (
            this.closeOnClickOutsideValue &&
            this.overlay?.isOpen &&
            this.overlay?.isTop &&
            !this.dialogTarget.contains(event.target) &&
            event.target !== this.dialogTarget
        ) {
            this.cancel();
        }
    }

}
