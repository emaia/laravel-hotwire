import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountControllers, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

const floatingCleanup = mock(() => {});
const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const defaultComputePosition = async () => ({ x: 18, y: 42, placement: "bottom-start" });
const computePosition = mock(defaultComputePosition);
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
    computePosition.mockImplementation(defaultComputePosition);
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
const isOpen = () => !content().hidden;

function mouse(type, target = trigger(), relatedTarget = null) {
    target.dispatchEvent(new MouseEvent(type, { bubbles: true, cancelable: true, relatedTarget }));
}

function focus(type, target = trigger(), relatedTarget = null) {
    target.dispatchEvent(new window.FocusEvent(type, { bubbles: true, cancelable: true, relatedTarget }));
}

function press(key, target = document) {
    target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

// --- delays / open-close ---

test.serial("starts closed with aria-expanded false", async () => {
    await mount();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(content().dataset.state).toBe("closed");
    expect(content().hasAttribute("inert")).toBe(true);
});

test.serial("opens after hover delay and closes after leave delay", async () => {
    await mount({ openDelay: 10, closeDelay: 10 });

    mouse("mouseenter");
    await waitUntil(() => content().dataset.state === "open");
    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(content().dataset.state).toBe("open");

    mouse("mouseleave");
    await waitUntil(() => !isOpen());
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
    await wait(0);
    expect(isOpen()).toBe(false);
});

test.serial("keeps open while focus moves from trigger to content with zero close delay", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    const closed = mock(() => {});
    mounted.root.addEventListener("hover-card:closed", closed);
    content().tabIndex = 0;

    trigger().focus();
    await wait(0);
    content().focus();
    await wait(0);

    expect(document.activeElement).toBe(content());
    expect(isOpen()).toBe(true);
    expect(closed).not.toHaveBeenCalled();
});

test.serial("keeps open while the pointer moves from trigger to content with zero close delay", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    const closed = mock(() => {});
    mounted.root.addEventListener("hover-card:closed", closed);

    mouse("mouseenter");
    await wait(0);
    mouse("mouseleave", trigger(), content());
    mouse("mouseenter", content(), trigger());
    await wait(0);

    expect(isOpen()).toBe(true);
    expect(closed).not.toHaveBeenCalled();
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
    await wait(0);

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
            <div data-hover-card-target="content" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" data-state="closed" data-motion="default" hidden inert>Content</div>
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

test.serial("rolls back when the first placement fails", async () => {
    computePosition.mockRejectedValueOnce(new Error("positioning failed"));
    await mount({ openDelay: 0, closeDelay: 0 });
    const handleError = mock(() => {});
    mounted.application.handleError = handleError;

    mouse("mouseenter");
    await wait(0);
    await wait(0);

    expect(mounted.controller.isOpen).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(trigger().dataset.hoverCardState).toBe("closed");
    expect(content().hidden).toBe(true);
    expect(content().hasAttribute("inert")).toBe(true);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
    expect(handleError).toHaveBeenCalledTimes(1);
});

test.serial("waits for placement before dispatching opened", async () => {
    const placement = deferred();
    computePosition.mockImplementationOnce(() => placement.promise);
    await mount({ openDelay: 0 });
    const opened = mock(() => {});
    mounted.root.addEventListener("hover-card:opened", opened);

    mouse("mouseenter");
    await wait(0);

    expect(content().dataset.state).toBe("closed");
    expect(content().hasAttribute("inert")).toBe(true);
    expect(opened).not.toHaveBeenCalled();

    placement.resolve({ x: 18, y: 42, placement: "bottom-start" });
    await wait(0);

    expect(content().dataset.state).toBe("open");
    expect(opened).toHaveBeenCalledTimes(1);
});

test.serial("re-anchors an open card when its active trigger is replaced", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    mouse("mouseenter");
    await wait(0);
    const oldTrigger = trigger();
    const replacement = oldTrigger.cloneNode(true);

    oldTrigger.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(oldTrigger);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);

    expect(replacement.getAttribute("aria-expanded")).toBe("true");
    expect(replacement.dataset.hoverCardState).toBe("open");
    expect(computePosition.mock.calls.at(-1)[0]).toBe(replacement);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("restores focus when the active focused trigger is replaced", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    trigger().focus();
    await waitUntil(() => content().dataset.state === "open");
    const oldTrigger = trigger();
    const replacement = oldTrigger.cloneNode(true);

    oldTrigger.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(oldTrigger);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);

    expect(document.activeElement).toBe(replacement);
    expect(isOpen()).toBe(true);
});

