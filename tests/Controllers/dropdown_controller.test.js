import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

const floatingCleanup = mock(() => {});
const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const computePosition = mock(async () => ({ x: 16, y: 24, placement: "bottom-start" }));
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
const isOpen = () => !menu().classList.contains("hidden");

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
    expect(menu().dataset.open).toBe("false");
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
        menuEl.className = "hidden";
        menuEl.innerHTML = '<a href="#x">x</a>';
        mounted.root.append(menuEl);
        mounted.controller.menuTargetConnected(menuEl);

        clickTrigger();

        expect(menuEl.classList.contains("hidden")).toBe(false);
        expect(trigger().getAttribute("aria-expanded")).toBe("true");
    } finally {
        console.error = consoleError;
    }
});

test.serial("toggles open and closed on the trigger", async () => {
    await mount();

    clickTrigger();
    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(menu().dataset.open).toBe("true");

    clickTrigger();
    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(menu().dataset.open).toBe("false");
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
                    data-state="closed">
                <span>Ednilson Maia</span>
                <svg data-slot="dropdown-trigger-icon"></svg>
            </button>
            <div id="account-menu"
                 data-slot="dropdown-menu"
                 data-open="false"
                 data-side="top"
                 data-align="start"
                 data-dropdown-target="menu"
                 data-dropdown-side-value="top"
                 data-dropdown-align-value="start">
                <a href="/profile">Profile</a>
            </div>
        </div>`,
    );

    clickTrigger();

    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(trigger().dataset.state).toBe("open");
    expect(menu().dataset.open).toBe("true");
});

test.serial("delegates trigger clicks when no data-action is present", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button type="button" data-dropdown-target="trigger" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" data-open="false" class="hidden"><a href="#x">x</a></div>
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
            <div data-dropdown-target="menu" class="hidden"><a href="#x">x</a></div>
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
                 class="hidden"><a href="#x">x</a></div>
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
                 class="hidden"><a href="#x">x</a></div>
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
                     class="hidden"><a href="#x">x</a></div>
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
                     class="hidden"><a href="#x">x</a></div>
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
                     class="hidden"><a href="#x">x</a></div>
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
                     class="hidden"><a href="#x">x</a></div>
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
            <div data-dropdown-target="menu" class="hidden">
                <input id="filter" type="text" value="abc">
                <button id="apply" type="button">Apply</button>
            </div>
        </div>`,
    );

    clickTrigger();
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

test.serial("Escape inside an open drawer closes only the dropdown first", async () => {
    mounted = await mountMultipleControllers(
        {
            drawer: DrawerController,
            dropdown: DropdownController,
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
                    <div data-controller="dropdown">
                        <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
                        <div data-dropdown-target="menu" class="hidden">
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
                    <div data-controller="dropdown">
                        <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
                        <div data-dropdown-target="menu" class="hidden">
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
    expect(isOpen()).toBe(true);
    expect(menu().dataset.open).toBe("true");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(menu().dataset.open).toBe("false");
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

// --- multiple instances ---

test.serial("dropdowns operate independently", async () => {
    mounted = await mountControllers();

    const triggers = [...document.querySelectorAll('[data-dropdown-target="trigger"]')];
    const menus = [...document.querySelectorAll('[data-dropdown-target="menu"]')];

    triggers[0].dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(menus[0].classList.contains("hidden")).toBe(false);
    expect(menus[1].classList.contains("hidden")).toBe(true);
});

// --- custom hidden class ---

test.serial("uses a custom hidden class", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown" data-dropdown-hidden-class="is-closed">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" class="is-closed"><a href="#x">x</a></div>
        </div>`,
    );

    const menuEl = document.querySelector('[data-dropdown-target="menu"]');
    document
        .querySelector('[data-dropdown-target="trigger"]')
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(menuEl.classList.contains("is-closed")).toBe(false);
});

// --- close action ---

test.serial("the close action dismisses the menu", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown" data-dropdown-close-on-select-value="false">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" class="hidden">
                <button type="button" data-action="dropdown#close">Apply</button>
            </div>
        </div>`,
    );

    const menuEl = document.querySelector('[data-dropdown-target="menu"]');
    document
        .querySelector('[data-dropdown-target="trigger"]')
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));
    expect(menuEl.classList.contains("hidden")).toBe(false);

    menuEl.querySelector("button").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(menuEl.classList.contains("hidden")).toBe(true);
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
            <div data-dropdown-target="menu" class="hidden"><a href="#x">x</a></div>
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

    expect(replacement.classList.contains("hidden")).toBe(true);
});

// --- before-cache with a pending transition ---

test.serial("turbo:before-cache cancels a pending transition and hides cleanly", async () => {
    mounted = await mountController(
        "dropdown",
        DropdownController,
        `
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">M</button>
            <div data-dropdown-target="menu" class="hidden"
                 data-transition-enter="t-enter" data-transition-enter-from="ef" data-transition-enter-to="et">
                <a href="#x">x</a>
            </div>
        </div>`,
    );

    const menuEl = document.querySelector('[data-dropdown-target="menu"]');

    // Start opening: the enter transition applies its active/from classes and
    // schedules a frame, but it has not completed yet.
    mounted.controller.open();
    expect(menuEl.classList.contains("t-enter")).toBe(true);
    expect(menuEl.classList.contains("ef")).toBe(true);

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    // Cancelled and hidden, with no stale transition classes left behind.
    expect(menuEl.classList.contains("hidden")).toBe(true);
    expect(menuEl.classList.contains("t-enter")).toBe(false);
    expect(menuEl.classList.contains("ef")).toBe(false);

    // The cancelled frame must not fire and re-dirty the element.
    await wait(0);
    expect(menuEl.classList.contains("hidden")).toBe(true);
    expect(menuEl.classList.contains("et")).toBe(false);
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
            <div data-dropdown-target="menu" data-open="${open ? "true" : "false"}" class="hidden">
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
            <div data-dropdown-target="menu" class="hidden"><a href="#a">a</a></div>
        </div>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">B</button>
            <div data-dropdown-target="menu" class="hidden"><a href="#b">b</a></div>
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
