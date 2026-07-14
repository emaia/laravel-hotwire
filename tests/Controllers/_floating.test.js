import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

const cleanup = mock(() => {});
const state = {
    update: null,
};

const autoUpdate = mock((anchor, floating, update) => {
    state.update = update;
    update();

    return cleanup;
});

const computePosition = mock(async () => ({ x: 12, y: 34, placement: "top-end" }));
const offset = mock((options) => ({ name: "offset", options }));
const flip = mock((options = {}) => ({ name: "flip", options }));
const shift = mock((options = {}) => ({ name: "shift", options }));
const size = mock((options) => {
    options.apply({
        availableWidth: 320,
        availableHeight: 240,
        rects: {
            reference: { width: 128, height: 32 },
        },
    });

    return { name: "size", options };
});

mock.module("@floating-ui/dom", () => ({
    autoUpdate,
    computePosition,
    offset,
    flip,
    shift,
    size,
}));

const { createFloating } = await import("../../resources/js/controllers/_floating.js");

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.HTMLElement = testWindow.HTMLElement;

    cleanup.mockClear();
    autoUpdate.mockClear();
    computePosition.mockClear();
    offset.mockClear();
    flip.mockClear();
    shift.mockClear();
    size.mockClear();
    state.update = null;
});

afterEach(() => {
    testWindow.close();
});

test("starts auto-update and positions the floating element", async () => {
    const anchor = document.createElement("button");
    const floating = document.createElement("div");
    const instance = createFloating(anchor, floating, {
        side: "bottom",
        align: "end",
        sideOffset: 8,
        alignOffset: -2,
        strategy: "fixed",
    });

    await instance.start();

    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalledWith(anchor, floating, expect.objectContaining({
        placement: "bottom-end",
        strategy: "fixed",
    }));
    expect(offset).toHaveBeenCalledWith({ mainAxis: 8, crossAxis: -2 });
    expect(floating.style.position).toBe("fixed");
    expect(floating.style.left).toBe("12px");
    expect(floating.style.top).toBe("34px");
    expect(floating.dataset.side).toBe("top");
    expect(floating.dataset.align).toBe("end");
    expect(floating.style.getPropertyValue("--anchor-width")).toBe("128px");
    expect(floating.style.getPropertyValue("--anchor-height")).toBe("32px");
    expect(floating.style.getPropertyValue("--available-width")).toBe("320px");
    expect(floating.style.getPropertyValue("--available-height")).toBe("240px");
    expect(floating.style.getPropertyValue("--transform-origin")).toBe("bottom right");
});

test("omits flip and shift middleware when disabled", async () => {
    const instance = createFloating(document.createElement("button"), document.createElement("div"), {
        flip: false,
        shift: false,
    });

    await instance.update();

    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

test("does not start auto-update twice and cleans up on stop", async () => {
    const instance = createFloating(document.createElement("button"), document.createElement("div"));

    await instance.start();
    await instance.start();

    expect(autoUpdate).toHaveBeenCalledTimes(1);

    instance.stop();

    expect(cleanup).toHaveBeenCalledTimes(1);
});

test("cleanup is idempotent", () => {
    const instance = createFloating(document.createElement("button"), document.createElement("div"));

    instance.cleanup();
    instance.cleanup();

    expect(cleanup).not.toHaveBeenCalled();
});
