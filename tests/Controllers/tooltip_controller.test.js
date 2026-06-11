import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import TooltipController from "../../resources/js/controllers/tooltip_controller.js";

// WARNING: mock.module is process-global in Bun. Currently tippy.js is only
// imported by tooltip_controller, so no cross-file conflict — but introducing
// another consumer (or reviving toaster, which hit this with sonner) will
// require a per-file scoping helper before this file can coexist with theirs.

const state = {
    instances: [],
    calls: [],
};

const tippyFn = mock((element, options) => {
    const instance = { element, options, destroy: mock(() => {}) };
    state.instances.push(instance);
    state.calls.push({ element, options });
    return instance;
});

mock.module("tippy.js", () => ({ default: tippyFn }));
mock.module("tippy.js/dist/tippy.css", () => ({}));

let mounted;

beforeEach(() => {
    state.instances = [];
    state.calls = [];
    tippyFn.mockClear();
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- connect ---

test.serial("connect creates a tippy instance on the element", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(tippyFn).toHaveBeenCalledTimes(1);
    expect(state.calls[0].element).toBe(mounted.root);
});

test.serial("passes content value to tippy", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-content-value="Hello world">Hover me</button>`);

    expect(state.calls[0].options.content).toBe("Hello world");
});

test.serial("default content is 'Tooltip'", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(state.calls[0].options.content).toBe("Tooltip");
});

test.serial("always passes allowHTML: true", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(state.calls[0].options.allowHTML).toBe(true);
});

test.serial("default placement is 'top'", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(state.calls[0].options.placement).toBe("top");
});

test.serial("passes placement value to tippy", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-placement-value="bottom-end">Hover me</button>`);

    expect(state.calls[0].options.placement).toBe("bottom-end");
});

// --- disconnect ---

test.serial("disconnect destroys the tippy instance", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    const instance = state.instances[0];
    mounted.controller.disconnect();

    expect(instance.destroy).toHaveBeenCalled();
});

test.serial("connect-disconnect-connect cycles cleanly", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(tippyFn).toHaveBeenCalledTimes(1);
    const first = state.instances[0];

    mounted.controller.disconnect();
    expect(first.destroy).toHaveBeenCalledTimes(1);

    mounted.controller.connect();
    expect(tippyFn).toHaveBeenCalledTimes(2);

    mounted.controller.disconnect();
    expect(state.instances[1].destroy).toHaveBeenCalledTimes(1);
});

test.serial("double connect destroys the previous instance", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    const first = state.instances[0];

    mounted.controller.connect();

    expect(tippyFn).toHaveBeenCalledTimes(2);
    expect(first.destroy).toHaveBeenCalledTimes(1);
});

async function mount(html) {
    mounted = await mountController("tooltip", TooltipController, html);
}
