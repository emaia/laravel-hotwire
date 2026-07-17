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

const computeState = {
    placement: "top-end",
    middlewareData: {},
};

const computePosition = mock(async () => ({
    x: 12,
    y: 34,
    placement: computeState.placement,
    middlewareData: computeState.middlewareData,
}));
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
const arrow = mock((options) => ({ name: "arrow", options }));
const hide = mock((options = {}) => ({ name: "hide", options }));

mock.module("@floating-ui/dom", () => ({
    autoUpdate,
    computePosition,
    offset,
    flip,
    shift,
    size,
    arrow,
    hide,
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
    arrow.mockClear();
    hide.mockClear();
    state.update = null;
    computeState.placement = "top-end";
    computeState.middlewareData = {};
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

test("supports arrow, hide and disabled size middleware", async () => {
    const anchor = document.createElement("button");
    const floating = document.createElement("div");
    const arrowElement = document.createElement("div");
    computeState.placement = "top";
    computeState.middlewareData = {
        arrow: { x: 6 },
        hide: { referenceHidden: true },
    };

    const instance = createFloating(anchor, floating, {
        arrowElement,
        arrowPadding: 6,
        hideWhenDetached: true,
        shiftPadding: 12,
        size: false,
    });

    await instance.update();

    expect(shift).toHaveBeenCalledWith({ padding: 12 });
    expect(size).not.toHaveBeenCalled();
    expect(arrow).toHaveBeenCalledWith({ element: arrowElement, padding: 6 });
    expect(hide).toHaveBeenCalled();
    expect(arrowElement.style.left).toBe("6px");
    expect(arrowElement.dataset.side).toBe("top");
    expect(arrowElement.style.bottom).toBe("-5px");
    expect(floating.hasAttribute("data-anchor-hidden")).toBe(true);
});

test("removes data-anchor-hidden when the anchor is visible", async () => {
    const floating = document.createElement("div");
    floating.setAttribute("data-anchor-hidden", "");
    computeState.middlewareData = {
        hide: { referenceHidden: false, escaped: false },
    };

    const instance = createFloating(document.createElement("button"), floating, {
        hideWhenDetached: true,
    });

    await instance.update();

    expect(floating.hasAttribute("data-anchor-hidden")).toBe(false);
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
