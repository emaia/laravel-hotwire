import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

const floatingCleanup = mock(() => {});
const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const defaultComputePosition = async () => ({ x: 16, y: 24, placement: "bottom-start" });
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

const { default: DropdownController } = await import("../../resources/js/controllers/dropdown_controller.js");

let mounted;
let originalMatchMedia;

beforeEach(() => {
    originalMatchMedia = globalThis.window?.matchMedia;
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
    if (globalThis.window) window.matchMedia = originalMatchMedia;
});

const trigger = () => document.querySelector('[data-dropdown-target="trigger"]');
const menu = () => document.querySelector('[data-dropdown-target="menu"]');
const isOpen = () => !menu().hidden;

function clickTrigger() {
    trigger().dispatchEvent(new MouseEvent("click", { bubbles: true }));
}

function press(key) {
    document.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true }));
}

function pressTarget(element, key) {
    element.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

// --- open / close ---

test.serial("starts closed with aria-expanded false", async () => {
    await mount();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(menu().dataset.state).toBe("closed");
    expect(menu().hasAttribute("inert")).toBe(true);
});

test.serial("connects without a menu target and wires one added later", async () => {
    const consoleError = console.error;
    const error = mock(() => {});
    console.error = error;

    try {
        mounted = await mountController(
            "dropdown",
            DropdownController,
            `
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            </div>`,
        );

        expect(error).not.toHaveBeenCalled();

        const menuEl = document.createElement("div");
        menuEl.dataset.dropdownTarget = "menu";
        menuEl.dataset.state = "closed";
        menuEl.dataset.motion = "default";
        menuEl.hidden = true;
        menuEl.setAttribute("inert", "");
        menuEl.innerHTML = '<a href="#x">x</a>';
        mounted.root.append(menuEl);
        mounted.controller.menuTargetConnected(menuEl);

        clickTrigger();

        expect(menuEl.hidden).toBe(false);
        expect(trigger().getAttribute("aria-expanded")).toBe("true");
    } finally {
        console.error = consoleError;
    }
});

test.serial("toggles open and closed on the trigger", async () => {
    await mount();

    clickTrigger();
    await wait(0);
    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(menu().dataset.state).toBe("open");

    clickTrigger();
    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(menu().dataset.state).toBe("closed");
});

test.serial("toggles from an as-child sidebar menu button trigger", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button type="button"
                    data-slot="sidebar-menu-button"
                    data-sidebar="menu-button"
                    data-dropdown-target="trigger"
                    data-action="dropdown#toggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="account-menu"
                    data-state="on">
                <span>Ednilson Maia</span>
                <svg data-slot="dropdown-trigger-icon"></svg>
            </button>
            <div id="account-menu"
                 data-slot="dropdown-menu"
                 data-state="closed"
                 data-motion="default"
                 data-side="top"
                 data-align="start"
                 data-dropdown-target="menu"
                 data-dropdown-side-value="top"
                 data-dropdown-align-value="start" hidden inert>
                <a href="/profile">Profile</a>
            </div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(trigger().dataset.state).toBe("on");
    expect(trigger().dataset.dropdownState).toBe("open");
    expect(menu().dataset.state).toBe("open");
});

