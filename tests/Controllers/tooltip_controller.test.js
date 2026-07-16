import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import SidebarController from "../../resources/js/controllers/sidebar_controller.js";
import TooltipController from "../../resources/js/controllers/tooltip_controller.js";

// mock.module is scoped per file because the suite runs with `bun test --isolate`
// (see package.json). Drop the flag once Bun 1.4 makes isolation the default.

const state = {
    instances: [],
    calls: [],
};

const tippyFn = mock((element, options) => {
    const instance = { element, options, destroy: mock(() => {}), hide: mock(() => {}) };
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

test.serial("default side creates top placement", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(state.calls[0].options.placement).toBe("top");
});

test.serial("passes side and align values to tippy placement", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-side-value="bottom" data-tooltip-align-value="end">Hover me</button>`);

    expect(state.calls[0].options.placement).toBe("bottom-end");
});

test.serial("omits the placement suffix for center alignment", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-side-value="right">Hover me</button>`);

    expect(state.calls[0].options.placement).toBe("right");
});

// --- conditional enablement ---

test.serial("allows showing when enabledWhen is omitted", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    expect(state.calls[0].options.onShow()).toBeUndefined();
});

test.serial("blocks showing when enabledWhen does not match an ancestor", async () => {
    await mount(`
        <div data-slot="sidebar" data-collapsible="">
            <button
                data-controller="tooltip"
                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
            >Map</button>
        </div>
    `);

    expect(state.calls[0].options.onShow()).toBe(false);
});

test.serial("allows showing when enabledWhen matches an ancestor", async () => {
    await mount(`
        <div data-slot="sidebar" data-collapsible="icon">
            <button
                data-controller="tooltip"
                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
            >Map</button>
        </div>
    `);

    expect(state.calls[0].options.onShow()).toBeUndefined();
});

test.serial("enables the tooltip when enabledWhen starts matching", async () => {
    await mount(`
        <div data-slot="sidebar" data-collapsible="">
            <div data-slot="sidebar-menu-item">
                <button
                    data-controller="tooltip"
                    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
                >Map</button>
            </div>
        </div>
    `);

    mounted.root.closest('[data-slot="sidebar"]').dataset.collapsible = "icon";
    await wait(0);

    expect(state.calls[0].options.onShow()).toBeUndefined();
});

test.serial("enables sidebar icon rail tooltips after the sidebar collapses", async () => {
    mounted = await mountMultipleControllers({ sidebar: SidebarController, tooltip: TooltipController }, `
        <div data-controller="sidebar" data-sidebar-open-value="true" data-state="expanded">
            <button data-slot="sidebar-trigger" data-action="click->sidebar#toggle">Toggle</button>
            <div
                data-slot="sidebar"
                data-sidebar-collapsible="icon"
                data-state="expanded"
                data-collapsible=""
            >
                <a
                    href="/components/map"
                    data-slot="sidebar-menu-button"
                    data-controller="tooltip"
                    data-tooltip-content-value="Map"
                    data-tooltip-side-value="right"
                    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
                >
                    <svg></svg>
                    <span>Map</span>
                </a>
            </div>
        </div>
    `);
    await wait(0);

    const tooltipElement = document.querySelector("[data-controller~='tooltip']");
    const tooltipController = mounted.getController("tooltip", tooltipElement);

    expect(tooltipController.isEnabled()).toBe(false);
    expect(state.calls[0].options.onShow()).toBe(false);

    document.querySelector("[data-slot='sidebar-trigger']").click();
    await wait(0);

    expect(document.querySelector("[data-slot='sidebar']").dataset.collapsible).toBe("icon");
    expect(tooltipController.isEnabled()).toBe(true);
    expect(state.calls[0].options.onShow()).toBeUndefined();
});

test.serial("hides the tooltip when enabledWhen stops matching", async () => {
    await mount(`
        <div data-slot="sidebar" data-collapsible="icon">
            <div data-slot="sidebar-menu-item">
                <button
                    data-controller="tooltip"
                    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
                >Map</button>
            </div>
        </div>
    `);

    mounted.root.closest('[data-slot="sidebar"]').dataset.collapsible = "";
    mounted.controller.syncEnabledState();

    expect(state.instances[0].hide).toHaveBeenCalled();
});

test.serial("invalid enabledWhen selectors fail closed", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-enabled-when-value="[">Hover me</button>`);

    expect(state.calls[0].options.onShow()).toBe(false);
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
