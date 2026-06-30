// @hotwire-package
import { Controller } from "@hotwired/stimulus";

import { createOverlay } from "./_overlay.js";

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

    observer = null;
    lastCloseTime = 0;
    contentState = "";
    dismissedWhileLoading = false;
    lastClickedLink = null;
    pendingEmptyStreamRender = null;
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
                const pending = this.pendingEmptyStreamRender;
                this.pendingEmptyStreamRender = null;
                this.#dispatchEvent("modal:closed");
                pending?.();
                this.clearContent();
            },
            getTriggerElement: () => this.triggerElement,
            isClickInsideCheck: (event) => this.#isClickInsideModal(event),
        });

        this.#initializeContentObserver();

        document.addEventListener("click", this.trackClickedLink, true);
        document.addEventListener("turbo:before-fetch-request", this.handleBeforeFetchRequest);
        document.addEventListener("turbo:before-stream-render", this.handleBeforeStreamRender);

        if (this.modalTarget.getAttribute("data-open") === "true") {
            this.overlay.setOpen();
        }
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }

        document.removeEventListener("click", this.trackClickedLink, true);
        document.removeEventListener("turbo:before-fetch-request", this.handleBeforeFetchRequest);
        document.removeEventListener("turbo:before-stream-render", this.handleBeforeStreamRender);

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
        this.dismissedWhileLoading = true;
        this.overlay?.close();
    }

    clearContent() {
        if (this.hasDynamicContentTarget) {
            this.dynamicContentTarget.innerHTML = "";
            this.contentState = "";
        }
    }

    // --- Modal-specific helpers ---

    clickOutside(event) {
        if (this.closeOnClickOutsideValue && this.isOpen) {
            if (event.target !== this.dialogTarget
                && !this.#isClickInsideModal(event)
                && !this.#isClickOnModalRelatedElement(event.target)) {
                this.close();
            }
        }
    }

    trackClickedLink = (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.button !== undefined && event.button !== 0) return;

        if (!this.hasDynamicContentTarget) return;
        const frameId = this.dynamicContentTarget.id;
        if (!frameId) return;

        const link = event.target.closest("a[data-turbo-frame]");
        if (!link || link.getAttribute("data-turbo-frame") !== frameId) {
            this.lastClickedLink = null;
            return;
        }

        this.lastClickedLink = link.hasAttribute("data-loading-template") ? link : null;
    };

    handleBeforeFetchRequest = (event) => {
        if (!this.hasDynamicContentTarget) return;
        if (event.target !== this.dynamicContentTarget) return;
        if (!this.modalTarget.hidden) return;

        this.dismissedWhileLoading = false;

        const templateHtml = this.#resolveLoadingTemplate();
        if (templateHtml) {
            this.dynamicContentTarget.innerHTML = templateHtml;
        }
    };

    handleBeforeStreamRender = (event) => {
        const stream = event.target;

        if (!this.#isEmptyStreamForModalCloseTarget(stream) || (!this.isOpen && !this.overlay?.isClosing)) {
            return;
        }

        event.preventDefault();
        this.pendingEmptyStreamRender = () => this.#renderStream(event);

        if (this.overlay?.isClosing) return;

        this.close();
    };

    #dispatchEvent(name) {
        this.element.dispatchEvent(
            new CustomEvent(name, {
                bubbles: true,
                detail: { controller: this },
            }),
        );
    }

    #resolveLoadingTemplate() {
        const trigger = this.#findTriggerWithTemplate();

        if (trigger) {
            const selector = trigger.getAttribute("data-loading-template");
            const customTemplate = document.querySelector(selector);
            if (customTemplate) return customTemplate.innerHTML;
        }

        if (this.hasLoadingTemplateTarget) {
            return this.loadingTemplateTarget.innerHTML;
        }

        return null;
    }

    #findTriggerWithTemplate() {
        return this.lastClickedLink ?? null;
    }

    #getContentHash() {
        if (!this.hasDynamicContentTarget) return "";
        const content = this.dynamicContentTarget.innerHTML.trim();
        const len = content.length;
        if (len === 0) return "";
        const prefix = content.substring(0, Math.min(20, len));
        const suffix = len > 20 ? content.substring(len - 20) : "";
        return `${len}:${prefix}:${suffix}`;
    }

    #initializeContentObserver() {
        if (!this.hasDynamicContentTarget) return;

        this.contentState = this.#getContentHash();

        this.observer = new MutationObserver(() => {
            if (!this.hasDynamicContentTarget) return;

            const currentHash = this.#getContentHash();
            const hasContent = currentHash.length > 0;
            const contentChanged = currentHash !== this.contentState;

            if (hasContent && contentChanged && !this.isOpen && !this.overlay?.isOpening && !this.dismissedWhileLoading) {
                this.contentState = currentHash;
                this.open();
            } else if (!hasContent && this.isOpen && !this.overlay?.isClosing) {
                this.contentState = currentHash;
                this.close();
            } else if (contentChanged) {
                this.contentState = currentHash;
            }
        });

        this.observer.observe(this.dynamicContentTarget, {
            childList: true,
            characterData: true,
            subtree: true,
        });
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

    #isEmptyStreamForModalCloseTarget(stream) {
        if (!stream) return false;

        const action = stream.getAttribute("action");
        const target = stream.getAttribute("target");

        if (!["update", "replace"].includes(action) || !this.#isModalCloseTarget(target)) return false;

        const template = stream.querySelector("template");
        if (!template) return true;

        return template.innerHTML.trim() === "";
    }

    #isModalCloseTarget(target) {
        if (!target) return false;
        if (this.element.id && target === this.element.id) return true;
        return this.hasDynamicContentTarget && this.dynamicContentTarget.id && target === this.dynamicContentTarget.id;
    }

    #renderStream(event) {
        if (typeof event.detail?.render === "function") {
            event.detail.render(event.target);
            return;
        }
        event.target.performAction?.();
    }
}