test.serial("delegates trigger clicks when no data-action is present", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button type="button" data-dropdown-target="trigger" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
        </div>`,
    );

    clickTrigger();

    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
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

test.serial("a synchronous close prevents deferred positioning from starting", async () => {
    await mount();

    mounted.controller.open();
    mounted.controller.close();
    await wait(0);

    expect(autoUpdate).not.toHaveBeenCalled();
    expect(menu().hidden).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
});

test.serial("starts floating positioning when opened and stops when closed", async () => {
    await mount();

    clickTrigger();
    await wait(0);

    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalled();
    expect(menu().style.left).toBe("16px");
    expect(menu().style.top).toBe("24px");
    expect(menu().dataset.side).toBe("bottom");
    expect(menu().dataset.align).toBe("start");

    clickTrigger();

    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("passes dropdown positioning values to Floating UI", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown"
             data-dropdown-side-value="right"
             data-dropdown-align-value="end"
             data-dropdown-side-offset-value="12"
             data-dropdown-align-offset-value="-4"
             data-dropdown-strategy-value="fixed"
             data-dropdown-flip-value="false"
             data-dropdown-shift-value="false">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    const options = computePosition.mock.calls[0][2];
    expect(options.placement).toBe("right-end");
    expect(options.strategy).toBe("fixed");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 12, crossAxis: -4 });
    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

test.serial("reads positioning values from the menu target", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu"
                 data-dropdown-side-value="right"
                 data-dropdown-align-value="end"
                 data-dropdown-side-offset-value="12"
                 data-dropdown-align-offset-value="-4"
                 data-dropdown-strategy-value="fixed"
                 data-dropdown-flip-value="false"
                 data-dropdown-shift-value="false"
                 data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    const options = computePosition.mock.calls[0][2];
    expect(options.placement).toBe("right-end");
    expect(options.strategy).toBe("fixed");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 12, crossAxis: -4 });
    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

test.serial("uses responsive side and align overrides and recalculates while open", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu"
                 data-dropdown-side-value="right"
                 data-dropdown-align-value="start"
                 data-dropdown-mobile-side-value="bottom"
                 data-dropdown-mobile-align-value="end"
                 data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
        </div>`,
    );
    const media = installMatchMedia(true);
    mounted.controller.connectMediaQuery();
    mounted.controller.syncState();

    clickTrigger();
    await wait(0);

    expect(computePosition.mock.calls[0][2].placement).toBe("bottom-end");

    media.setMatches(false);
    await wait(0);

    expect(floatingCleanup).toHaveBeenCalledTimes(1);
    expect(computePosition.mock.calls.at(-1)[2].placement).toBe("right-start");
});

test.serial("uses collapsed side and align overrides inside a collapsed sidebar", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-slot="sidebar" data-state="collapsed">
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
                <div data-dropdown-target="menu"
                     data-dropdown-side-value="top"
                     data-dropdown-align-value="start"
                     data-dropdown-collapsed-side-value="right"
                     data-dropdown-collapsed-align-value="end"
                     data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
            </div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    expect(computePosition.mock.calls[0][2].placement).toBe("right-end");
});

test.serial("uses collapsed overrides inside an icon-collapsible sidebar rail", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-slot="sidebar" data-collapsible="icon">
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
                <div data-dropdown-target="menu"
                     data-dropdown-side-value="top"
                     data-dropdown-align-value="start"
                     data-dropdown-collapsed-side-value="right"
                     data-dropdown-collapsed-align-value="end"
                     data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
            </div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    expect(computePosition.mock.calls[0][2].placement).toBe("right-end");
});

test.serial("uses collapsed overrides when only the sidebar wrapper carries collapsed state", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-slot="sidebar-wrapper" data-state="collapsed">
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
                <div data-dropdown-target="menu"
                     data-dropdown-side-value="top"
                     data-dropdown-align-value="start"
                     data-dropdown-collapsed-side-value="right"
                     data-dropdown-collapsed-align-value="end"
                     data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
            </div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    expect(computePosition.mock.calls[0][2].placement).toBe("right-end");
});

test.serial("uses collapsed overrides when sidebar has persisted collapsed state and icon collapsible mode", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-slot="sidebar" data-sidebar-collapsible="icon" data-state="collapsed" data-collapsible="">
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
                <div data-dropdown-target="menu"
                     data-dropdown-side-value="top"
                     data-dropdown-align-value="start"
                     data-dropdown-collapsed-side-value="right"
                     data-dropdown-collapsed-align-value="end"
                     data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
            </div>
        </div>`,
    );

    clickTrigger();
    await wait(0);

    expect(computePosition.mock.calls[0][2].placement).toBe("right-end");
});

test.serial("starts open when open-value is true", async () => {
    await mount({ open: true });

    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
});

// --- keyboard behavior ---

test.serial("does not intercept arrow keys on the trigger", async () => {
    await mount();

    const event = new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true, cancelable: true });
    trigger().dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(isOpen()).toBe(false);
});

test.serial("does not move focus with arrow keys, Home or End inside the menu", async () => {
    await mount();
    clickTrigger();
    await wait(0);

    const link = menu().querySelector("a");
    link.focus();

    for (const key of ["ArrowDown", "ArrowUp", "Home", "End"]) {
        const event = new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true });
        link.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
        expect(document.activeElement).toBe(link);
    }
});

test.serial("does not intercept arrow keys from form controls inside the menu", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown" data-dropdown-close-on-select-value="false">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Filters</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                <input id="filter" type="text" value="abc">
                <button id="apply" type="button">Apply</button>
            </div>
        </div>`,
    );

    clickTrigger();
    await wait(0);
    const input = document.getElementById("filter");
    input.focus();

    const event = new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true, cancelable: true });
    input.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(document.activeElement).toBe(input);
});

