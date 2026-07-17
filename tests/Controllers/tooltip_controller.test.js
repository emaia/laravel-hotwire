import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import SidebarController from "../../resources/js/controllers/sidebar_controller.js";

const FRAME_WAIT = 20;
const ANIMATION_WAIT = 180;

const floatingCleanup = mock(() => {});
const floatingState = {
    placement: "bottom-start",
    middlewareData: {},
};

const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const computePosition = mock(async () => ({
    x: 16,
    y: 24,
    placement: floatingState.placement,
    middlewareData: floatingState.middlewareData,
}));
const offset = mock((options) => ({ name: "offset", options }));
const flip = mock((options = {}) => ({ name: "flip", options }));
const shift = mock((options = {}) => ({ name: "shift", options }));
const size = mock((options) => ({ name: "size", options }));
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

const { default: TooltipController } = await import("../../resources/js/controllers/tooltip_controller.js");

let mounted;

beforeEach(() => {
    floatingCleanup.mockClear();
    autoUpdate.mockClear();
    computePosition.mockClear();
    offset.mockClear();
    flip.mockClear();
    shift.mockClear();
    size.mockClear();
    arrow.mockClear();
    hide.mockClear();
    floatingState.placement = "bottom-start";
    floatingState.middlewareData = {};
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.querySelectorAll('[data-slot="tooltip"]').forEach((tooltip) => tooltip.remove());
});

// --- open / positioning ---

test.serial("opens a tooltip on pointerenter and positions it with Floating UI", async () => {
    await mount(`
        <button
            data-controller="tooltip"
            data-tooltip-content-value="Hello <strong>tooltip</strong>"
            data-tooltip-side-value="bottom"
            data-tooltip-align-value="end"
        >Hover me</button>
    `);

    dispatchPointer(mounted.root, "pointerenter");
    await wait(FRAME_WAIT);

    expect(tooltip()?.innerHTML).toContain("Hello <strong>tooltip</strong>");
    expect(tooltip()?.getAttribute("role")).toBe("tooltip");
    expect(tooltip()?.dataset.state).toBe("open");
    expect(tooltip()?.style.left).toBe("16px");
    expect(tooltip()?.style.top).toBe("24px");
    expect(tooltip()?.dataset.side).toBe("bottom");
    expect(tooltip()?.dataset.align).toBe("start");
    expect(tooltipArrow()).toBeTruthy();
    expect(tooltipArrow()?.dataset.side).toBe("bottom");
    expect(mounted.root.getAttribute("aria-describedby")).toBe(tooltip()?.id);
    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition.mock.calls[0][2].placement).toBe("bottom-end");
    expect(computePosition.mock.calls[0][2].strategy).toBe("fixed");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 8, crossAxis: 0 });
    expect(size).not.toHaveBeenCalled();
    expect(arrow).toHaveBeenCalledWith({ element: tooltipArrow(), padding: 4 });
    expect(hide).toHaveBeenCalled();
});

test.serial("opens a tooltip on focusin", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-content-value="Focused">Focus me</button>`);

    mounted.root.dispatchEvent(new Event("focusin", { bubbles: true }));
    await wait(FRAME_WAIT);

    expect(tooltip()?.textContent).toContain("Focused");
});

test.serial("ignores touch pointer hover", async () => {
    await mount(`<button data-controller="tooltip">Hover me</button>`);

    dispatchPointer(mounted.root, "pointerenter", "touch");
    await wait(0);

    expect(tooltip()).toBeNull();
    expect(autoUpdate).not.toHaveBeenCalled();
});

// --- close behavior ---

test.serial("closes after pointerleave and close delay", async () => {
    await openWithPointer(`<button data-controller="tooltip" data-tooltip-close-delay-value="1">Hover me</button>`);

    dispatchPointer(mounted.root, "pointerleave");
    await wait(5);

    expect(tooltip()?.dataset.state).toBe("closed");
    expect(floatingCleanup).toHaveBeenCalledTimes(1);

    await wait(ANIMATION_WAIT);

    expect(tooltip()).toBeNull();
});

test.serial("stays open while the tooltip itself is hovered", async () => {
    await openWithPointer(`<button data-controller="tooltip" data-tooltip-close-delay-value="1">Hover me</button>`);
    const element = tooltip();

    dispatchPointer(mounted.root, "pointerleave");
    dispatchPointer(element, "pointerenter");
    await wait(5);

    expect(tooltip()).toBe(element);

    dispatchPointer(element, "pointerleave");
    await wait(5);

    expect(tooltip()?.dataset.state).toBe("closed");

    await wait(ANIMATION_WAIT);

    expect(tooltip()).toBeNull();
});

test.serial("closes on focusout when the tooltip is not hovered", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-close-delay-value="1">Focus me</button>`);

    mounted.root.dispatchEvent(new Event("focusin", { bubbles: true }));
    await wait(FRAME_WAIT);
    mounted.root.dispatchEvent(new Event("focusout", { bubbles: true }));
    await wait(5);

    expect(tooltip()?.dataset.state).toBe("closed");

    await wait(ANIMATION_WAIT);

    expect(tooltip()).toBeNull();
});

