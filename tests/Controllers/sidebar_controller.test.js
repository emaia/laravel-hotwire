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
    mounted.controller.handleMediaChange();
}

function template(open = true) {
    return `
        <div data-controller="sidebar"
             data-sidebar-open-value="${open}"
             data-action="keydown@window->sidebar#shortcut turbo:before-cache@window->sidebar#closeForCache turbo:before-render@window->sidebar#preserveStateForRender"
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
             data-sidebar-lock-scroll-class="overflow-hidden"
             data-state="${open ? "expanded" : "collapsed"}">
            <button data-slot="sidebar-trigger" data-action="click->sidebar#toggle">Toggle</button>
            <div data-slot="sidebar"
                 data-sidebar-target="modal"
                 data-sidebar-collapsible="offcanvas"
                  data-state="${open ? "expanded" : "collapsed"}"
                  data-mobile-state="closed"
                  data-motion="default"
                  data-collapsible="${open ? "" : "offcanvas"}"
                 hidden>
                <div data-slot="sidebar-backdrop" data-sidebar-target="backdrop" data-action="click->sidebar#clickOutside"></div>
                <div data-slot="sidebar-container" data-sidebar-target="dialog">
                    <aside data-slot="sidebar-inner"><a href="/reports" data-testid="nav-link">Reports</a></aside>
                </div>
            </div>
        </div>
    `;
}

function mockMobile(matches) {
    originalMatchMedia ??= window.matchMedia;
    const listeners = new Set();
    Object.defineProperty(window, "matchMedia", {
        configurable: true,
        writable: true,
        value: (query) => ({
            matches: query === "(max-width: 767px)" ? matches : false,
            media: query,
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

function navLink() {
    return document.querySelector("[data-testid='nav-link']");
}

async function waitForMobileState(state, timeout = 250) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeout) {
        if (sidebar().dataset.mobileState === state) return;

        await wait(5);
    }

    expect(sidebar().dataset.mobileState).toBe(state);
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

test("Turbo renders keep the current collapsed desktop state", async () => {
    await mount(template(true));

    trigger().click();
    await wait(0);

    const newBody = document.createElement("body");
    newBody.innerHTML = template(true);
    window.dispatchEvent(new CustomEvent("turbo:before-render", {
        detail: { newBody },
    }));

    const nextRoot = newBody.querySelector("[data-controller~='sidebar']");
    const nextSidebar = newBody.querySelector("[data-slot='sidebar']");

    expect(nextRoot.dataset.state).toBe("collapsed");
    expect(nextRoot.dataset.sidebarOpenValue).toBe("false");
    expect(nextSidebar.dataset.state).toBe("collapsed");
    expect(nextSidebar.dataset.collapsible).toBe("offcanvas");
});

test("Turbo before-cache does not hide the desktop sidebar", async () => {
    await mount(template(true));

    trigger().click();
    await wait(0);
    window.dispatchEvent(new CustomEvent("turbo:before-cache"));

    expect(sidebar().hidden).toBe(false);
    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().dataset.state).toBe("collapsed");
    expect(sidebar().dataset.collapsible).toBe("offcanvas");
});

test("Turbo before-cache synchronously closes the mobile overlay", async () => {
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();
    await waitForMobileState("open");
    const motion = fakeAnimation();
    dialog().getAnimations = () => sidebar().dataset.mobileState === "closed" ? [motion.animation] : [];
    mounted.controller.closeMobile();
    await wait(0);

    expect(sidebar().hidden).toBe(false);

    mounted.controller.closeForCache();

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().dataset.state).toBe("expanded");
    expect(sidebar().hidden).toBe(true);
    expect(sidebar().hasAttribute("inert")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);

    motion.finish();
    await wait(0);
    expect(sidebar().hidden).toBe(true);
});

test("mobile toggle opens and closes the mobile drawer without changing desktop state", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();
    await waitForMobileState("open");

    expect(root().dataset.state).toBe("expanded");
    expect(sidebar().dataset.mobileState).toBe("open");
    expect(sidebar().hidden).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");

    trigger().click();
    await waitForMobileState("closed");

    expect(root().dataset.state).toBe("expanded");
    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(true);
});

test("mobile open keeps the closed state inert until the enter frame", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(false);
    expect(sidebar().hasAttribute("inert")).toBe(true);

    await waitForMobileState("open");

    expect(sidebar().dataset.mobileState).toBe("open");
    expect(sidebar().hasAttribute("inert")).toBe(false);
});

test("mobile close keeps the overlay mounted while the panel slides out", async () => {
    mockMobile(true);
    await mount(mobileTemplate());
    forceMobile();

    trigger().click();
    await waitForMobileState("open");
    const motion = fakeAnimation();
    dialog().getAnimations = () => sidebar().dataset.mobileState === "closed" ? [motion.animation] : [];

    trigger().click();

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(false);
    expect(sidebar().hasAttribute("inert")).toBe(true);

    await wait(10);

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(false);

    motion.finish();
    await wait(0);

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(true);
});

test("mobile Escape and backdrop close only the mobile drawer", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();
    await waitForMobileState("open");
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await waitForMobileState("closed");

    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(root().dataset.state).toBe("expanded");

    trigger().click();
    await waitForMobileState("open");
    document.querySelector('[data-slot="sidebar-backdrop"]').click();
    await waitForMobileState("closed");

    expect(sidebar().dataset.mobileState).toBe("closed");
});

test("mobile link clicks wait for the close animation before navigating", async () => {
    mockMobile(true);
    await mount(mobileTemplate());
    forceMobile();

    trigger().click();
    await waitForMobileState("open");

    let navigations = 0;
    navLink().addEventListener("click", (event) => {
        navigations++;
        event.preventDefault();
    });
    const motion = fakeAnimation();
    dialog().getAnimations = () => sidebar().dataset.mobileState === "closed" ? [motion.animation] : [];

    navLink().click();

    expect(navigations).toBe(0);
    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(false);

    motion.finish();
    await wait(0);

    expect(navigations).toBe(1);
    expect(sidebar().dataset.mobileState).toBe("closed");
    expect(sidebar().hidden).toBe(true);
});

test("mobile target morph preserves focus return to the external trigger", async () => {
    await mount(mobileTemplate());
    forceMobile();

    trigger().click();
    await waitForMobileState("open");
    navLink().focus();
    document.querySelector('[data-slot="sidebar-backdrop"]').replaceWith(
        document.querySelector('[data-slot="sidebar-backdrop"]').cloneNode(true),
    );
    await wait(10);

    await mounted.controller.closeMobile();

    expect(document.activeElement).toBe(trigger());
});

test("mobile modified link clicks are not intercepted", async () => {
    mockMobile(true);
    await mount(mobileTemplate(true));
    forceMobile();

    trigger().click();
    await waitForMobileState("open");

    const event = new MouseEvent("click", { bubbles: true, cancelable: true, metaKey: true });
    navLink().dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(sidebar().dataset.mobileState).toBe("open");
});

function fakeAnimation() {
    const finished = deferred();

    return {
        animation: {
            effect: { getComputedTiming: () => ({ endTime: 100 }) },
            finished: finished.promise,
            playState: "running",
        },
        finish: () => finished.resolve(),
    };
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}
