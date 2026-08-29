// @hotwire-package

const ORIGINAL_POPOVER_ATTRIBUTE = "data-hotwire-top-layer-popover";
const ABSENT_POPOVER = "__hotwire_absent__";
const topLayers = [];

export function createTopLayer(element, { enabled = true } = {}) {
    if (element?.hasAttribute("data-hotwire-top-layer") && element.getAttribute("popover") === "manual") {
        const originalPopover = element.getAttribute(ORIGINAL_POPOVER_ATTRIBUTE);
        element.removeAttribute("data-hotwire-top-layer");
        element.removeAttribute(ORIGINAL_POPOVER_ATTRIBUTE);
        if (originalPopover && originalPopover !== ABSENT_POPOVER) {
            element.setAttribute("popover", originalPopover);
        } else {
            element.removeAttribute("popover");
        }
    }

    const supported = Boolean(
        enabled &&
        element &&
        typeof element.showPopover === "function" &&
        typeof element.hidePopover === "function",
    );

    let shown = false;
    let previousPopover = null;
    const entry = { raise };

    function show(position = null) {
        if (!supported || shown) return;
        if (!showNative()) return;

        removeEntry();
        const index = Number.isInteger(position) && position >= 0
            ? Math.min(position, topLayers.length)
            : topLayers.length;
        topLayers.splice(index, 0, entry);
        topLayers.slice(index + 1).forEach((topLayer) => topLayer.raise());
    }

    function showNative() {
        previousPopover = element.getAttribute("popover");
        element.setAttribute(ORIGINAL_POPOVER_ATTRIBUTE, previousPopover ?? ABSENT_POPOVER);
        element.setAttribute("popover", "manual");
        element.setAttribute("data-hotwire-top-layer", "");

        try {
            element.showPopover();
            shown = true;
            notifyShown();

            return true;
        } catch (_error) {
            restoreAttributes();

            return false;
        }
    }

    function hide() {
        if (shown) hideNative();
        removeEntry();
    }

    function restore() {
        // A raise that failed while the element was detached keeps the entry but
        // clears `shown`, so retention — not `shown` — decides what to restore.
        if (!supported || topLayers.indexOf(entry) < 0) return;

        if (!shown) {
            if (!showNative()) return;
        } else {
            try {
                if (!element.matches(":popover-open")) {
                    element.showPopover();
                    notifyShown();
                }
            } catch (_error) {
                // The element may not have reconnected yet.

                return;
            }
        }

        const index = topLayers.indexOf(entry);
        if (index >= 0) topLayers.slice(index + 1).forEach((topLayer) => topLayer.raise());
    }

    function hideNative() {
        if (!shown) return;

        try {
            element.hidePopover();
        } catch (_error) {
            // The element may already have been hidden by the browser or removed.
        }

        shown = false;
        restoreAttributes();
    }

    function cleanup() {
        hide();
    }

    function bringToFront() {
        if (shown) {
            hideNative();
            removeEntry();
        }

        show();
    }

    function raise() {
        if (!shown) return;

        hideNative();
        if (!showNative() && element.isConnected) removeEntry();
    }

    function removeEntry() {
        const index = topLayers.indexOf(entry);
        if (index >= 0) topLayers.splice(index, 1);
    }

    function restoreAttributes() {
        element.removeAttribute("data-hotwire-top-layer");
        element.removeAttribute(ORIGINAL_POPOVER_ATTRIBUTE);

        if (previousPopover === null) {
            element.removeAttribute("popover");
        } else {
            element.setAttribute("popover", previousPopover);
        }

        previousPopover = null;
    }

    function notifyShown() {
        document.dispatchEvent(new CustomEvent("hotwire:top-layer:show", {
            detail: { element },
        }));
    }

    return {
        get isShown() { return shown; },
        get isSupported() { return supported; },
        get position() { return topLayers.indexOf(entry); },
        show,
        hide,
        restore,
        bringToFront,
        cleanup,
    };
}
