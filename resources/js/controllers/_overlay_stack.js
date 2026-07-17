// @hotwire-package

const stack = [];

export function registerOverlay(entry) {
    unregisterOverlay(entry);

    const current = topOverlay();
    current?.deactivateFocusTrap?.();

    stack.push(entry);
    entry.activateFocusTrap?.();

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

function topOverlay() {
    return stack[stack.length - 1] ?? null;
}