test.serial("rolls back when positioning a replacement trigger fails", async () => {
    await mount({ openDelay: 0, closeDelay: 100 });
    mouse("mouseenter");
    await wait(0);
    const handleError = mock(() => {});
    mounted.application.handleError = handleError;
    computePosition.mockRejectedValueOnce(new Error("replacement positioning failed"));
    const oldTrigger = trigger();
    const replacement = oldTrigger.cloneNode(true);

    oldTrigger.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(oldTrigger);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);
    await wait(0);

    expect(mounted.controller.isOpen).toBe(false);
    expect(content().hidden).toBe(true);
    expect(handleError).toHaveBeenCalledTimes(1);
});

test.serial("closes after a hovered trigger is removed even when another trigger remains", async () => {
    await mount({ openDelay: 0, closeDelay: 10 });
    const active = trigger();
    const fallback = active.cloneNode(true);
    active.after(fallback);
    mounted.controller.triggerTargetConnected(fallback);
    mouse("mouseenter", active);
    await waitUntil(() => content().dataset.state === "open");

    active.remove();
    mounted.controller.triggerTargetDisconnected(active);
    await waitUntil(() => !isOpen());

    expect(fallback.getAttribute("aria-expanded")).toBe("false");
    expect(content().hidden).toBe(true);
});

test.serial("does not let a closed trigger replacement steal a later active trigger", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    const first = trigger();
    const second = first.cloneNode(true);
    first.after(second);
    mounted.controller.triggerTargetConnected(second);
    mounted.controller.pointerEnter({ currentTarget: first });
    await waitUntil(() => content().dataset.state === "open");
    mounted.controller.close();
    await waitUntil(() => !isOpen());

    first.remove();
    mounted.controller.triggerTargetDisconnected(first);
    mounted.controller.pointerEnter({ currentTarget: second });
    await waitUntil(() => content().dataset.state === "open");

    const third = second.cloneNode(true);
    second.after(third);
    mounted.controller.triggerTargetConnected(third);
    await wait(0);

    expect(computePosition.mock.calls.at(-1)[0]).toBe(second);
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

test.serial("composing Escape leaves the hover card open", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    focus("focusin");
    trigger().focus();

    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    Object.defineProperty(event, "isComposing", { value: true });
    content().dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(isOpen()).toBe(true);
    expect(document.activeElement).toBe(trigger());
});

test.serial("closes on turbo:before-cache", async () => {
    await mount({ openDelay: 0, closeDelay: 0 });
    mouse("mouseenter");
    expect(isOpen()).toBe(true);
    await wait(0);
    expect(content().dataset.state).toBe("open");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(content().dataset.state).toBe("closed");
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
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>A content</div>
        </div>
        <div data-controller="hover-card" data-hover-card-open-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">B</span>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>B content</div>
        </div>`,
    );

    const triggers = [...document.querySelectorAll('[data-hover-card-target="trigger"]')];
    const contents = [...document.querySelectorAll('[data-hover-card-target="content"]')];

    triggers[0].dispatchEvent(new MouseEvent("mouseenter", { bubbles: true }));

    expect(contents[0].hidden).toBe(false);
    expect(contents[1].hidden).toBe(true);
});

test.serial("Escape inside an open modal closes only the hover card when the hover card listener runs first", async () => {
    mounted = await mountMultipleControllers(
        {
            "hover-card": HoverCardController,
            modal: ModalController,
        },
        `
        <div id="modal" data-controller="modal"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
                        <span id="hover-trigger" data-hover-card-target="trigger" data-action="focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0" aria-expanded="false">User</span>
                        <div id="hover-content" data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>Preview</div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    const modal = mounted.getController("modal", document.getElementById("modal"));
    const hoverCard = mounted.getController("hover-card", document.querySelector('[data-controller="hover-card"]'));

    document.getElementById("modal-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);

    document.getElementById("hover-trigger").dispatchEvent(new Event("focusin", { bubbles: true, cancelable: true }));
    await wait(0);

    press("Escape", document.getElementById("hover-content"));
    await wait(10);

    expect(hoverCard.isOpen).toBe(false);
    expect(modal.isOpen).toBe(true);
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
            <div data-hover-card-target="content" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" data-state="closed" data-motion="default" hidden inert>Preview</div>
        </div>`,
    );
}

async function waitUntil(predicate, timeout = 500) {
    const deadline = Date.now() + timeout;

    while (!predicate()) {
        if (Date.now() >= deadline) throw new Error("Timed out waiting for Hover Card state");
        await wait(5);
    }
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}
