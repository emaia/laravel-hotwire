// @hotwire-package

const stack = [];

export function registerOverlay(entry, position = null) {
    unregisterOverlay(entry);

    const current = topOverlay();
    current?.deactivateFocusTrap?.();

    const index = Number.isInteger(position) && position >= 0
        ? Math.min(position, stack.length)
        : stack.length;
    stack.splice(index, 0, entry);
    topOverlay()?.activateFocusTrap?.();

    return () => unregisterOverlay(entry);
}

export function unregisterOverlay(entry) {
    const index = stack.indexOf(entry);
    if (index === -1) return;

    const wasTop = index === stack.length - 1;
    stack.splice(index, 1);
    entry.deactivateFocusTrap?.();

    if (wasTop) {
        topOverlay()?.activateFocusTrap?.();
    }
}

export function isTopOverlay(entry) {
    return topOverlay() === entry;
}

export function overlayPosition(entry) {
    return stack.indexOf(entry);
}

function topOverlay() {
    return stack[stack.length - 1] ?? null;
}
