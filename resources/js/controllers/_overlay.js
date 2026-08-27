// @hotwire-package
// Shared overlay lifecycle for modal, sheet, drawer and sidebar controllers.

import { FocusTrap } from "./_focus_trap.js";
import { isComposing } from "./_composition.js";
import { registerOverlay, unregisterOverlay, isTopOverlay, overlayPosition } from "./_overlay_stack.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

const ESCAPE_SCOPE_SELECTOR = "[data-hotwire-escape-scope]";
const handledEscapeEvents = new WeakSet();

const bodyScrollLock = {
    count: 0,
    classes: new Map(),
    paddingRight: null,
};

export function createOverlay(controller, {
    modalTarget,
    backdropTarget,
    dialogTarget,
    lockScrollClasses = [],
    lockScroll = true,
    closeOnEscape = true,
    escapeCapture = false,
    stopEscapePropagation = false,
    topLayer = true,
    onEscape,
    onOpen,
    onClose,
    getTriggerElement,
    stateAttribute = "state",
    accessibilityPrefix = null,
}) {
    const presence = createPresence(modalTarget, {
        motionElements: [backdropTarget, dialogTarget],
        stateAttribute,
    });
    const focusTrap = new FocusTrap(modalTarget);
    const topLayerHandle = createTopLayer(modalTarget, { enabled: topLayer });
    let desiredOpen = false;
    let destroyed = false;
    let triggerElement = null;
    let stackEntry = null;
    let unregisterStackEntry = null;
    let scrollLocked = false;
    let opening = null;
    let closing = null;
    const accessibility = accessibilityPrefix
        ? createOverlayAccessibility(controller, modalTarget, accessibilityPrefix)
        : null;
    const managedPresenceAttributes = new Set([
        "data-presence",
        "hidden",
        "inert",
        `data-${stateAttribute.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}`,
    ]);
    const managedTopLayerAttributes = new Set([
        "popover",
        "data-hotwire-top-layer",
        "data-hotwire-top-layer-popover",
    ]);

    function preserveManagedAttributesDuringMorph(event) {
        if (event.target !== modalTarget) return;

        const attributeName = event.detail?.attributeName;
        const managedPresence = managedPresenceAttributes.has(attributeName);
        const activeTopLayer = topLayerHandle.isShown && managedTopLayerAttributes.has(attributeName);
        const managedAccessibility = accessibility?.manages(attributeName) ?? false;
        if (!managedPresence && !activeTopLayer && !managedAccessibility) return;

        event.preventDefault();
    }

    function prepareManagedAttributesForMorph(event) {
        if (event.target !== modalTarget) return;

        accessibility?.prepareMorph(event.detail?.newElement);
    }

    function refreshManagedAttributesAfterMorph(event) {
        if (event.target !== modalTarget) return;

        accessibility?.completeMorph();
    }

    function handleEscapeKey(event) {
        if (isComposing(event)) return;
        if (!closeOnEscape || event.key !== "Escape" || !desiredOpen) return;
        if (handledEscapeEvents.has(event)) return;
        if (!isTop()) return;
        if (isNestedEscapeScopeEvent(event, dialogTarget)) return;

        handledEscapeEvents.add(event);

        if (stopEscapePropagation) {
            event.stopImmediatePropagation();
            event.preventDefault();
        }

        if (typeof onEscape === "function") {
            onEscape(event);
        } else {
            close();
        }
    }

    document.addEventListener("keydown", handleEscapeKey, escapeCapture);
    modalTarget.addEventListener("turbo:before-morph-element", prepareManagedAttributesForMorph);
    modalTarget.addEventListener("turbo:before-morph-attribute", preserveManagedAttributesDuringMorph);
    modalTarget.addEventListener("turbo:morph-element", refreshManagedAttributesAfterMorph);

    async function open() {
        if (destroyed) return false;
        if (desiredOpen && presence.phase !== "closing") return opening ?? true;

        accessibility?.refresh();

        desiredOpen = true;
        triggerElement = typeof getTriggerElement === "function"
            ? getTriggerElement()
            : document.activeElement;

        const operation = presence.open({
            beforeEnter: () => desiredOpen,
            onEnter: () => registerStack(),
        });
        topLayerHandle.show();
        lockScrollIfNeeded();
        opening = operation;
        let completed;
        try {
            completed = await operation;
        } catch (error) {
            if (desiredOpen) {
                desiredOpen = false;
                unregisterStack();
                unlockScrollIfNeeded();
                topLayerHandle.hide();
            }

            throw error;
        }
        if (opening === operation) opening = null;

        if (!completed || !desiredOpen || destroyed) return false;

        onOpen?.();

        return true;
    }

    async function close({ restoreFocus = true } = {}) {
        if (destroyed) return false;
        if (!desiredOpen && presence.phase === "closing") return closing;
        if (!desiredOpen && presence.phase === "closed") return true;

        desiredOpen = false;
        const operation = presence.close();
        closing = operation;
        unregisterStack();
        unlockScrollIfNeeded();
        if (restoreFocus) restoreTriggerFocus();

        const completed = await operation;
        if (closing === operation) closing = null;

        if (!completed || desiredOpen || destroyed) return false;

        topLayerHandle.hide();
        onClose?.();

        return true;
    }

    function cleanup() {
        if (destroyed) return;

        destroyed = true;
        document.removeEventListener("keydown", handleEscapeKey, escapeCapture);
        modalTarget.removeEventListener("turbo:before-morph-element", prepareManagedAttributesForMorph);
        modalTarget.removeEventListener("turbo:before-morph-attribute", preserveManagedAttributesDuringMorph);
        modalTarget.removeEventListener("turbo:morph-element", refreshManagedAttributesAfterMorph);
        desiredOpen = false;
        presence.cleanup();
        unregisterStack();
        unlockScrollIfNeeded();
        focusTrap.deactivate();
        accessibility?.cleanup();
        topLayerHandle.cleanup();
        triggerElement = null;
    }

    function closeNow({ restoreFocus = false } = {}) {
        if (destroyed) return false;

        const wasOpen = desiredOpen || presence.phase !== "closed";
        desiredOpen = false;
        presence.sync(false);
        unregisterStack();
        unlockScrollIfNeeded();
        topLayerHandle.hide();
        if (restoreFocus) restoreTriggerFocus();
        if (wasOpen) onClose?.();

        return true;
    }

    function setOpen({ notify = true, stackPosition = null, topLayerPosition = null } = {}) {
        if (destroyed || desiredOpen) return false;

        accessibility?.refresh();

        desiredOpen = true;
        triggerElement = typeof getTriggerElement === "function"
            ? getTriggerElement()
            : document.activeElement;
        presence.sync(true);
        topLayerHandle.show(topLayerPosition);
        lockScrollIfNeeded();
        registerStack(stackPosition);
        if (notify) onOpen?.();

        return true;
    }

    function registerStack(position = null) {
        if (unregisterStackEntry) return;

        stackEntry ??= {
            activateFocusTrap: () => focusTrap.activate(),
            deactivateFocusTrap: () => focusTrap.deactivate(),
        };

        unregisterStackEntry = registerOverlay(stackEntry, position);
    }

    function unregisterStack() {
        if (unregisterStackEntry) {
            unregisterStackEntry();
            unregisterStackEntry = null;

            return;
        }

        if (stackEntry) unregisterOverlay(stackEntry);
    }

    function isTop() {
        return !stackEntry || isTopOverlay(stackEntry);
    }

    function lockScrollIfNeeded() {
        if (!lockScroll || scrollLocked) return;

        lockBodyScroll(lockScrollClasses);
        scrollLocked = true;
    }

    function unlockScrollIfNeeded() {
        if (!scrollLocked) return;

        unlockBodyScroll(lockScrollClasses);
        scrollLocked = false;
    }

    function restoreTriggerFocus() {
        if (!triggerElement || triggerElement.disabled || typeof triggerElement.focus !== "function") return;

        triggerElement.focus();
    }

    return {
        get isOpen() { return desiredOpen; },
        get isOpening() { return presence.phase === "opening"; },
        get isClosing() { return presence.phase === "closing"; },
        get phase() { return presence.phase; },
        get settled() { return opening ?? closing ?? Promise.resolve(true); },
        get stackPosition() { return stackEntry ? overlayPosition(stackEntry) : -1; },
        get topLayerPosition() { return topLayerHandle.position; },
        get isTop() { return isTop(); },
        setOpen,
        open,
        close,
        closeNow,
        cleanup,
    };
}

