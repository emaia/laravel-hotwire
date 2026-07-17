import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountControllers, wait } from "../../resources/js/helpers/test_stimulus.js";

const floatingCleanup = mock(() => {});
const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const computePosition = mock(async () => ({ x: 18, y: 42, placement: "bottom-start" }));
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

const { default: HoverCardController } = await import("../../resources/js/controllers/hover_card_controller.js");

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

const trigger = () => document.querySelector('[data-hover-card-target="trigger"]');
const content = () => document.querySelector('[data-hover-card-target="content"]');
const isOpen = () => !content().classList.contains("hidden");

function mouse(type, target = trigger()) {
    target.dispatchEvent(new MouseEvent(type, { bubbles: true, cancelable: true }));
}

function focus(type, target = trigger(), relatedTarget = null) {
    target.dispatchEvent(new Event(type, { bubbles: true, cancelable: true, relatedTarget }));
}

function press(key, target = document) {
    target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

// --- delays / open-close ---

test.serial("starts closed with aria-expanded false", async () => {
    await mount();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(content().dataset.open).toBe("false");
});

test.serial("opens after hover delay and closes after leave delay", async () => {
    await mount({ openDelay: 10, closeDelay: 10 });

    mouse("mouseenter");
    await wait(5);
    expect(isOpen()).toBe(false);

    await wait(10);
    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(content().dataset.open).toBe("true");

    mouse("mouseleave");
    await wait(5);
    expect(isOpen()).toBe(true);

    await wait(10);
    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
});

test.serial("cancels a pending hover open when the pointer leaves before the delay", async () => {
    await mount({ openDelay: 20, closeDelay: 0 });

    mouse("mouseenter");
    await wait(5);
    mouse("mouseleave");
    await wait(25);

    expect(isOpen()).toBe(false);
    expect(autoUpdate).not.toHaveBeenCalled();
});

test.serial("opens from focus and closes from blur", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });

    focus("focusin");
    expect(isOpen()).toBe(true);

    focus("focusout");
    expect(isOpen()).toBe(false);
});

test.serial("keeps open while moving pointer from trigger to content", async () => {
    await mount({ openDelay: 0, closeDelay: 20 });

    mouse("mouseenter");
    expect(isOpen()).toBe(true);

    mouse("mouseleave");
    await wait(5);
    mouse("mouseenter", content());
    await wait(25);

    expect(isOpen()).toBe(true);
});

// --- positioning ---

test.serial("starts floating positioning when opened and stops when closed", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });

    mouse("mouseenter");
    await wait(0);

    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalled();
    expect(content().style.left).toBe("18px");
    expect(content().style.top).toBe("42px");
    expect(content().dataset.side).toBe("bottom");
    expect(content().dataset.align).toBe("start");
    expect(computePosition.mock.calls[0][2].strategy).toBe("fixed");

    mouse("mouseleave");

    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("passes hover card positioning values to Floating UI", async () => {
    mounted = await mountController(
        "hover-card",
        HoverCardController,
        `
        <div data-controller="hover-card"
             data-hover-card-open-delay-value="0"
             data-hover-card-side-value="right"
             data-hover-card-align-value="end"
             data-hover-card-side-offset-value="12"
             data-hover-card-align-offset-value="-4"
             data-hover-card-strategy-value="absolute"
             data-hover-card-flip-value="false"
             data-hover-card-shift-value="false">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">User</span>
            <div data-hover-card-target="content" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" class="hidden">Content</div>
        </div>`,
    );

    mouse("mouseenter");
    await wait(0);

    const options = computePosition.mock.calls[0][2];
    expect(options.placement).toBe("right-end");
    expect(options.strategy).toBe("absolute");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 12, crossAxis: -4 });
    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

// --- dismissal / cleanup ---

test.serial("Escape closes and returns focus to the trigger", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });

    focus("focusin");
    trigger().focus();
    expect(isOpen()).toBe(true);

    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    content().dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(isOpen()).toBe(false);
    expect(document.activeElement).toBe(trigger());
});

test.serial("closes on turbo:before-cache", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    mouse("mouseenter");
    expect(isOpen()).toBe(true);
    expect(content().dataset.open).toBe("true");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(content().dataset.open).toBe("false");
    expect(floatingCleanup).toHaveBeenCalled();
});

test.serial("disconnect clears pending timers and floating positioning", async () => {
    await mount({ openDelay: 20, closeDelay: 0 });

    mouse("mouseenter");
    mounted.controller.disconnect();
    await wait(25);

    expect(isOpen()).toBe(false);
    expect(autoUpdate).not.toHaveBeenCalled();
});

test.serial("connects without reporting a Stimulus error when content is missing", async () => {
    const consoleError = console.error;
    const error = mock(() => {});
    console.error = error;

    try {
        mounted = await mountController(
            "hover-card",
            HoverCardController,
            `
            <div data-controller="hover-card">
                <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">User</span>
            </div>`,
        );

        expect(error).not.toHaveBeenCalled();
        expect(mounted.controller.isOpen).toBe(false);
    } finally {
        console.error = consoleError;
    }
});

test.serial("hover cards operate independently", async () => {
    mounted = await mountControllers(
        "hover-card",
        HoverCardController,
        `
        <div data-controller="hover-card" data-hover-card-open-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">A</span>
            <div data-hover-card-target="content" class="hidden">A content</div>
        </div>
        <div data-controller="hover-card" data-hover-card-open-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">B</span>
            <div data-hover-card-target="content" class="hidden">B content</div>
        </div>`,
    );

    const triggers = [...document.querySelectorAll('[data-hover-card-target="trigger"]')];
    const contents = [...document.querySelectorAll('[data-hover-card-target="content"]')];

    triggers[0].dispatchEvent(new MouseEvent("mouseenter", { bubbles: true }));

    expect(contents[0].classList.contains("hidden")).toBe(false);
    expect(contents[1].classList.contains("hidden")).toBe(true);
});

// --- helpers ---

async function mount({ openDelay = 10, closeDelay = 100, open = false } = {}) {
    const openAttr = open ? 'data-hover-card-open-value="true"' : "";

    mounted = await mountController(
        "hover-card",
        HoverCardController,
        `
        <div data-controller="hover-card"
             data-hover-card-open-delay-value="${openDelay}"
             data-hover-card-close-delay-value="${closeDelay}"
             ${openAttr}>
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0" aria-expanded="false">User</span>
            <div data-hover-card-target="content" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" data-open="${open ? "true" : "false"}" class="hidden">Preview</div>
        </div>`,
    );
}