// --- dismissal ---

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

test.serial("stays open when clicking a non-actionable element inside the menu", async () => {
    await mount();
    clickTrigger();

    menu()
        .querySelector("span")
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(true);
});

test.serial("Escape closes and returns focus to the trigger", async () => {
    await mount();
    clickTrigger();
    expect(isOpen()).toBe(true);

    press("Escape");

    expect(isOpen()).toBe(false);
    expect(document.activeElement).toBe(trigger());
});

test.serial("composing Escape leaves the dropdown open", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const input = document.createElement("input");
    menu().appendChild(input);
    input.focus();

    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    Object.defineProperty(event, "isComposing", { value: true });
    input.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(isOpen()).toBe(true);
    expect(document.activeElement).toBe(input);
});

test.serial("Escape inside an open drawer closes only the dropdown first", async () => {
    mounted = await mountMultipleControllers(
        {
            drawer: DrawerController,
            dropdown: DropdownController,
        },
        `
        <div data-controller="drawer"
             data-drawer-lock-scroll-class="overflow-hidden">
            <button id="drawer-trigger" data-drawer-target="trigger" data-action="drawer#toggle">Open drawer</button>
            <div data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-drawer-target="backdrop" data-action="click->drawer#clickOutside" class="opacity-0"></div>
                <div data-drawer-target="dialog" class="translate-x-full">
                    <div data-controller="dropdown">
                        <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
                        <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                            <a id="nested-dropdown-item" href="#item">Item</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    document.getElementById("drawer-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);
    clickTrigger();

    pressTarget(document.getElementById("nested-dropdown-item"), "Escape");
    await wait(10);

    expect(isOpen()).toBe(false);
    expect(mounted.controller.isOpen).toBe(true);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("Escape inside an open modal closes only the dropdown when the dropdown listener runs first", async () => {
    mounted = await mountMultipleControllers(
        {
            dropdown: DropdownController,
            modal: ModalController,
        },
        `
        <div id="modal" data-controller="modal"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <div data-controller="dropdown">
                        <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
                        <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                            <a id="modal-dropdown-item" href="#item">Item</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    const modal = mounted.getController("modal", document.getElementById("modal"));

    document.getElementById("modal-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);
    clickTrigger();

    pressTarget(document.getElementById("modal-dropdown-item"), "Escape");
    await wait(10);

    expect(isOpen()).toBe(false);
    expect(modal.isOpen).toBe(true);
});

test.serial("closes on turbo:before-cache", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    expect(isOpen()).toBe(true);
    expect(menu().dataset.state).toBe("open");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(menu().dataset.state).toBe("closed");
    expect(floatingCleanup).toHaveBeenCalled();
});

test.serial("disconnect cleans up floating positioning", async () => {
    await mount();
    clickTrigger();
    await wait(0);

    mounted.controller.disconnect();

    expect(floatingCleanup).toHaveBeenCalled();
});

// --- close on select ---

test.serial("closes when an actionable item is clicked (default)", async () => {
    await mount();
    clickTrigger();

    menu()
        .querySelector("a")
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(false);
});

test.serial("keeps open on item click when close-on-select is false", async () => {
    await mount({ closeOnSelect: false });
    clickTrigger();

    menu()
        .querySelector("a")
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(isOpen()).toBe(true);
});

test.serial("keeps open when a clicked item removes itself and close-on-select is false", async () => {
    await mount({ closeOnSelect: false });
    clickTrigger();
    const item = menu().querySelector("a");
    item.addEventListener("click", () => item.remove());

    item.dispatchEvent(new MouseEvent("click", { bubbles: true, composed: true }));

    expect(isOpen()).toBe(true);
});

// --- multiple instances ---

test.serial("dropdowns operate independently", async () => {
    mounted = await mountControllers();

    const triggers = [...document.querySelectorAll('[data-dropdown-target="trigger"]')];
    const menus = [...document.querySelectorAll('[data-dropdown-target="menu"]')];

    triggers[0].dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(menus[0].hidden).toBe(false);
    expect(menus[1].hidden).toBe(true);
});

test.serial("clicking another dropdown closes the open dropdown", async () => {
    mounted = await mountControllers();
    const triggers = [...document.querySelectorAll('[data-dropdown-target="trigger"]')];
    const menus = [...document.querySelectorAll('[data-dropdown-target="menu"]')];

    triggers[0].dispatchEvent(new MouseEvent("click", { bubbles: true }));
    triggers[1].dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(menus[0].hidden).toBe(true);
    expect(menus[1].hidden).toBe(false);
});

// --- close action ---

test.serial("the close action dismisses the menu", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown" data-dropdown-close-on-select-value="false">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                <button type="button" data-action="dropdown#close">Apply</button>
            </div>
        </div>`,
    );

    const menuEl = document.querySelector('[data-dropdown-target="menu"]');
    document
        .querySelector('[data-dropdown-target="trigger"]')
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));
    expect(menuEl.hidden).toBe(false);

    menuEl.querySelector("button").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(menuEl.hidden).toBe(true);
});

// --- focus return with multiple triggers ---

test.serial("Escape returns focus to the trigger that opened the menu", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button id="t1" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">One</button>
            <button id="t2" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Two</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
        </div>`,
    );

    document.getElementById("t2").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    press("Escape");

    expect(document.activeElement).toBe(document.getElementById("t2"));
});

