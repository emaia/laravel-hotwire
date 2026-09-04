// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";
import { createFrameOverlay } from "./_frame_overlay.js";

export default class ModalController extends Controller {
    static targets = ["modal", "backdrop", "dialog", "dynamicContent", "loadingTemplate"];

    static classes = ["lockScroll"];

    static values = {
        lockScroll: { type: Boolean, default: true },
        closeOnEscape: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
        initialFocus: { type: String, default: "auto" },
    };

    frameOverlay = null;
    overlay = null;
    connected = false;
    hasConnected = false;
    overlayRefreshQueued = false;

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.connected = true;
        this.#setupOverlay(false, null, null, !this.hasConnected);
        this.hasConnected = true;
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

    #setupOverlay(forceOpen = false, stackPosition = null, topLayerPosition = null, focusExisting = false) {
        this.overlay = createOverlay(this, {
            modalTarget: this.modalTarget,
            backdropTarget: this.backdropTarget,
            dialogTarget: this.dialogTarget,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            closeOnEscape: this.closeOnEscapeValue,
            initialFocus: () => this.initialFocusValue,
            initialFocusFallback: () => this.modalTarget,
            onOpen: () => {
                this.#dispatchEvent("modal:opened");
            },
            onClose: () => {
                this.#dispatchEvent("modal:closed");
                this.frameOverlay?.handleOverlayClosed();
            },
            getTriggerElement: () => this.triggerElement,
        });

        this.frameOverlay ??= createFrameOverlay(this);

        if (forceOpen) {
            this.overlay.setOpen({ notify: false, stackPosition, topLayerPosition });
        } else if (this.modalTarget.dataset.state === "open" && !this.modalTarget.hidden) {
            this.overlay.setOpen({ focus: focusExisting });
        }
    }

    open(event) {
        if (event && (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey)) return;
        if (event && event.button !== undefined && event.button !== 0) return;

        const trigger = event?.currentTarget ?? event?.target;
        if (trigger?.matches?.('[disabled], [aria-disabled="true"]')) {
            event?.preventDefault?.();
            return;
        }
        const frameLink = trigger?.matches?.("a[href][data-turbo-frame]");
        if (trigger?.matches?.("a[href]") && !frameLink) {
            event.preventDefault();
        }

        if (this.overlay?.isOpening || this.isOpen) return;

        this.triggerElement = trigger ?? this.triggerElement ?? document.activeElement;

        if (frameLink) {
            return this.overlay?.isClosing ? this.overlay.open() : undefined;
        }

        return this.overlay?.open();
    }

    close(event) {
        if (event && (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey)) return;
        if (event && event.button !== undefined && event.button !== 0) return;

        const trigger = event?.currentTarget ?? event?.target;
        if (trigger?.matches?.('[disabled], [aria-disabled="true"]')) {
            event.preventDefault();
            return;
        }
        if (!trigger?.matches?.("a[href]")) event?.preventDefault?.();

        if (!this.overlay?.isOpen) return;

        this.frameOverlay?.markDismissedWhileLoading();
        return this.overlay?.close();
    }

    closeForCache() {
        this.frameOverlay?.clearContent();
        this.overlay?.closeNow({ restoreFocus: false });
    }

    clearContent() {
        this.frameOverlay?.clearContent();
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
            this.frameOverlay?.refresh();
        });
    }

    // --- Modal-specific helpers ---

    clickOutside(event) {
        if (this.closeOnClickOutsideValue && this.isOpen && this.overlay?.isTop) {
            if (event.target !== this.dialogTarget
                && !this.#isClickInsideModal(event)
                && !this.#isClickOnModalRelatedElement(event.target)) {
                this.close();
            }
        }
    }

    #dispatchEvent(name) {
        this.element.dispatchEvent(
            new CustomEvent(name, {
                bubbles: true,
                detail: { controller: this },
            }),
        );
    }

    #isClickInsideModal(event) {
        const rect = this.dialogTarget?.getBoundingClientRect();
        if (!rect) return false;
        return (
            rect.top <= event.clientY &&
            event.clientY <= rect.bottom &&
            rect.left <= event.clientX &&
            event.clientX <= rect.right
        );
    }

    #isClickOnModalRelatedElement(target) {
        if (!target) return false;

        if (this.dialogTarget.contains(target)) return true;

        const selectElement = target.closest("select");
        if (selectElement && this.dialogTarget.contains(selectElement)) return true;

        if (target.tagName === "OPTION") {
            const selectParent = target.parentElement;
            if (selectParent && this.dialogTarget.contains(selectParent)) return true;
        }

        return !!target.closest("[data-modal-ignore]");
    }

}
