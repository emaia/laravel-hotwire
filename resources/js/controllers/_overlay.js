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

export function createOverlay(_controller, {
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
        if (!managedPresence && !activeTopLayer) return;

        event.preventDefault();
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
    modalTarget.addEventListener("turbo:before-morph-attribute", preserveManagedAttributesDuringMorph);

    async function open() {
        if (destroyed) return false;
        if (desiredOpen && presence.phase !== "closing") return opening ?? true;

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
        modalTarget.removeEventListener("turbo:before-morph-attribute", preserveManagedAttributesDuringMorph);
        desiredOpen = false;
        presence.cleanup();
        unregisterStack();
        unlockScrollIfNeeded();
        focusTrap.deactivate();
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

    function setTriggerElement(element) {
        triggerElement = element;
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
        setTriggerElement,
        cleanup,
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