// --- target lifecycle survives DOM replacement (Turbo morph) ---

test.serial("re-attaches the menu click listener when the menu node is replaced", async () => {
    await mount();
    clickTrigger();
    expect(isOpen()).toBe(true);

    // Simulate a Turbo morph that swaps the menu node while keeping the
    // controller's root in place. Stimulus's MutationObserver should fire
    // menuTargetDisconnected/Connected on us, so onMenuClick rebinds.
    const oldMenu = menu();
    const replacement = oldMenu.cloneNode(true);
    oldMenu.replaceWith(replacement);
    mounted.controller.menuTargetDisconnected(oldMenu);
    mounted.controller.menuTargetConnected(replacement);

    replacement.querySelector("a").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(replacement.hidden).toBe(true);
});

test.serial("closes logical state when the menu target is removed without replacement", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const removed = menu();

    removed.remove();
    mounted.controller.menuTargetDisconnected(removed);

    expect(mounted.controller.openValue).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(trigger().dataset.dropdownState).toBe("closed");
});

// --- before-cache with pending presence ---

test.serial("turbo:before-cache cancels pending positioning and hides cleanly", async () => {
    const positioning = deferred();
    computePosition.mockImplementation(() => positioning.promise);
    await mount();

    mounted.controller.open();
    expect(menu().hidden).toBe(false);
    expect(menu().dataset.state).toBe("closed");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(menu().hidden).toBe(true);
    expect(menu().hasAttribute("inert")).toBe(true);
    expect(menu().dataset.state).toBe("closed");

    positioning.resolve({ x: 99, y: 88, placement: "right-end" });
    await wait(0);

    expect(menu().hidden).toBe(true);
    expect(menu().dataset.state).toBe("closed");
});

// --- state-driven presence and resolved positioning ---

