import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import DropdownController from "../../resources/js/controllers/dropdown_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
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

// --- open / close ---

test.serial("starts closed with aria-expanded false", async () => {
    await mount();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(menu().dataset.open).toBe("false");
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

    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
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

test.serial("closes on turbo:before-cache", async () => {
    await mount();
    clickTrigger();
    expect(isOpen()).toBe(true);
    expect(menu().dataset.open).toBe("true");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(menu().dataset.open).toBe("false");
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
