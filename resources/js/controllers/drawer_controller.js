// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";
import { createFrameOverlay } from "./_frame_overlay.js";

export default class DrawerController extends Controller {
    static targets = ["trigger", "modal", "backdrop", "dialog", "dynamicContent", "loadingTemplate"];

    static classes = [
        "hidden",
        "visible",
        "backdropHidden",
        "backdropVisible",
        "dialogHidden",
        "dialogVisible",
        "lockScroll",
    ];

    static values = {
        openDuration: { type: Number, default: 450 },
        closeDuration: { type: Number, default: 450 },
        lockScroll: { type: Boolean, default: true },
        closeOnEscape: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
    };

    overlay = null;
    frameOverlay = null;
    triggerElement = null;

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.overlay = createOverlay(this, {
            modalTarget: this.modalTarget,
            backdropTarget: this.hasBackdropTarget ? this.backdropTarget : null,
            dialogTarget: this.dialogTarget,
            hiddenClasses: this.hiddenClasses,
            visibleClasses: this.visibleClasses,
            backdropHiddenClasses: this.hasBackdropHiddenClass ? this.backdropHiddenClasses : [],
            backdropVisibleClasses: this.hasBackdropVisibleClass ? this.backdropVisibleClasses : [],
            dialogHiddenClasses: this.dialogHiddenClasses,
            dialogVisibleClasses: this.dialogVisibleClasses,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            openDuration: this.openDurationValue,
            closeDuration: this.closeDurationValue,
            closeOnEscape: this.closeOnEscapeValue,
            escapeCapture: true,
            stopEscapePropagation: true,
            closeOnClickOutside: this.closeOnClickOutsideValue,
            onOpen: () => this.dispatch("opened"),
            onClose: () => {
                this.dispatch("closed");
                this.frameOverlay?.handleOverlayClosed();
            },
            getTriggerElement: () => this.triggerElement,
        });

        this.frameOverlay = createFrameOverlay(this);

        if (this.modalTarget.getAttribute("data-open") === "true") {
            this.overlay.setOpen();
        }
    }

    disconnect() {
        this.frameOverlay?.cleanup();
        this.frameOverlay = null;
        this.overlay?.cleanup();
    }

    open(event) {
        if (event && (event.ctrlKey || event.metaKey || event.shiftKey)) return;
        if (event && event.button !== undefined && event.button !== 0) return;
        if (this.isOpen) return;

        this.triggerElement = event?.currentTarget ?? event?.target ?? document.activeElement;
        this.overlay?.open();
    }

    close() {
        this.frameOverlay?.markDismissedWhileLoading();
        this.overlay?.close();
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
}