test.serial("waits for the first placement before entering", async () => {
    const positioning = deferred();
    computePosition.mockImplementation(() => positioning.promise);
    await mount();

    clickTrigger();

    expect(menu().hidden).toBe(false);
    expect(menu().dataset.state).toBe("closed");
    expect(menu().hasAttribute("inert")).toBe(true);

    positioning.resolve({ x: 16, y: 24, placement: "top-start" });
    await wait(0);

    expect(menu().dataset.state).toBe("open");
    expect(menu().dataset.side).toBe("top");
    expect(menu().hasAttribute("inert")).toBe(false);
});

test.serial("rolls logical and rendered state back when the first placement fails", async () => {
    computePosition.mockRejectedValueOnce(new Error("positioning failed"));
    await mount();
    const handleError = mock(() => {});
    mounted.application.handleError = handleError;

    clickTrigger();
    await wait(0);
    await wait(0);

    expect(mounted.controller.openValue).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(trigger().dataset.dropdownState).toBe("closed");
    expect(menu().dataset.state).toBe("closed");
    expect(menu().hidden).toBe(true);
    expect(menu().hasAttribute("inert")).toBe(true);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
    expect(handleError).toHaveBeenCalledTimes(1);
});

test.serial("keeps the resolved placement and top-layer resources until exit finishes", async () => {
    const exitMotion = fakeAnimation();
    computePosition.mockImplementation(async () => ({ x: 16, y: 24, placement: "top-end" }));
    await mount();
    clickTrigger();
    await wait(0);
    menu().getAnimations = () => menu().dataset.state === "closed" ? [exitMotion.animation] : [];

    mounted.controller.close();

    expect(menu().dataset.state).toBe("closed");
    expect(menu().dataset.side).toBe("top");
    expect(menu().dataset.align).toBe("end");
    expect(menu().hidden).toBe(false);
    expect(floatingCleanup).not.toHaveBeenCalled();

    exitMotion.finish();
    await wait(0);

    expect(menu().hidden).toBe(true);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("reopening during exit cancels the stale hide and teardown", async () => {
    const exitMotion = fakeAnimation();
    await mount();
    clickTrigger();
    await wait(0);
    menu().getAnimations = () => menu().dataset.state === "closed" ? [exitMotion.animation] : [];

    mounted.controller.close();
    mounted.controller.open();
    await wait(0);

    exitMotion.finish();
    await wait(0);

    expect(menu().dataset.state).toBe("open");
    expect(menu().hidden).toBe(false);
    expect(floatingCleanup).not.toHaveBeenCalled();
});

test.serial("reopening after a breakpoint change during exit uses the new profile", async () => {
    const exitMotion = fakeAnimation();
    await mount();
    const media = installMatchMedia(false);
    mounted.controller.connectMediaQuery();
    clickTrigger();
    await wait(0);
    menu().getAnimations = () => menu().dataset.state === "closed" ? [exitMotion.animation] : [];

    mounted.controller.close();
    media.setMatches(true);
    mounted.controller.open();
    await wait(0);

    expect(computePosition.mock.calls.at(-1)[2].placement).toBe("bottom-start");
    expect(autoUpdate).toHaveBeenCalledTimes(2);
});

test.serial("an obsolete positioning restart cannot roll back a newer restart", async () => {
    const first = deferred();
    const second = deferred();
    await mount();
    clickTrigger();
    await wait(0);
    computePosition.mockImplementationOnce(() => first.promise).mockImplementationOnce(() => second.promise);

    mounted.controller.onMediaChange();
    await wait(0);
    mounted.controller.onMediaChange();
    await wait(0);

    second.resolve({ x: 30, y: 40, placement: "right-start" });
    await wait(0);
    first.resolve({ x: 90, y: 100, placement: "left-end" });
    await wait(0);

    expect(mounted.controller.openValue).toBe(true);
    expect(menu().hidden).toBe(false);
    expect(menu().dataset.state).toBe("open");
    expect(menu().style.left).toBe("30px");
});

test.serial("rolls back when breakpoint repositioning fails", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const handleError = mock(() => {});
    mounted.application.handleError = handleError;
    computePosition.mockRejectedValueOnce(new Error("breakpoint positioning failed"));

    mounted.controller.onMediaChange();
    await wait(0);
    await wait(0);

    expect(mounted.controller.openValue).toBe(false);
    expect(menu().hidden).toBe(true);
    expect(handleError).toHaveBeenCalledTimes(1);
});

test.serial("re-anchors an open menu when its active trigger is replaced", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const oldTrigger = trigger();
    const replacement = oldTrigger.cloneNode(true);

    oldTrigger.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(oldTrigger);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);

    expect(replacement.getAttribute("aria-expanded")).toBe("true");
    expect(replacement.dataset.dropdownState).toBe("open");
    expect(computePosition.mock.calls.at(-1)[0]).toBe(replacement);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("keeps a replacement of the active secondary trigger as the anchor", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button id="first-trigger" data-dropdown-target="trigger" data-action="dropdown#toggle">First</button>
            <button id="active-trigger" data-dropdown-target="trigger" data-action="dropdown#toggle">Active</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>Menu</div>
        </div>`,
    );
    const active = document.getElementById("active-trigger");
    active.click();
    await wait(0);
    const replacement = active.cloneNode(true);

    active.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(active);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);

    expect(computePosition.mock.calls.at(-1)[0]).toBe(replacement);
    press("Escape");
    expect(document.activeElement).toBe(replacement);
});

test.serial("re-anchors to another trigger when the active trigger is removed", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button id="first-trigger" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">First</button>
            <button id="active-trigger" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Active</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#x">x</a></div>
        </div>`,
    );
    document.getElementById("active-trigger").click();
    await wait(0);
    const removed = document.getElementById("active-trigger");

    removed.remove();
    mounted.controller.triggerTargetDisconnected(removed);
    await wait(0);

    expect(mounted.controller.openValue).toBe(true);
    expect(computePosition.mock.calls.at(-1)[0]).toBe(document.getElementById("first-trigger"));
    expect(menu().hidden).toBe(false);
});

