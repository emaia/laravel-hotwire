// @hotwire-package

const stack = [];
let activeEntry = null;

export function registerOverlay(entry, position = null) {
    unregisterOverlay(entry);

    const index = Number.isInteger(position) && position >= 0
        ? Math.min(position, stack.length)
        : stack.length;
    stack.splice(index, 0, entry);
    activateTopOverlay(entry);

    return () => unregisterOverlay(entry);
}

export function unregisterOverlay(entry) {
    const index = stack.indexOf(entry);
    if (index === -1) return;

    stack.splice(index, 1);
    if (activeEntry !== entry) return;

    activeEntry = null;
    activateTopOverlay(topOverlay());
    entry.deactivateFocusTrap?.();
}

export function activateTopOverlay(entry) {
    if (!entry || topOverlay() !== entry) return false;
    if (activeEntry === entry) return true;
    if (entry.activateFocusTrap?.() !== true) return false;

    const previous = activeEntry;
    activeEntry = entry;
    previous?.deactivateFocusTrap?.();

    return true;
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
