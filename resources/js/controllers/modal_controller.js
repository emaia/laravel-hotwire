// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";
import { createFrameOverlay } from "./_frame_overlay.js";

export default class ModalController extends Controller {
    static targets = ["modal", "backdrop", "dialog", "dynamicContent", "loadingTemplate"];

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
        openDuration: { type: Number, default: 300 },
        closeDuration: { type: Number, default: 300 },
        lockScroll: { type: Boolean, default: true },
        closeOnEscape: { type: Boolean, default: true },
        closeOnClickOutside: { type: Boolean, default: true },
        preventReopenDelay: { type: Number, default: 300 },
    };

    lastCloseTime = 0;
    frameOverlay = null;
    overlay = null;

    get isOpen() {
        return this.overlay?.isOpen ?? false;
    }

    connect() {
        this.overlay = createOverlay(this, {
            modalTarget: this.modalTarget,
            backdropTarget: this.backdropTarget,
            dialogTarget: this.dialogTarget,
            hiddenClasses: this.hiddenClasses,
            visibleClasses: this.visibleClasses,
            backdropHiddenClasses: this.backdropHiddenClasses,
            backdropVisibleClasses: this.backdropVisibleClasses,
            dialogHiddenClasses: this.dialogHiddenClasses,
            dialogVisibleClasses: this.dialogVisibleClasses,
            lockScrollClasses: this.lockScrollClasses,
            lockScroll: this.lockScrollValue,
            openDuration: this.openDurationValue,
            closeDuration: this.closeDurationValue,
            closeOnEscape: this.closeOnEscapeValue,
            closeOnClickOutside: this.closeOnClickOutsideValue,
            onOpen: () => {
                this.#dispatchEvent("modal:opened");
            },
            onClose: () => {
                this.#dispatchEvent("modal:closed");
                this.frameOverlay?.handleOverlayClosed();
            },
            getTriggerElement: () => this.triggerElement,
            isClickInsideCheck: (event) => this.#isClickInsideModal(event),
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

        const currentTime = Date.now();
        const clickedElement = event?.target;

        if (clickedElement === this.triggerElement && currentTime - this.lastCloseTime < this.preventReopenDelayValue) {
            return;
        }

        if (this.overlay?.isOpening || this.overlay?.isClosing || this.isOpen) return;

        this.triggerElement = event?.target || document.activeElement;
        this.overlay?.open();
    }

    close() {
        if (!this.overlay?.isOpen) return;

        this.lastCloseTime = Date.now();
        this.frameOverlay?.markDismissedWhileLoading();
        this.overlay?.close();
    }

    clearContent() {
        this.frameOverlay?.clearContent();
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
