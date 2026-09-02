import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

const cleanup = mock(() => {});
const state = {
    update: null,
};

const defaultAutoUpdate = (anchor, floating, update) => {
    state.update = update;
    update();

    return cleanup;
};
const autoUpdate = mock(defaultAutoUpdate);

const computeState = {
    placement: "top-end",
    middlewareData: {},
};

const defaultComputePosition = async () => ({
    x: 12,
    y: 34,
    placement: computeState.placement,
    middlewareData: computeState.middlewareData,
});
const computePosition = mock(defaultComputePosition);
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
    autoUpdate.mockImplementation(defaultAutoUpdate);
    computePosition.mockClear();
    computePosition.mockImplementation(defaultComputePosition);
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

    expect(await instance.start()).toBe(true);

    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalledTimes(1);
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

test("resolves inline transform origins from the floating element direction", async () => {
    const anchor = document.createElement("button");
    const floating = document.createElement("div");
    const directionScope = document.createElement("div");
    directionScope.dir = "rtl";
    directionScope.append(floating);
    computeState.placement = "top-start";

    await createFloating(anchor, floating).update();

    expect(floating.style.getPropertyValue("--transform-origin")).toBe("bottom right");

    computeState.placement = "top-end";
    await createFloating(anchor, floating).update();

    expect(floating.style.getPropertyValue("--transform-origin")).toBe("bottom left");
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

    const firstStart = instance.start();
    const secondStart = instance.start();

    expect(secondStart).toBe(firstStart);
    expect(await firstStart).toBe(true);

    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalledTimes(1);

    instance.stop();

    expect(cleanup).toHaveBeenCalledTimes(1);
});

test("cleans up a failed first placement and allows a retry", async () => {
    const instance = createFloating(document.createElement("button"), document.createElement("div"));
    computePosition.mockRejectedValueOnce(new Error("placement failed"));

    await expect(instance.start()).rejects.toThrow("placement failed");

    expect(cleanup).toHaveBeenCalledTimes(1);
    expect(await instance.start()).toBe(true);
    expect(autoUpdate).toHaveBeenCalledTimes(2);
    expect(computePosition).toHaveBeenCalledTimes(2);
});

test("turns a synchronous auto-update failure into a retryable rejection", async () => {
    const instance = createFloating(document.createElement("button"), document.createElement("div"));
    autoUpdate.mockImplementationOnce(() => {
        throw new Error("auto-update failed");
    });

    await expect(instance.start()).rejects.toThrow("auto-update failed");

    expect(await instance.start()).toBe(true);
    expect(autoUpdate).toHaveBeenCalledTimes(2);
});

test("cleanup is idempotent", () => {
    const instance = createFloating(document.createElement("button"), document.createElement("div"));

    instance.cleanup();
    instance.cleanup();

    expect(cleanup).not.toHaveBeenCalled();
});

test("stop invalidates an in-flight first placement", async () => {
    const pending = deferred();
    const floating = document.createElement("div");
    computePosition.mockImplementation(() => pending.promise);
    const instance = createFloating(document.createElement("button"), floating);

    const started = instance.start();
    instance.stop();

    expect(await started).toBe(false);

    pending.resolve(position({ x: 99, y: 88, placement: "left-start" }));
    await tick();

    expect(floating.style.left).toBe("");
    expect(floating.dataset.side).toBeUndefined();
});

test("restart ignores an older placement that resolves last", async () => {
    const first = deferred();
    const second = deferred();
    const results = [first, second];
    const floating = document.createElement("div");
    computePosition.mockImplementation(() => results.shift().promise);
    const instance = createFloating(document.createElement("button"), floating);

    const firstStart = instance.start();
    instance.stop();
    const secondStart = instance.start();

    second.resolve(position({ x: 20, y: 30, placement: "right-end" }));
    expect(await secondStart).toBe(true);

    first.resolve(position({ x: 90, y: 100, placement: "top-start" }));
    await firstStart;
    await tick();

    expect(floating.style.left).toBe("20px");
    expect(floating.style.top).toBe("30px");
    expect(floating.dataset.side).toBe("right");
    expect(floating.dataset.align).toBe("end");
});

test("the latest update wins when computations resolve out of order", async () => {
    const first = deferred();
    const second = deferred();
    const results = [first, second];
    const floating = document.createElement("div");
    computePosition.mockImplementation(() => results.shift().promise);
    const instance = createFloating(document.createElement("button"), floating);

    const started = instance.start();
    const updated = instance.update();

    second.resolve(position({ x: 40, y: 50, placement: "bottom-end" }));
    expect(await updated).toBe(true);
    expect(await started).toBe(true);

    first.resolve(position({ x: 4, y: 5, placement: "left-start" }));
    await tick();

    expect(floating.style.left).toBe("40px");
    expect(floating.style.top).toBe("50px");
    expect(floating.dataset.side).toBe("bottom");
});

test("stale size middleware cannot mutate floating variables", async () => {
    const pending = deferred();
    const floating = document.createElement("div");
    computePosition.mockImplementation(() => pending.promise);
    const instance = createFloating(document.createElement("button"), floating);

    const started = instance.start();
    const apply = size.mock.calls[0][0].apply;
    instance.stop();

    floating.style.removeProperty("--anchor-width");
    floating.style.removeProperty("--available-width");
    apply({
        availableWidth: 900,
        availableHeight: 800,
        rects: { reference: { width: 700, height: 600 } },
    });

    expect(floating.style.getPropertyValue("--anchor-width")).toBe("");
    expect(floating.style.getPropertyValue("--available-width")).toBe("");

    pending.resolve(position());
    expect(await started).toBe(false);
});

function position(overrides = {}) {
    return {
        x: 12,
        y: 34,
        placement: "top-end",
        middlewareData: {},
        ...overrides,
    };
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}

function tick() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
