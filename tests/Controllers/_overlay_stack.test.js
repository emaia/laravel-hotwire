import { afterEach, expect, mock, test } from "bun:test";

import { activateTopOverlay, registerOverlay } from "../../resources/js/controllers/_overlay_stack.js";

const unregister = [];

afterEach(() => {
    while (unregister.length > 0) unregister.pop()();
});

test("keeps the current focus trap active until the reserved top can activate", () => {
    let childReady = false;
    const parent = {
        activateFocusTrap: mock(() => true),
        deactivateFocusTrap: mock(() => {}),
    };
    const child = {
        activateFocusTrap: mock(() => childReady),
        deactivateFocusTrap: mock(() => {}),
    };
    const unregisterParent = registerOverlay(parent);
    const unregisterChild = registerOverlay(child);
    unregister.push(unregisterParent, unregisterChild);

    expect(parent.activateFocusTrap).toHaveBeenCalledTimes(1);
    expect(parent.deactivateFocusTrap).not.toHaveBeenCalled();
    expect(child.activateFocusTrap).toHaveBeenCalledTimes(1);

    childReady = true;

    expect(activateTopOverlay(child)).toBe(true);
    expect(child.activateFocusTrap).toHaveBeenCalledTimes(2);
    expect(parent.deactivateFocusTrap).toHaveBeenCalledTimes(1);

    unregisterChild();

    expect(child.deactivateFocusTrap).toHaveBeenCalledTimes(1);
    expect(parent.activateFocusTrap).toHaveBeenCalledTimes(2);
});