function createOverlayAccessibility(controller, modalTarget, prefix) {
    const roleSelector = '[role="dialog"], [role="alertdialog"]';
    const rootId = controller?.element?.id || modalTarget.id;
    const root = controller?.element && controller.element !== modalTarget ? controller.element : null;
    const rootAccessibilityValues = new Map();
    let managesLabel = false;
    let managesDescription = false;
    let pendingLabelManagement = null;
    let pendingDescriptionManagement = null;
    let morphToken = 0;

    function refresh() {
        managesLabel = syncReference("labelledby", "title", managesLabel);
        managesDescription = syncReference("describedby", "description", managesDescription);
    }

    function syncReference(attributeSuffix, slotSuffix, managed) {
        const attributeName = `aria-${attributeSuffix}`;
        const candidate = findOwnedSlot(modalTarget, slotSuffix);

        if (candidate && !candidate.id && rootId) {
            candidate.id = `${rootId}-${slotSuffix}`;
        }

        if (isRootAuthored(attributeName)) {
            return false;
        }

        if (managed) {
            if (candidate?.id) {
                modalTarget.setAttribute(attributeName, candidate.id);

                return true;
            }

            modalTarget.removeAttribute(attributeName);

            return false;
        }

        const currentReference = modalTarget.getAttribute(attributeName);
        if (currentReference !== null) {
            return currentReference === candidate?.id;
        }

        if (attributeSuffix === "labelledby" && modalTarget.hasAttribute("aria-label")) {
            return false;
        }

        if (!candidate?.id) {
            return false;
        }

        modalTarget.setAttribute(attributeName, candidate.id);

        return true;
    }

    function prepareMorph(newElement) {
        if (!newElement?.querySelectorAll) return;

        pendingLabelManagement = prepareReference("labelledby", "title", managesLabel, newElement);
        pendingDescriptionManagement = prepareReference("describedby", "description", managesDescription, newElement);
        const token = ++morphToken;
        queueMicrotask(() => {
            if (token !== morphToken) return;

            pendingLabelManagement = null;
            pendingDescriptionManagement = null;
            refresh();
        });
    }

    function completeMorph() {
        morphToken++;
        managesLabel = pendingLabelManagement ?? managesLabel;
        managesDescription = pendingDescriptionManagement ?? managesDescription;
        pendingLabelManagement = null;
        pendingDescriptionManagement = null;
        syncRootAccessibility();
        refresh();
    }

    function prepareReference(attributeSuffix, slotSuffix, managed, newElement) {
        if (!managed) return false;

        const attributeName = `aria-${attributeSuffix}`;
        const incomingReference = newElement.getAttribute(attributeName);
        const incomingCandidate = findOwnedSlot(newElement, slotSuffix);

        if (incomingReference !== null) {
            return incomingReference === incomingCandidate?.id;
        }

        if (attributeSuffix === "labelledby" && newElement.hasAttribute("aria-label")) {
            return false;
        }

        return true;
    }

    function findOwnedSlot(root, slotSuffix) {
        return [...root.querySelectorAll(`[data-slot="${prefix}-${slotSuffix}"]`)]
            .find((element) => element.closest(roleSelector) === root);
    }

    function syncRootAccessibility() {
        if (!root) return;

        for (const attributeName of ["aria-label", "aria-labelledby", "aria-describedby"]) {
            const previousValue = rootAccessibilityValues.get(attributeName);
            if (root.hasAttribute(attributeName)) {
                const value = root.getAttribute(attributeName);
                modalTarget.setAttribute(attributeName, value);
                rootAccessibilityValues.set(attributeName, value);
            } else if (previousValue !== undefined) {
                if (modalTarget.getAttribute(attributeName) === previousValue) {
                    modalTarget.removeAttribute(attributeName);
                }
                rootAccessibilityValues.delete(attributeName);
            }
        }

        if (root.hasAttribute("aria-label") && !root.hasAttribute("aria-labelledby")) {
            modalTarget.removeAttribute("aria-labelledby");
        }
    }

    function isRootAuthored(attributeName) {
        if (!root) return false;

        if (root.hasAttribute(attributeName)) return true;

        return attributeName === "aria-labelledby"
            && root.hasAttribute("aria-label")
            && !root.hasAttribute("aria-labelledby");
    }

    syncRootAccessibility();
    refresh();

    const Observer = modalTarget.ownerDocument.defaultView?.MutationObserver;
    const observer = Observer ? new Observer(refresh) : null;
    observer?.observe(modalTarget, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["id", "data-slot"],
    });
    const rootObserver = root && Observer ? new Observer(() => {
        syncRootAccessibility();
        refresh();
    }) : null;
    rootObserver?.observe(root, {
        attributes: true,
        attributeFilter: ["aria-label", "aria-labelledby", "aria-describedby"],
    });

    return {
        refresh,
        prepareMorph,
        completeMorph,
        manages(attributeName) {
            const labelManagement = pendingLabelManagement ?? managesLabel;
            const descriptionManagement = pendingDescriptionManagement ?? managesDescription;

            return (attributeName === "aria-labelledby" && labelManagement)
                || (attributeName === "aria-describedby" && descriptionManagement);
        },
        cleanup() {
            observer?.disconnect();
            rootObserver?.disconnect();
        },
    };
}

