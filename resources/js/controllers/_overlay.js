// @hotwire-package
// Shared overlay lifecycle for modal, sheet, drawer and sidebar controllers.

import { FocusTrap } from "./_focus_trap.js";
import { isComposing } from "./_composition.js";
import { registerOverlay, unregisterOverlay, isTopOverlay, overlayPosition } from "./_overlay_stack.js";
import { createPresence } from "./_presence.js";
import { createTopLayer } from "./_top_layer.js";

const ESCAPE_SCOPE_SELECTOR = "[data-hotwire-escape-scope]";
const handledEscapeEvents = new WeakSet();
let overlayAccessibilityId = 0;

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
    const rootId = controller?.element?.id || modalTarget.id || nextOverlayAccessibilityId(modalTarget, prefix);
    const root = controller?.element && controller.element !== modalTarget ? controller.element : null;
    const rootAccessibilityValues = new Map();
    const rootAccessibilityFallbacks = new Map();
    let suppressedLabelReference;
    let managesLabel = false;
    let managesDescription = false;
    let managedLabelReference = null;
    let managedDescriptionReference = null;
    let pendingLabelManagement = null;
    let pendingDescriptionManagement = null;
    let morphToken = 0;
    let refreshFrame = null;
    let pendingRootSync = false;
    let active = true;

    function refresh() {
        ({ managed: managesLabel, reference: managedLabelReference } = syncReference(
            "labelledby",
            "title",
            managesLabel,
            managedLabelReference,
        ));
        ({ managed: managesDescription, reference: managedDescriptionReference } = syncReference(
            "describedby",
            "description",
            managesDescription,
            managedDescriptionReference,
        ));
    }

    function syncReference(attributeSuffix, slotSuffix, managed, managedReference) {
        const attributeName = `aria-${attributeSuffix}`;
        const candidate = findOwnedSlot(modalTarget, slotSuffix);

        if (isRootAuthored(attributeName)) {
            return { managed: false, reference: null };
        }

        if (managed) {
            if (modalTarget.getAttribute(attributeName) !== managedReference) {
                return { managed: false, reference: null };
            }

            assignCandidateId(candidate, slotSuffix);
            if (candidate?.id) {
                modalTarget.setAttribute(attributeName, candidate.id);

                return { managed: true, reference: candidate.id };
            }

            modalTarget.removeAttribute(attributeName);

            return { managed: false, reference: null };
        }

        const currentReference = modalTarget.getAttribute(attributeName);
        if (currentReference !== null) {
            const ownsReference = currentReference === candidate?.id;

            return { managed: ownsReference, reference: ownsReference ? currentReference : null };
        }

        if (attributeSuffix === "labelledby" && modalTarget.hasAttribute("aria-label")) {
            return { managed: false, reference: null };
        }

        assignCandidateId(candidate, slotSuffix);
        if (!candidate?.id) {
            return { managed: false, reference: null };
        }

        modalTarget.setAttribute(attributeName, candidate.id);

        return { managed: true, reference: candidate.id };
    }

    function assignCandidateId(candidate, slotSuffix) {
        if (candidate && !candidate.id && rootId) {
            candidate.id = `${rootId}-${slotSuffix}`;
        }
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
        if (pendingLabelManagement === true) {
            managedLabelReference = modalTarget.getAttribute("aria-labelledby");
        } else if (pendingLabelManagement === false) {
            managedLabelReference = null;
        }
        if (pendingDescriptionManagement === true) {
            managedDescriptionReference = modalTarget.getAttribute("aria-describedby");
        } else if (pendingDescriptionManagement === false) {
            managedDescriptionReference = null;
        }
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
                const hasTargetValue = modalTarget.hasAttribute(attributeName);
                const targetValue = modalTarget.getAttribute(attributeName);
                if ((previousValue === undefined && hasTargetValue)
                    || (previousValue !== undefined && targetValue !== previousValue)) {
                    rootAccessibilityFallbacks.set(attributeName, hasTargetValue ? targetValue : null);
                }
                modalTarget.setAttribute(attributeName, value);
                rootAccessibilityValues.set(attributeName, value);
            } else if (previousValue !== undefined) {
                if (modalTarget.getAttribute(attributeName) === previousValue) {
                    const hasFallback = rootAccessibilityFallbacks.has(attributeName);
                    const fallback = rootAccessibilityFallbacks.get(attributeName);
                    if (hasFallback && fallback !== null) {
                        modalTarget.setAttribute(attributeName, fallback);
                    } else {
                        modalTarget.removeAttribute(attributeName);
                    }
                }
                rootAccessibilityFallbacks.delete(attributeName);
                rootAccessibilityValues.delete(attributeName);
            }
        }

        const suppressesLabelReference = root.hasAttribute("aria-label") && !root.hasAttribute("aria-labelledby");
        if (suppressesLabelReference) {
            if (modalTarget.hasAttribute("aria-labelledby")) {
                suppressedLabelReference = modalTarget.getAttribute("aria-labelledby");
            }
            modalTarget.removeAttribute("aria-labelledby");
        } else if (!root.hasAttribute("aria-labelledby") && suppressedLabelReference !== undefined) {
            if (!modalTarget.hasAttribute("aria-labelledby")) {
                modalTarget.setAttribute("aria-labelledby", suppressedLabelReference);
            }
            suppressedLabelReference = undefined;
        }
    }

    function restoreRootAccessibility() {
        for (const [attributeName, copiedValue] of rootAccessibilityValues) {
            if (modalTarget.getAttribute(attributeName) !== copiedValue) continue;

            if (!rootAccessibilityFallbacks.has(attributeName)
                || rootAccessibilityFallbacks.get(attributeName) === null) {
                modalTarget.removeAttribute(attributeName);
            } else {
                modalTarget.setAttribute(attributeName, rootAccessibilityFallbacks.get(attributeName));
            }
        }
        rootAccessibilityValues.clear();
        rootAccessibilityFallbacks.clear();

        if (suppressedLabelReference !== undefined) {
            if (!modalTarget.hasAttribute("aria-labelledby")) {
                modalTarget.setAttribute("aria-labelledby", suppressedLabelReference);
            }
            suppressedLabelReference = undefined;
        }
    }

    function isRootAuthored(attributeName) {
        if (!root) return false;

        if (root.hasAttribute(attributeName)) return true;

        return attributeName === "aria-labelledby"
            && root.hasAttribute("aria-label")
            && !root.hasAttribute("aria-labelledby");
    }

    function scheduleRefresh(syncRoot = false) {
        if (!active) return;

        pendingRootSync ||= syncRoot;
        if (refreshFrame !== null) return;

        let frameRan = false;
        const frame = requestAnimationFrame(() => {
            frameRan = true;
            refreshFrame = null;
            if (!active) return;

            if (pendingRootSync) syncRootAccessibility();
            pendingRootSync = false;
            refresh();
        });
        refreshFrame = frameRan ? null : frame;
    }

    function ownsMutation(mutation) {
        if (mutation.attributeName === "role") return true;

        return mutation.target?.closest?.(roleSelector) === modalTarget;
    }

    syncRootAccessibility();
    refresh();

    const Observer = modalTarget.ownerDocument.defaultView?.MutationObserver;
    const observer = Observer ? new Observer((mutations) => {
        if (mutations.some(ownsMutation)) scheduleRefresh();
    }) : null;
    observer?.observe(modalTarget, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["id", "data-slot", "role"],
    });
    const rootObserver = root && Observer ? new Observer(() => scheduleRefresh(true)) : null;
    rootObserver?.observe(root, {
        attributes: true,
        attributeFilter: ["aria-label", "aria-labelledby", "aria-describedby"],
    });

    return {
        refresh,
        prepareMorph,
        completeMorph,
        manages(attributeName) {
            if (isRootAuthored(attributeName)) return false;

            const labelManagement = pendingLabelManagement ?? managesLabel;
            const descriptionManagement = pendingDescriptionManagement ?? managesDescription;

            return (attributeName === "aria-labelledby"
                    && labelManagement
                    && modalTarget.getAttribute(attributeName) === managedLabelReference)
                || (attributeName === "aria-describedby"
                    && descriptionManagement
                    && modalTarget.getAttribute(attributeName) === managedDescriptionReference);
        },
        cleanup() {
            active = false;
            morphToken++;
            pendingLabelManagement = null;
            pendingDescriptionManagement = null;
            pendingRootSync = false;
            if (refreshFrame !== null) cancelAnimationFrame(refreshFrame);
            refreshFrame = null;
            observer?.disconnect();
            rootObserver?.disconnect();
            restoreRootAccessibility();
        },
    };
}

function nextOverlayAccessibilityId(modalTarget, prefix) {
    const document = modalTarget.ownerDocument;
    let id;

    do {
        id = `hw-${prefix}-${++overlayAccessibilityId}`;
    } while (document.getElementById(`${id}-title`) || document.getElementById(`${id}-description`));

    return id;
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
