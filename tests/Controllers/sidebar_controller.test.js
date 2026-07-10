import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import SidebarController from "../../resources/js/controllers/sidebar_controller.js";

let mounted;
let originalMatchMedia;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.cookie = "sidebar_state=; path=/; max-age=0";
    if (originalMatchMedia) window.matchMedia = originalMatchMedia;
    document.body.className = "";
});


async function mount(html = template()) {
    mounted = await mountController("sidebar", SidebarController, html);
    await wait(0);
}

function forceMobile() {
    mounted.controller.mediaQuery = { matches: true };
    mounted.controller.sync();
}

function template(open = true) {
    return `
        <div data-controller="sidebar"
             data-sidebar-open-value="${open}"
             data-action="keydown@window->sidebar#shortcut"
             data-state="${open ? "expanded" : "collapsed"}">
            <button data-slot="sidebar-trigger" data-action="click->sidebar#toggle">Toggle</button>
            <div data-slot="sidebar"
                 data-sidebar-collapsible="offcanvas"
                 data-state="${open ? "expanded" : "collapsed"}"
                 data-collapsible="${open ? "" : "offcanvas"}"></div>
        </div>
    `;
}

function mobileTemplate(open = true) {
    return `
        <div data-controller="sidebar"
             data-sidebar-open-value="${open}"
             data-sidebar-open-duration-value="1"
             data-sidebar-close-duration-value="1"
             data-sidebar-hidden-class="pointer-events-none"
             data-sidebar-visible-class="pointer-events-auto"
             data-sidebar-backdrop-hidden-class="opacity-0"
             data-sidebar-backdrop-visible-class="opacity-100"
             data-sidebar-dialog-hidden-class="-translate-x-full"
             data-sidebar-dialog-visible-class="translate-x-0"
             data-sidebar-lock-scroll-class="overflow-hidden"
             data-state="${open ? "expanded" : "collapsed"}">
            <button data-slot="sidebar-trigger" data-action="click->sidebar#toggle">Toggle</button>
            <div data-slot="sidebar"
                 data-sidebar-target="modal"
                 data-sidebar-collapsible="offcanvas"
                 data-state="${open ? "expanded" : "collapsed"}"
                 data-collapsible="${open ? "" : "offcanvas"}"
                 hidden>
                <div data-slot="sidebar-backdrop" data-sidebar-target="backdrop" data-action="click->sidebar#clickOutside"></div>
                <div data-slot="sidebar-container" data-sidebar-target="dialog">
                    <aside data-slot="sidebar-inner">Nav</aside>
                </div>
            </div>
        </div>
    `;
}

function mobileTemplateWithDurations({ open = true, openDuration = 1, closeDuration = 1 } = {}) {
    return mobileTemplate(open)
        .replace('data-sidebar-open-duration-value="1"', `data-sidebar-open-duration-value="${openDuration}"`)
        .replace('data-sidebar-close-duration-value="1"', `data-sidebar-close-duration-value="${closeDuration}"`);
}

function mockMobile(matches) {
    originalMatchMedia ??= window.matchMedia;
    const listeners = new Set();
    Object.defineProperty(window, "matchMedia", {
        configurable: true,
        writable: true,
        value: () => ({
            matches,
            media: "(max-width: 767px)",
            addEventListener: (_event, listener) => listeners.add(listener),
            removeEventListener: (_event, listener) => listeners.delete(listener),
        }),
    });
}

function root() {
    return document.querySelector("[data-controller~='sidebar']");
}

function sidebar() {
    return document.querySelector("[data-slot='sidebar']");
}

function trigger() {
    return document.querySelector("[data-slot='sidebar-trigger']");
}

function dialog() {
    return document.querySelector("[data-slot='sidebar-container']");
}

test("connect syncs expanded state to root, sidebar and trigger", async () => {
    await mount(template(true));

    expect(root().dataset.state).toBe("expanded");
    expect(sidebar().dataset.state).toBe("expanded");
    expect(sidebar().dataset.collapsible).toBe("");
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
});

test("toggle collapses and expands the sidebar", async () => {
    await mount(template(true));

    trigger().click();
    await wait(0);

    expect(root().dataset.state).toBe("collapsed");
    expect(sidebar().dataset.state).toBe("collapsed");
    expect(sidebar().dataset.collapsible).toBe("offcanvas");
    expect(trigger().getAttribute("aria-expanded")).toBe("false");

    trigger().click();
    await wait(0);

    expect(root().dataset.state).toBe("expanded");
    expect(sidebar().dataset.collapsible).toBe("");
});

test("Cmd/Ctrl+B toggles the sidebar and prevents default", async () => {
    await mount(template(true));

    const event = new KeyboardEvent("keydown", {
        key: "b",
        ctrlKey: true,
        bubbles: true,
        cancelable: true,
    });
    window.dispatchEvent(event);
    await wait(0);

    expect(event.defaultPrevented).toBe(true);
    expect(root().dataset.state).toBe("collapsed");
});

test("open changes are persisted to the sidebar cookie by default", async () => {
    await mount(template(true));

    trigger().click();
    await wait(0);

    expect(document.cookie).toContain("sidebar_state=false");
});

test("mobile toggle opens and closes the mobile drawer without changing desktop state", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();
    await wait(50);

    expect(root().dataset.state).toBe("expanded");
    expect(sidebar().dataset.mobileState).toBe("open");
    expect(sidebar().hidden).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");

    trigger().click();
    await wait(50);

    expect(root().dataset.state).toBe("expanded");
    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(true);
});

test("mobile open paints the offscreen state before sliding into view", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();

    expect(sidebar().dataset.mobileState).toBe("opening");
    expect(sidebar().hidden).toBe(false);
    expect(dialog().classList.contains("-translate-x-full")).toBe(true);
    expect(dialog().classList.contains("translate-x-0")).toBe(false);

    await wait(50);

    expect(sidebar().dataset.mobileState).toBe("open");
    expect(dialog().classList.contains("-translate-x-full")).toBe(false);
    expect(dialog().classList.contains("translate-x-0")).toBe(true);
});

test("mobile close keeps the overlay mounted while the panel slides out", async () => {
    mockMobile(true);
    await mount(mobileTemplateWithDurations({ closeDuration: 40 }));
    forceMobile();

    trigger().click();
    await wait(50);

    trigger().click();

    expect(sidebar().dataset.mobileState).toBe("closing");
    expect(sidebar().hidden).toBe(false);
    expect(dialog().classList.contains("-translate-x-full")).toBe(true);
    expect(dialog().classList.contains("translate-x-0")).toBe(false);

    await wait(10);

    expect(sidebar().dataset.mobileState).toBe("closing");
    expect(sidebar().hidden).toBe(false);

    await wait(60);

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(true);
});

test("mobile Escape and backdrop close only the mobile drawer", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();
    await wait(50);
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(50);

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(root().dataset.state).toBe("expanded");

    trigger().click();
    await wait(50);
    document.querySelector('[data-slot="sidebar-backdrop"]').click();
    await wait(50);

    expect(sidebar().dataset.mobileState).toBe("closed");
});
