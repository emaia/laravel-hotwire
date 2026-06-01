import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
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
});

test.serial("toggles open and closed on the trigger", async () => {
    await mount();

    clickTrigger();
    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");

    clickTrigger();
    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
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

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
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
            <div data-dropdown-target="menu" class="hidden">
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
