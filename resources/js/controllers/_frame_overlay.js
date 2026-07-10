// @hotwire-package

export function createFrameOverlay(controller) {
    let observer = null;
    let contentState = "";
    let dismissedWhileLoading = false;
    let lastClickedLink = null;
    let pendingStreamRender = null;
    let dynamicContentId = null;

    function hasDynamicContent() {
        return controller.hasDynamicContentTarget;
    }

    function dynamicContent() {
        if (dynamicContentId) {
            const frame = controller.element.querySelector(`turbo-frame#${cssEscape(dynamicContentId)}`);
            if (frame) {
                ensureDynamicTarget(frame);

                return frame;
            }
        }

        return controller.dynamicContentTarget;
    }

    function getContentHash() {
        if (!hasDynamicContent()) return "";

        const content = dynamicContent().innerHTML.trim();
        const len = content.length;
        if (len === 0) return "";

        const prefix = content.substring(0, Math.min(20, len));
        const suffix = len > 20 ? content.substring(len - 20) : "";

        return `${len}:${prefix}:${suffix}`;
    }

    function clearContent() {
        if (!hasDynamicContent()) return;

        dynamicContent().innerHTML = "";
        contentState = "";
    }

    function initializeContentObserver() {
        if (!hasDynamicContent()) return;

        dynamicContentId = dynamicContent().id || null;
        contentState = getContentHash();

        observer = new MutationObserver(syncContentState);

        observer.observe(dynamicContent(), {
            childList: true,
            characterData: true,
            subtree: true,
        });
    }

    function trackClickedLink(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.button !== undefined && event.button !== 0) return;
        if (!hasDynamicContent()) return;

        const frameId = dynamicContent().id;
        if (!frameId) return;

        const link = event.target.closest("a[data-turbo-frame]");
        if (!link || link.getAttribute("data-turbo-frame") !== frameId) {
            lastClickedLink = null;
            return;
        }

        lastClickedLink = link.hasAttribute("data-loading-template") ? link : null;
    }

    function handleBeforeFetchRequest(event) {
        if (!hasDynamicContent()) return;
        if (event.target !== dynamicContent()) return;
        if (!controller.modalTarget.hidden) return;

        dismissedWhileLoading = false;

        const templateHtml = resolveLoadingTemplate();
        if (templateHtml) {
            dynamicContent().innerHTML = templateHtml;
        }
    }

    function resolveLoadingTemplate() {
        if (lastClickedLink) {
            const selector = lastClickedLink.getAttribute("data-loading-template");
            const customTemplate = document.querySelector(selector);
            if (customTemplate) return customTemplate.innerHTML;
        }

        if (controller.hasLoadingTemplateTarget) {
            return controller.loadingTemplateTarget.innerHTML;
        }

        return null;
    }

    function handleBeforeStreamRender(event) {
        const stream = event.target;

        if (!isCloseStream(stream) || (!controller.isOpen && !controller.overlay?.isClosing)) {
            return;
        }

        event.preventDefault();
        pendingStreamRender = () => renderStream(event);

        if (controller.overlay?.isClosing) return;

        controller.close();
    }

    function handleFrameLoad(event) {
        if (!isDynamicFrame(event.target)) return;

        syncContentState();
    }

    function handleFrameRender(event) {
        if (!isDynamicFrame(event.target)) return;

        ensureDynamicTarget(event.target);
    }

    function isDynamicFrame(frame) {
        if (!frame || frame.tagName !== "TURBO-FRAME") return false;
        if (hasDynamicContent() && frame === dynamicContent()) return true;

        return dynamicContentId && frame.id === dynamicContentId;
    }

    function ensureDynamicTarget(frame) {
        if (!dynamicContentId) return;

        const attribute = `data-${controller.identifier}-target`;
        const targets = (frame.getAttribute(attribute) || "").split(/\s+/).filter(Boolean);
        if (!targets.includes("dynamicContent")) {
            targets.push("dynamicContent");
            frame.setAttribute(attribute, targets.join(" "));
        }
    }

    function cssEscape(value) {
        return window.CSS?.escape ? window.CSS.escape(value) : value.replace(/[^a-zA-Z0-9_-]/g, "\\$&");
    }

    function syncContentState() {
        const currentHash = getContentHash();
        const hasContent = currentHash.length > 0;
        const contentChanged = currentHash !== contentState;

        if (hasContent && !controller.isOpen && !controller.overlay?.isOpening && !dismissedWhileLoading) {
            contentState = currentHash;
            controller.open();
        } else if (contentChanged) {
            contentState = currentHash;
        }
    }

    function isEmptyStreamForCloseTarget(stream) {
        if (!stream) return false;

        const action = stream.getAttribute("action");
        const target = stream.getAttribute("target");

        if (!["update", "replace"].includes(action) || !isCloseTarget(target)) return false;

        const template = stream.querySelector("template");
        if (!template) return true;

        return template.innerHTML.trim() === "";
    }

    function isCloseStream(stream) {
        if (!stream) return false;

        if (stream.getAttribute("action") === "refresh") return true;

        return isEmptyStreamForCloseTarget(stream);
    }

    function isCloseTarget(target) {
        if (!target) return false;
        if (controller.element.id && target === controller.element.id) return true;
        return hasDynamicContent() && dynamicContent().id && target === dynamicContent().id;
    }

    function renderStream(event) {
        if (typeof event.detail?.render === "function") {
            event.detail.render(event.target);
            return;
        }

        event.target.performAction?.();
    }

    initializeContentObserver();
    document.addEventListener("click", trackClickedLink, true);
    document.addEventListener("turbo:before-fetch-request", handleBeforeFetchRequest);
    document.addEventListener("turbo:frame-render", handleFrameRender);
    document.addEventListener("turbo:frame-load", handleFrameLoad);
    document.addEventListener("turbo:before-stream-render", handleBeforeStreamRender);

    return {
        markDismissedWhileLoading() {
            dismissedWhileLoading = true;
        },
        handleOverlayClosed() {
            const pending = pendingStreamRender;
            pendingStreamRender = null;
            pending?.();
            clearContent();
        },
        clearContent,
        cleanup() {
            observer?.disconnect();
            observer = null;
            document.removeEventListener("click", trackClickedLink, true);
            document.removeEventListener("turbo:before-fetch-request", handleBeforeFetchRequest);
            document.removeEventListener("turbo:frame-render", handleFrameRender);
            document.removeEventListener("turbo:frame-load", handleFrameLoad);
            document.removeEventListener("turbo:before-stream-render", handleBeforeStreamRender);
        },
    };
}
