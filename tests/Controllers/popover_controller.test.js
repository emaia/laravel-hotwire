import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

const floatingCleanup = mock(() => {});
const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const computePosition = mock(async () => ({ x: 20, y: 32, placement: "bottom-start" }));
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

const { default: PopoverController } = await import("../../resources/js/controllers/popover_controller.js");

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
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

const trigger = () => document.querySelector('[data-popover-target="trigger"]');
const content = () => document.querySelector('[data-popover-target="content"]');
const isOpen = () => !content().classList.contains("hidden");

function clickTrigger() {
    trigger().dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
}

function press(key, target = document) {
    target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

// --- open / close ---

test.serial("starts closed with aria-expanded false", async () => {
    await mount();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(content().dataset.open).toBe("false");
});

test.serial("toggles open and closed from the trigger", async () => {
    await mount();

    clickTrigger();
    await wait(0);

    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(content().dataset.open).toBe("true");
    expect(document.activeElement).toBe(content().querySelector("input"));

    clickTrigger();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(content().dataset.open).toBe("false");
});

test.serial("open() and close() are idempotent", async () => {
    await mount();

    mounted.controller.open();
    mounted.controller.open();
    expect(isOpen()).toBe(true);

    mounted.controller.close();
    mounted.controller.close();
    expect(isOpen()).toBe(false);
});

test.serial("starts open when open-value is true", async () => {
    await mount({ open: true });
    await wait(0);

    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(autoUpdate).toHaveBeenCalledTimes(1);
});

test.serial("connects without reporting a Stimulus error when content is missing", async () => {
    const consoleError = console.error;
    const error = mock(() => {});
    console.error = error;

    try {
        mounted = await mountController(
            "popover",
            PopoverController,
            `
            <div data-controller="popover">
                <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            </div>`,
        );

        expect(error).not.toHaveBeenCalled();
        expect(mounted.controller.isOpen).toBe(false);
    } finally {
        console.error = consoleError;
    }
});

// --- positioning ---

test.serial("starts floating positioning when opened and stops when closed", async () => {
    await mount();

    clickTrigger();
    await wait(0);

    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalled();
    expect(content().style.left).toBe("20px");
    expect(content().style.top).toBe("32px");
    expect(content().dataset.side).toBe("bottom");
    expect(content().dataset.align).toBe("start");
    expect(computePosition.mock.calls[0][2].strategy).toBe("fixed");

    clickTrigger();

    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("passes popover positioning values to Floating UI", async () => {
    mounted = await mountController(
        "popover",
        PopoverController,
        `
        <div data-controller="popover"
             data-popover-side-value="right"
             data-popover-align-value="end"
             data-popover-side-offset-value="12"
             data-popover-align-offset-value="-4"
             data-popover-strategy-value="absolute"
             data-popover-flip-value="false"
             data-popover-shift-value="false">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            <div data-popover-target="content" class="hidden"><input id="name"></div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    const options = computePosition.mock.calls[0][2];
    expect(options.placement).toBe("right-end");
    expect(options.strategy).toBe("absolute");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 12, crossAxis: -4 });
    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

// --- focus / dismissal ---

test.serial("clicking inside the popover content does not close it", async () => {
    await mount();
    clickTrigger();

    content().querySelector("button").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(true);
});

test.serial("closes when clicking outside", async () => {
    await mount();
    clickTrigger();
    expect(isOpen()).toBe(true);

    document.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(false);
});

test.serial("does not close from the same click event that opened it", async () => {
    await mount();

    const event = { currentTarget: trigger(), target: document.body };
    mounted.controller.toggle(event);
    mounted.controller.onOutsideClick(event);

    expect(isOpen()).toBe(true);
});

test.serial("Escape closes, prevents default and returns focus to the trigger", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    expect(isOpen()).toBe(true);

    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    content().querySelector("input").dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(isOpen()).toBe(false);
    expect(document.activeElement).toBe(trigger());
});

test.serial("the close action dismisses the popover", async () => {
    await mount();
    clickTrigger();

    content().querySelector("button").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(true);

    content().querySelector("[data-action='popover#close']").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(false);
});

test.serial("Escape inside an open drawer closes only the popover first", async () => {
    mounted = await mountMultipleControllers(
        {
            drawer: DrawerController,
            popover: PopoverController,
        },
        `
        <div data-controller="drawer"
             data-drawer-open-duration-value="1"
             data-drawer-close-duration-value="1"
             data-drawer-hidden-class="pointer-events-none"
             data-drawer-visible-class="pointer-events-auto"
             data-drawer-backdrop-hidden-class="opacity-0"
             data-drawer-backdrop-visible-class="opacity-100"
             data-drawer-dialog-hidden-class="translate-x-full"
             data-drawer-dialog-visible-class="translate-x-0"
             data-drawer-lock-scroll-class="overflow-hidden">
            <button id="drawer-trigger" data-drawer-target="trigger" data-action="drawer#toggle">Open drawer</button>
            <div data-drawer-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-drawer-target="backdrop" data-action="click->drawer#clickOutside" class="opacity-0"></div>
                <div data-drawer-target="dialog" class="translate-x-full">
                    <div data-controller="popover">
                        <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
                        <div data-popover-target="content" class="hidden"><input id="nested-input"></div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    document.getElementById("drawer-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);
    clickTrigger();
    await wait(0);

    press("Escape", document.getElementById("nested-input"));
    await wait(10);

    expect(isOpen()).toBe(false);
    expect(mounted.controller.isOpen).toBe(true);
});

test.serial("Escape inside an open modal closes only the popover when the popover listener runs first", async () => {
    mounted = await mountMultipleControllers(
        {
            popover: PopoverController,
            modal: ModalController,
        },
        `
        <div id="modal" data-controller="modal"
             data-modal-open-duration-value="1"
             data-modal-close-duration-value="1"
             data-modal-hidden-class="pointer-events-none"
             data-modal-visible-class="pointer-events-auto"
             data-modal-backdrop-hidden-class="opacity-0"
             data-modal-backdrop-visible-class="opacity-100"
             data-modal-dialog-hidden-class="scale-80 opacity-0"
             data-modal-dialog-visible-class="scale-100 opacity-100"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <div data-controller="popover">
                        <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
                        <div data-popover-target="content" class="hidden"><input id="modal-popover-input"></div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    const modal = mounted.getController("modal", document.getElementById("modal"));

    document.getElementById("modal-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);
    clickTrigger();
    await wait(0);

    press("Escape", document.getElementById("modal-popover-input"));
    await wait(10);

    expect(isOpen()).toBe(false);
    expect(modal.isOpen).toBe(true);
});

// --- cleanup ---

test.serial("closes on turbo:before-cache", async () => {
    await mount();
    clickTrigger();
    expect(isOpen()).toBe(true);
    expect(content().dataset.open).toBe("true");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(content().dataset.open).toBe("false");
    expect(floatingCleanup).toHaveBeenCalled();
});

test.serial("disconnect cleans up floating positioning", async () => {
    await mount();
    clickTrigger();
    await wait(0);

    mounted.controller.disconnect();

    expect(floatingCleanup).toHaveBeenCalled();
});

test.serial("turbo:before-cache cancels a pending transition and hides cleanly", async () => {
    mounted = await mountController(
        "popover",
        PopoverController,
        `
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            <div data-popover-target="content" class="hidden"
                 data-transition-enter="t-enter" data-transition-enter-from="ef" data-transition-enter-to="et">
                <input>
            </div>
        </div>`,
    );

    const contentEl = content();
    mounted.controller.open();
    expect(contentEl.classList.contains("t-enter")).toBe(true);
    expect(contentEl.classList.contains("ef")).toBe(true);

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(contentEl.classList.contains("hidden")).toBe(true);
    expect(contentEl.classList.contains("t-enter")).toBe(false);
    expect(contentEl.classList.contains("ef")).toBe(false);

    await wait(0);
    expect(contentEl.classList.contains("hidden")).toBe(true);
    expect(contentEl.classList.contains("et")).toBe(false);
});

test.serial("popovers operate independently", async () => {
    const { mountControllers } = await import("../../resources/js/helpers/test_stimulus.js");

    mounted = await mountControllers(
        "popover",
        PopoverController,
        `
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">A</button>
            <div data-popover-target="content" class="hidden"><input></div>
        </div>
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">B</button>
            <div data-popover-target="content" class="hidden"><input></div>
        </div>`,
    );

    const triggers = [...document.querySelectorAll('[data-popover-target="trigger"]')];
    const contents = [...document.querySelectorAll('[data-popover-target="content"]')];

    triggers[0].dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(contents[0].classList.contains("hidden")).toBe(false);
    expect(contents[1].classList.contains("hidden")).toBe(true);
});

// --- helpers ---

async function mount({ open = false } = {}) {
    const openAttr = open ? 'data-popover-open-value="true"' : "";

    mounted = await mountController(
        "popover",
        PopoverController,
        `
        <div data-controller="popover" ${openAttr}>
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-haspopup="dialog" aria-expanded="false">
                Open
            </button>
            <div data-popover-target="content" data-open="${open ? "true" : "false"}" class="hidden" tabindex="-1">
                <label>Name <input id="name"></label>
                <button type="button">Action</button>
                <button type="button" data-action="popover#close">Done</button>
            </div>
        </div>`,
    );
}
