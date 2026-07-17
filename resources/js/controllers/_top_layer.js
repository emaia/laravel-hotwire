// @hotwire-package

export function createTopLayer(element, { enabled = true } = {}) {
    const supported = Boolean(
        enabled &&
        element &&
        typeof element.showPopover === "function" &&
        typeof element.hidePopover === "function",
    );

    let shown = false;
    let previousPopover = null;
    let hideTimer = null;

    function show() {
        clearTimeout(hideTimer);
        hideTimer = null;

        if (!supported || shown) return;

        previousPopover = element.getAttribute("popover");
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
        clearTimeout(hideTimer);
        hideTimer = null;

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

    function hideAfterTransition() {
        clearTimeout(hideTimer);

        const delay = transitionDuration(element);
        if (delay <= 0) {
            hide();

            return;
        }

        hideTimer = setTimeout(() => hide(), delay);
    }

    function bringToFront() {
        if (shown) hide();

        show();
    }

    function restoreAttributes() {
        element.removeAttribute("data-hotwire-top-layer");

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
        hideAfterTransition,
        bringToFront,
        cleanup,
    };
}

function transitionDuration(element) {
    const style = getComputedStyle(element);

    return longest(style.transitionDuration) + longest(style.transitionDelay);
}

function longest(value) {
    if (!value) return 0;

    return value.split(",").reduce((max, part) => {
        const trimmed = part.trim();
        const ms = trimmed.endsWith("ms") ? parseFloat(trimmed) : parseFloat(trimmed) * 1000;

        return Number.isFinite(ms) ? Math.max(max, ms) : max;
    }, 0);
}
