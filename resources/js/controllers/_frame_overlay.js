// @hotwire-package

export function createFrameOverlay(controller) {
    let observer = null;
    let contentState = "";
    let dismissedWhileLoading = false;
    let lastClickedLink = null;
    let pendingStreamRender = null;
    let dynamicContentId = null;
    let managedReferences = null;

    resetManagedReferences();

    function resetManagedReferences() {
        managedReferences = {
            title: managedReference("aria-labelledby", "data-hotwire-overlay-labelledby"),
            description: managedReference("aria-describedby", "data-hotwire-overlay-describedby"),
        };
    }

    function managedReference(attribute, marker) {
        const value = controller.modalTarget.getAttribute(marker);
        const current = controller.modalTarget.getAttribute(attribute);

        return {
            attribute,
            marker,
            value,
            owned: value !== null && current === value,
            relinquished: value === null && current !== null,
        };
    }

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
        if (hasDynamicContent()) dynamicContent().innerHTML = "";
        contentState = "";
        syncAccessibleName();
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
        syncAccessibleName();
    }

    function refreshContentObserver() {
        observer?.disconnect();
        observer = null;
        dynamicContentId = null;
        resetManagedReferences();
        initializeContentObserver();
    }

    function trackClickedLink(event) {
        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.button !== undefined && event.button !== 0) return;
        if (!hasDynamicContent()) return;

        const frameId = dynamicContent().id;
        if (!frameId) return;

        const link = event.target.closest("a[data-turbo-frame]");
        if (!link || link.getAttribute("data-turbo-frame") !== frameId) {
            lastClickedLink = null;
            return;
        }

        if (!controller.isOpen && !controller.overlay?.isOpening) {
            controller.triggerElement = link;
        }
        lastClickedLink = link.hasAttribute("data-loading-template") ? link : null;
    }

    function handleBeforeFetchRequest(event) {
        if (!hasDynamicContent()) return;
        if (event.target !== dynamicContent()) return;

        dismissedWhileLoading = false;
        if (!controller.modalTarget.hidden) return;

        const templateHtml = resolveLoadingTemplate();
        if (templateHtml) {
            dynamicContent().innerHTML = templateHtml;
            syncAccessibleName();
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
        syncAccessibleName();
    }

    function handleMorphElement(event) {
        if (!controller.hasModalTarget || event.target !== controller.modalTarget) return;

        resetManagedReferences();
        syncAccessibleName();
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
        syncAccessibleName();
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

    function syncAccessibleName() {
        if (!controller.hasModalTarget) return;
        if (!hasDynamicContent() && dynamicContentId === null) return;
        if (!controller.modalTarget.hasAttribute("data-hotwire-overlay-labels")) return;

        syncReference("title");
        syncReference("description");
    }

    function syncReference(kind) {
        const state = managedReferences[kind];
        const current = controller.modalTarget.getAttribute(state.attribute);

        if (state.owned && current !== state.value) {
            state.owned = false;
            state.relinquished = true;
            controller.modalTarget.removeAttribute(state.marker);
            return;
        }

        const authoredAttribute = kind === "title" ? "aria-label" : "aria-description";
        if (controller.modalTarget.hasAttribute(authoredAttribute)) {
            removeManagedReference(state);
            state.relinquished = true;
            return;
        }

        if (!state.owned && current !== null) {
            state.relinquished = true;
        }
        if (state.relinquished) return;

        const label = findOwnedLabel(kind);
        const id = label ? resolveLabelId(label, kind) : null;
        if (!id) {
            removeManagedReference(state);
            return;
        }

        controller.modalTarget.setAttribute(state.attribute, id);
        controller.modalTarget.setAttribute(state.marker, id);
        state.value = id;
        state.owned = true;
    }

    function removeManagedReference(state) {
        if (state.owned && controller.modalTarget.getAttribute(state.attribute) === state.value) {
            controller.modalTarget.removeAttribute(state.attribute);
        }

        controller.modalTarget.removeAttribute(state.marker);
        state.value = null;
        state.owned = false;
    }

    function findOwnedLabel(kind) {
        if (!hasDynamicContent()) return null;

        const selector = `[data-slot="${controller.identifier}-${kind}"]`;

        return [...dynamicContent().querySelectorAll(selector)].find((element) => {
            if (element.closest("template")) return false;

            const boundary = element.closest('[role="dialog"], [role="alertdialog"], [data-hotwire-overlay-labels]');

            return boundary === controller.modalTarget;
        }) ?? null;
    }

    function resolveLabelId(label, kind) {
        if (label.id) {
            return document.querySelectorAll(`#${cssEscape(label.id)}`).length === 1 ? label.id : null;
        }

        const root = controller.element.id || dynamicContent().id || `hotwire-${controller.identifier}`;
        const base = `${root}-${kind}`;
        let id = base;
        let suffix = 2;
        while (document.getElementById(id)) {
            id = `${base}-${suffix}`;
            suffix++;
        }

        label.id = id;
        label.setAttribute("data-hotwire-overlay-generated-id", "");

        return id;
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
    controller.element.addEventListener("turbo:morph-element", handleMorphElement);

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
        refresh: refreshContentObserver,
        cleanup() {
            observer?.disconnect();
            observer = null;
            document.removeEventListener("click", trackClickedLink, true);
            document.removeEventListener("turbo:before-fetch-request", handleBeforeFetchRequest);
            document.removeEventListener("turbo:frame-render", handleFrameRender);
            document.removeEventListener("turbo:frame-load", handleFrameLoad);
            document.removeEventListener("turbo:before-stream-render", handleBeforeStreamRender);
            controller.element.removeEventListener("turbo:morph-element", handleMorphElement);
        },
    };
}