test.serial("mobile positioning overrides the complete collapsed profile", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-slot="sidebar" data-state="collapsed">
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
                <div data-dropdown-target="menu"
                     data-state="closed"
                     data-motion="default"
                     data-dropdown-side-value="top"
                     data-dropdown-align-value="start"
                     data-dropdown-mobile-side-value="bottom"
                     data-dropdown-collapsed-side-value="right"
                     data-dropdown-collapsed-align-value="end"
                     hidden inert><a href="#x">x</a></div>
            </div>
        </div>`,
    );
    installMatchMedia(true);
    mounted.controller.connectMediaQuery();

    clickTrigger();
    await wait(0);

    expect(computePosition.mock.calls[0][2].placement).toBe("bottom-start");
});

// --- helpers ---

async function mount({ open = false, closeOnSelect = null } = {}) {
    const openAttr = open ? 'data-dropdown-open-value="true"' : "";
    const cosAttr = closeOnSelect === null ? "" : `data-dropdown-close-on-select-value="${closeOnSelect}"`;

    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown" ${openAttr} ${cosAttr}>
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-haspopup="true" aria-expanded="false">
                Menu
            </button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                <span>label</span>
                <a href="#item">Item</a>
                <button type="button">Action</button>
            </div>
        </div>`,
    );
}

async function mountControllers() {
    const { mountControllers: mount } = await import("../../resources/js/helpers/test_stimulus.js");
    return mount(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">A</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#a">a</a></div>
        </div>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">B</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#b">b</a></div>
        </div>`,
    );
}

function installMatchMedia(initialMatches) {
    let matches = initialMatches;
    const listeners = new Set();
    const media = {
        get matches() {
            return matches;
        },
        media: "(max-width: 767px)",
        addEventListener: (_event, listener) => listeners.add(listener),
        removeEventListener: (_event, listener) => listeners.delete(listener),
        setMatches(next) {
            matches = next;
            listeners.forEach((listener) => listener({ matches, media: this.media }));
        },
    };

    window.matchMedia = mock(() => media);

    return media;
}

function fakeAnimation() {
    const finished = deferred();

    return {
        animation: {
            effect: { getComputedTiming: () => ({ endTime: 100 }) },
            finished: finished.promise,
            playState: "running",
        },
        finish: finished.resolve,
    };
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}
