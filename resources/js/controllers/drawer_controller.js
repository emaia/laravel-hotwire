// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";
import { createFrameOverlay } from "./_frame_overlay.js";

export default class DrawerController extends Controller {
    static targets = ["trigger", "modal", "backdrop", "dialog", "dynamicContent", "loadingTemplate"];

    static classes = ["lockScroll"];

    static values = {
        lockScroll: { type: Boolean, default: true },
        closeOnEscape: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    overlay = null;
    frameOverlay = null;
    triggerElement = null;
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
        this.frameOverlay?.cleanup();
        this.frameOverlay = null;
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
            backdropTarget: this.hasBackdropTarget ? this.backdropTarget : null,
            dialogTarget: this.dialogTarget,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            closeOnEscape: this.closeOnEscapeValue,
            escapeCapture: true,
            stopEscapePropagation: true,
            onOpen: () => this.dispatch("opened"),
            onClose: () => {
                this.dispatch("closed");
                this.frameOverlay?.handleOverlayClosed();
            },
            getTriggerElement: () => this.triggerElement,
        });

        this.frameOverlay ??= createFrameOverlay(this);

        if (forceOpen) {
            this.overlay.setOpen({ notify: false, stackPosition, topLayerPosition });
        } else if (this.modalTarget.dataset.state === "open" && !this.modalTarget.hidden) {
            this.overlay.setOpen();
        }
    }

    open(event) {
        if (event && (event.ctrlKey || event.metaKey || event.shiftKey)) return;
        if (event && event.button !== undefined && event.button !== 0) return;
        if (this.isOpen) return;

        this.triggerElement = event?.currentTarget ?? event?.target ?? document.activeElement;
        return this.overlay?.open();
    }

    close() {
        this.frameOverlay?.markDismissedWhileLoading();
        return this.overlay?.close();
    }

    toggle(event) {
        this.isOpen ? this.close() : this.open(event);
    }

    clickOutside(event) {
        if (!this.closeOnClickOutsideValue || !this.isOpen) return;
        if (!this.overlay?.isTop) return;
        if (this.dialogTarget.contains(event.target)) return;

        this.close();
    }

    closeForCache() {
        this.frameOverlay?.clearContent();
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
            if (!this.hasModalTarget || !this.hasDialogTarget) return;

            this.#setupOverlay(reopen, stackPosition, topLayerPosition);
            this.frameOverlay?.refresh();
        });
    }
}
