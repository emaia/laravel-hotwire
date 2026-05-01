import { Controller } from "@hotwired/stimulus";

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
    trapPriming = false;
    isOpening = false;
    isClosing = false;
    triggerElement = null;
    lastCloseTime = 0;
    contentState = "";
    dismissedWhileLoading = false;
    lastClickedLink = null;
    pendingEmptyStreamRender = null;

    get isOpen() {
        if (!this.hasModalTarget) return false;

        return this.modalTarget.getAttribute("data-open") === "true";
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
        this.showLoading();
    };

    connect() {
        this.#initializeContentObserver();

        this.handleFocusTrap = this.handleFocusTrap.bind(this);
        this.handleEscapeKey = this.handleEscapeKey.bind(this);
        document.addEventListener("keydown", this.handleFocusTrap);
        document.addEventListener("keydown", this.handleEscapeKey);
        document.addEventListener("click", this.trackClickedLink, true);
        document.addEventListener("turbo:before-stream-render", this.handleBeforeStreamRender);

        if (this.isOpen) {
            this.lockScrollClasses.forEach((cls) => document.body.classList.toggle(cls, this.lockScrollValue));
        }
    }

    disconnect() {
        this.#cleanupResources();
    }

    open(event) {
        if (event && (event.ctrlKey || event.metaKey || event.shiftKey)) {
            return;
        }

        if (event && event.button !== undefined && event.button !== 0) {
            return;
        }

        const currentTime = Date.now();
        const clickedElement = event?.target;

        if (clickedElement === this.triggerElement && currentTime - this.lastCloseTime < this.preventReopenDelayValue) {
            return;
        }

        if (this.isOpening || this.isClosing || this.isOpen) {
            return;
        }

        this.isOpening = true;

        this.triggerElement = event?.target || document.activeElement;

        this.modalTarget.hidden = false;
        this.modalTarget.setAttribute("data-open", "true");

        if (this.lockScrollValue) {
            document.body.classList.add(...this.lockScrollClasses);
        }

        requestAnimationFrame(() => {
            this.modalTarget.classList.remove(...this.hiddenClasses);
            this.modalTarget.classList.add(...this.visibleClasses);

            this.backdropTarget.classList.remove(...this.backdropHiddenClasses);
            this.backdropTarget.classList.add(...this.backdropVisibleClasses);

            this.dialogTarget.classList.remove(...this.dialogHiddenClasses);
            this.dialogTarget.classList.add(...this.dialogVisibleClasses);

            this.trapPriming = true;

            setTimeout(() => {
                this.isOpening = false;

                this.#dispatchEvent("modal:opened");
            }, this.openDurationValue);
        });
    }

    close() {
        if (this.isClosing || !this.isOpen) {
            return;
        }

        this.lastCloseTime = Date.now();
        this.isClosing = true;
        this.dismissedWhileLoading = true;

        this.modalTarget.setAttribute("data-open", "false");
        this.modalTarget.classList.remove(...this.visibleClasses);
        this.modalTarget.classList.add(...this.hiddenClasses);

        this.backdropTarget.classList.remove(...this.backdropVisibleClasses);
        this.backdropTarget.classList.add(...this.backdropHiddenClasses);

        this.dialogTarget.classList.remove(...this.dialogVisibleClasses);
        this.dialogTarget.classList.add(...this.dialogHiddenClasses);

        setTimeout(() => {
            this.modalTarget.hidden = true;
            this.isClosing = false;

            const pendingEmptyStreamRender = this.pendingEmptyStreamRender;
            this.pendingEmptyStreamRender = null;

            this.#dispatchEvent("modal:closed");
            pendingEmptyStreamRender?.();
            this.clearContent();
        }, this.closeDurationValue);

        if (this.lockScrollValue) {
            document.body.classList.remove(...this.lockScrollClasses);
        }

        if (this.triggerElement && !this.triggerElement.disabled && typeof this.triggerElement.focus === "function") {
            this.triggerElement.focus();
        }
    }

    clearContent() {
        if (this.hasDynamicContentTarget) {
            this.dynamicContentTarget.innerHTML = "";
            this.contentState = "";
        }
    }

    clickOutside(event) {
        if (
            this.closeOnClickOutsideValue &&
            this.isOpen &&
            event.target !== this.dialogTarget &&
            !this.#isClickInside(event, this.dialogTarget) &&
            !this.#isClickOnModalRelatedElement(event.target)
        ) {
            this.close();
        }
    }

    handleEscapeKey(event) {
        if (this.closeOnEscapeValue && event.key === "Escape" && this.isOpen) {
            this.close();
        }
    }

    showLoading() {
        if (!this.modalTarget.hidden || !this.hasDynamicContentTarget) return;

        this.dismissedWhileLoading = false;

        let isNeedingLoadingIndicator = true;
        const handleFetchResponse = (event) => {
            if (this.#isResponseForDynamicFrame(event)) {
                isNeedingLoadingIndicator = false;
            }
        };

        document.addEventListener("turbo:before-fetch-response", handleFetchResponse);

        setTimeout(() => {
            if (isNeedingLoadingIndicator) {
                const templateHtml = this.#resolveLoadingTemplate();
                if (templateHtml) {
                    this.dynamicContentTarget.innerHTML = templateHtml;
                }
            }
            document.removeEventListener("turbo:before-fetch-response", handleFetchResponse);
        }, 0);
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
        if (this.lastClickedLink) return this.lastClickedLink;

        return null;
    }

    #isResponseForDynamicFrame(event) {
        if (!this.hasDynamicContentTarget) return false;

        const frame = this.dynamicContentTarget;
        const target = event?.target;

        if (target === frame) {
            return true;
        }

        return target?.closest?.("turbo-frame") === frame;
    }

    #dispatchEvent(name) {
        this.element.dispatchEvent(
            new CustomEvent(name, {
                bubbles: true,
                detail: { controller: this },
            }),
        );
    }

    #getContentHash() {
        if (!this.hasDynamicContentTarget) return "";
        const content = this.dynamicContentTarget.innerHTML.trim();
        const contentLength = content.length;
        if (contentLength === 0) return "";

        const prefix = content.substring(0, Math.min(20, contentLength));
        const suffix = contentLength > 20 ? content.substring(contentLength - 20) : "";
        return `${contentLength}:${prefix}:${suffix}`;
    }

    #initializeContentObserver() {
        if (this.hasDynamicContentTarget) {
            this.contentState = this.#getContentHash();

            this.observer = new MutationObserver(() => {
                if (!this.hasDynamicContentTarget) return;

                const currentHash = this.#getContentHash();
                const hasContent = currentHash.length > 0;
                const contentChanged = currentHash !== this.contentState;

                if (hasContent && contentChanged && !this.isOpen && !this.isOpening && !this.dismissedWhileLoading) {
                    this.contentState = currentHash;
                    this.open();
                } else if (!hasContent && this.isOpen && !this.isClosing) {
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
    }

    #cleanupResources() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }

        if (this.isOpen && !this.isClosing) {
            this.close();
        }

        document.removeEventListener("keydown", this.handleFocusTrap);
        document.removeEventListener("keydown", this.handleEscapeKey);
        document.removeEventListener("click", this.trackClickedLink, true);
        document.removeEventListener("turbo:before-stream-render", this.handleBeforeStreamRender);
    }

    handleBeforeStreamRender = (event) => {
        const stream = event.target;

        if (!this.#isEmptyStreamForModalCloseTarget(stream) || (!this.isOpen && !this.isClosing)) {
            return;
        }

        event.preventDefault();
        this.pendingEmptyStreamRender = () => this.#renderStream(event);

        if (this.isClosing) {
            return;
        }

        this.close();
    };

    #isClickInside(event, element) {
        if (!element) return false;

        const rect = element.getBoundingClientRect();
        return (
            rect.top <= event.clientY &&
            event.clientY <= rect.bottom &&
            rect.left <= event.clientX &&
            event.clientX <= rect.right
        );
    }

    #isClickOnModalRelatedElement(target) {
        if (!target) return false;

        if (this.dialogTarget.contains(target)) {
            return true;
        }

        const selectElement = target.closest("select");
        if (selectElement && this.dialogTarget.contains(selectElement)) {
            return true;
        }

        if (target.tagName === "OPTION") {
            const selectParent = target.parentElement;
            if (selectParent && this.dialogTarget.contains(selectParent)) {
                return true;
            }
        }

        return !!target.closest("[data-modal-ignore]");
    }

    handleFocusTrap(event) {
        if (event.key !== "Tab" || !this.hasModalTarget || this.modalTarget.hidden || !this.isOpen) return;

        const focusableElements = this.#getFocusableElements();
        if (focusableElements.length === 0) return;

        const first = focusableElements[0];
        const last = focusableElements[focusableElements.length - 1];
        const active = document.activeElement;

        if (this.trapPriming) {
            event.preventDefault();
            this.trapPriming = false;
            first.focus();
            return;
        }

        if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        } else if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        }
    }

    #getFocusableElements() {
        return this.modalTarget.querySelectorAll(
            'a[href], area[href], input:not([disabled]):not([type="hidden"]), ' +
                "select:not([disabled]), textarea:not([disabled]), " +
                'button:not([disabled]), [tabindex]:not([tabindex="-1"])',
        );
    }

    #isEmptyStreamForModalCloseTarget(stream) {
        if (!stream) return false;

        const action = stream.getAttribute("action");
        const target = stream.getAttribute("target");

        if (!["update", "replace"].includes(action) || !this.#isModalCloseTarget(target)) {
            return false;
        }

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