function isNestedEscapeScopeEvent(event, dialogTarget) {
    if (!dialogTarget || typeof event.target?.closest !== "function") return false;

    const scope = event.target.closest(ESCAPE_SCOPE_SELECTOR);

    return Boolean(scope && scope !== dialogTarget && dialogTarget.contains(scope));
}

function lockBodyScroll(classes) {
    if (bodyScrollLock.count === 0) {
        bodyScrollLock.paddingRight = document.body.style.paddingRight;

        const clientWidth = document.documentElement.clientWidth;
        const scrollbarWidth = clientWidth > 0 ? Math.max(0, window.innerWidth - clientWidth) : 0;
        if (scrollbarWidth > 0) {
            const currentPadding = bodyScrollLock.paddingRight.trim();
            document.body.style.paddingRight = currentPadding === ""
                ? `${scrollbarWidth}px`
                : `calc(${currentPadding} + ${scrollbarWidth}px)`;
        }
    }

    for (const className of new Set(classes)) {
        const entry = bodyScrollLock.classes.get(className) ?? {
            count: 0,
            preexisting: document.body.classList.contains(className),
        };
        entry.count++;
        bodyScrollLock.classes.set(className, entry);
        document.body.classList.add(className);
    }

    bodyScrollLock.count++;
}

function unlockBodyScroll(classes) {
    if (bodyScrollLock.count === 0) return;

    bodyScrollLock.count--;
    for (const className of new Set(classes)) {
        const entry = bodyScrollLock.classes.get(className);
        if (!entry) continue;

        entry.count--;
        if (entry.count > 0) continue;

        if (!entry.preexisting) document.body.classList.remove(className);
        bodyScrollLock.classes.delete(className);
    }
    if (bodyScrollLock.count > 0) return;

    document.body.style.paddingRight = bodyScrollLock.paddingRight ?? "";
    bodyScrollLock.paddingRight = null;
}
