// @hotwire-package

const ORIGINAL_POPOVER_ATTRIBUTE = "data-hotwire-top-layer-popover";
const ABSENT_POPOVER = "__hotwire_absent__";

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

    function show() {
        if (!supported || shown) return;

        previousPopover = element.getAttribute("popover");
        element.setAttribute(ORIGINAL_POPOVER_ATTRIBUTE, previousPopover ?? ABSENT_POPOVER);
        element.setAttribute("popover", "manual");
        element.setAttribute("data-hotwire-top-layer", "");

        try {
            element.showPopover();
            shown = true;
            document.dispatchEvent(new CustomEvent("hotwire:top-layer:show", {
                detail: { element },
            }));
        } catch (_error) {
            restoreAttributes();
        }
    }

    function hide() {
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
        if (shown) hide();

        show();
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

    return {
        get isShown() { return shown; },
        get isSupported() { return supported; },
        show,
        hide,
        bringToFront,
        cleanup,
    };
}