test.serial("closes on Escape without moving focus", async () => {
    await openWithPointer(`<button data-controller="tooltip">Hover me</button>`);
    mounted.root.focus();

    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    document.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(mounted.root);
    expect(tooltip()?.dataset.state).toBe("closed");

    await wait(ANIMATION_WAIT);

    expect(tooltip()).toBeNull();
});

test.serial("closes when the trigger is activated", async () => {
    await openWithPointer(`<button data-controller="tooltip">Hover me</button>`);

    mounted.root.click();

    expect(tooltip()?.dataset.state).toBe("closed");

    await wait(ANIMATION_WAIT);

    expect(tooltip()).toBeNull();
});

test.serial("cleans up on disconnect", async () => {
    await openWithPointer(`<button data-controller="tooltip">Hover me</button>`);

    mounted.controller.disconnect();

    expect(tooltip()).toBeNull();
    expect(mounted.root.hasAttribute("aria-describedby")).toBe(false);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("cleans up before Turbo caches the page", async () => {
    await openWithPointer(`<button data-controller="tooltip">Hover me</button>`);

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(tooltip()).toBeNull();
    expect(mounted.root.hasAttribute("aria-describedby")).toBe(false);
});

// --- aria ---

test.serial("preserves existing aria-describedby tokens", async () => {
    await openWithPointer(`<button data-controller="tooltip" aria-describedby="existing help">Hover me</button>`);
    const id = tooltip().id;

    expect(mounted.root.getAttribute("aria-describedby")).toBe(`existing help ${id}`);

    mounted.controller.hide();

    expect(mounted.root.getAttribute("aria-describedby")).toBe("existing help");
});

test.serial("passes positioning values through to Floating UI", async () => {
    await openWithPointer(`
        <button
            data-controller="tooltip"
            data-tooltip-side-value="right"
            data-tooltip-align-value="end"
            data-tooltip-side-offset-value="12"
            data-tooltip-align-offset-value="-4"
            data-tooltip-strategy-value="absolute"
            data-tooltip-flip-value="false"
            data-tooltip-shift-value="false"
        >Hover me</button>
    `);

    expect(computePosition.mock.calls[0][2].placement).toBe("right-end");
    expect(computePosition.mock.calls[0][2].strategy).toBe("absolute");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 12, crossAxis: -4 });
    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

// --- conditional enablement ---

test.serial("does not open when enabledWhen does not match", async () => {
    await mount(`
        <div data-slot="sidebar" data-collapsible="">
            <button
                data-controller="tooltip"
                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
            >Map</button>
        </div>
    `);

    dispatchPointer(mounted.root, "pointerenter");
    await wait(0);

    expect(tooltip()).toBeNull();
});

test.serial("opens when enabledWhen matches an ancestor", async () => {
    await openWithPointer(`
        <div data-slot="sidebar" data-collapsible="icon">
            <button
                data-controller="tooltip"
                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
            >Map</button>
        </div>
    `);

    expect(tooltip()).toBeTruthy();
});

test.serial("hides the tooltip when enabledWhen stops matching", async () => {
    await openWithPointer(`
        <div data-slot="sidebar" data-collapsible="icon">
            <button
                data-controller="tooltip"
                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
            >Map</button>
        </div>
    `);

    mounted.root.closest('[data-slot="sidebar"]').dataset.collapsible = "";
    mounted.controller.syncEnabledState();

    expect(tooltip()?.dataset.state).toBe("closed");

    await wait(ANIMATION_WAIT);

    expect(tooltip()).toBeNull();
});

test.serial("invalid enabledWhen selectors fail closed", async () => {
    await mount(`<button data-controller="tooltip" data-tooltip-enabled-when-value="[">Hover me</button>`);

    dispatchPointer(mounted.root, "pointerenter");
    await wait(0);

    expect(tooltip()).toBeNull();
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
    dispatchPointer(tooltipElement, "pointerenter");
    await wait(0);

    expect(tooltip()).toBeNull();

    document.querySelector("[data-slot='sidebar-trigger']").click();
    await wait(0);

    dispatchPointer(tooltipElement, "pointerenter");
    await wait(FRAME_WAIT);

    expect(document.querySelector("[data-slot='sidebar']").dataset.collapsible).toBe("icon");
    expect(tooltip()?.textContent).toContain("Map");
});

async function mount(html) {
    mounted = await mountController("tooltip", TooltipController, html);
}

async function openWithPointer(html) {
    await mount(html);
    dispatchPointer(mounted.root, "pointerenter");
    await wait(FRAME_WAIT);
}

function tooltip() {
    return document.querySelector('[data-slot="tooltip"]');
}

function tooltipArrow() {
    return tooltip()?.querySelector('[data-slot="tooltip-arrow"]');
}

function dispatchPointer(element, type, pointerType = "mouse") {
    const event = new Event(type, { bubbles: false, cancelable: true });
    Object.defineProperty(event, "pointerType", { value: pointerType });
    element.dispatchEvent(event);
}
