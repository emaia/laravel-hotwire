// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { captureAction, replayAction, resolveActionElement } from "./_action_replay.js";
import { createOverlay } from "./_overlay.js";

export default class AlertDialogController extends Controller {
    static targets = ["modal", "backdrop", "dialog"];

    static classes = ["lockScroll"];

    static values = {
        lockScroll: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    pendingAction = null;
    replayingAction = false;
    overlay = null;
    overlayTargets = null;
    connected = false;
    overlayRefreshQueued = false;
    disconnectTimer = null;

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.connected = true;
        if (this.disconnectTimer !== null) {
            clearTimeout(this.disconnectTimer);
            this.disconnectTimer = null;
        }
        if (this.overlay && this.#overlayTargetsMatch()) {
            this.overlay.restoreAfterReconnect();

            return;
        }

        const reopen = this.overlay?.isOpen ?? false;
        const stackPosition = this.overlay?.stackPosition ?? null;
        const topLayerPosition = this.overlay?.topLayerPosition ?? null;
        this.overlay?.cleanup();
        this.overlay = null;
        this.overlayTargets = null;
        this.#setupOverlay(reopen, stackPosition, topLayerPosition);
    }

    disconnect() {
        this.connected = false;
        this.overlayRefreshQueued = false;
        if (this.disconnectTimer !== null) clearTimeout(this.disconnectTimer);
        this.disconnectTimer = setTimeout(() => {
            this.disconnectTimer = null;
            if (this.connected) return;

            this.pendingAction = null;
            this.replayingAction = false;
            this.overlay?.cleanup();
            this.overlay = null;
            this.overlayTargets = null;
        }, 0);
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
        this.overlayTargets = {
            modal: this.modalTarget,
            backdrop: this.backdropTarget,
            dialog: this.dialogTarget,
        };
        this.overlay = createOverlay(this, {
            modalTarget: this.overlayTargets.modal,
            backdropTarget: this.overlayTargets.backdrop,
            dialogTarget: this.overlayTargets.dialog,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            closeOnEscape: true,
            escapeCapture: true,
            stopEscapePropagation: true,
            onEscape: () => this.cancel(),
            getTriggerElement: () => this.pendingAction
                ? resolveActionElement(this.pendingAction, this.element)
                : null,
        });

        if (forceOpen) {
            this.overlay.setOpen({ notify: false, stackPosition, topLayerPosition });
        } else if (this.modalTarget.dataset.state === "open" && !this.modalTarget.hidden) {
            this.overlay.setOpen();
        } else {
            this.overlay.closeNow({ restoreFocus: false });
        }
    }

    intercept(event) {
        if (!this.#shouldIntercept(event)) return;

        const action = captureAction(event, this.element);
        if (!action) return;

        this.#intercept(event, action);
    }

    interceptCapture(event) {
        if (!this.#shouldIntercept(event)) return;

        const action = captureAction(event, this.element);
        if (!action) return;

        event.preventDefault();
        event.stopPropagation();
        this.pendingAction = action;
        this.overlay?.open();
    }

    #shouldIntercept(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey) return false;
        if (event.button !== undefined && event.button !== 0) return false;
        if (event.defaultPrevented || this.replayingAction) return false;

        return true;
    }

    #intercept(event, action) {
        event.preventDefault();
        event.stopPropagation();

        this.pendingAction = action;
        this.overlay?.open();
    }

    async confirm() {
        const action = this.pendingAction;
        this.#refreshTriggerElement();
        const closingOverlay = this.overlay;
        let closed = await closingOverlay?.close();
        if (
            !closed &&
            this.connected &&
            this.pendingAction === action &&
            this.overlay &&
            this.overlay !== closingOverlay
        ) {
            closed = await this.overlay.close();
        }
        if (!closed || this.pendingAction !== action) return;

        this.pendingAction = null;
        this.replayingAction = true;
        try {
            if (action) replayAction(action, this.element);
        } finally {
            this.replayingAction = false;
        }
    }

    cancel() {
        this.#refreshTriggerElement();
        const closing = this.overlay?.close();
        this.pendingAction = null;

        return closing;
    }

    closeForCache() {
        this.pendingAction = null;
        this.overlay?.closeNow({ restoreFocus: false });
    }

    #refreshTriggerElement() {
        const trigger = this.pendingAction ? resolveActionElement(this.pendingAction, this.element) : null;
        this.overlay?.setTriggerElement(trigger);
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
            this.overlayTargets = null;
            if (!this.hasModalTarget || !this.hasBackdropTarget || !this.hasDialogTarget) return;

            this.#setupOverlay(reopen, stackPosition, topLayerPosition);
        });
    }

    #overlayTargetsMatch() {
        return this.hasModalTarget && this.hasBackdropTarget && this.hasDialogTarget &&
            this.overlayTargets?.modal === this.modalTarget &&
            this.overlayTargets?.backdrop === this.backdropTarget &&
            this.overlayTargets?.dialog === this.dialogTarget;
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
