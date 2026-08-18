import { afterEach, expect, test } from "bun:test";

import { mountController, mountControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import SidebarController from "../../resources/js/controllers/sidebar_controller.js";

let mounted;
let originalMatchMedia;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.cookie = "sidebar_state=; path=/; max-age=0";
    document.cookie = "panel_state=; path=/; max-age=0";
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

test("composing Cmd/Ctrl+B leaves the sidebar unchanged", async () => {
    await mount(template(true));

    const event = new KeyboardEvent("keydown", {
        key: "b",
        ctrlKey: true,
        bubbles: true,
        cancelable: true,
    });
    Object.defineProperty(event, "isComposing", { value: true });
    window.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(root().dataset.state).toBe("expanded");
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

test("toggling the outer provider leaves a nested provider untouched", async () => {
    await mountNested();

    byTestId("outer-trigger").click();
    await wait(0);

    expect(byTestId("outer-sidebar").dataset.state).toBe("collapsed");
    expect(byTestId("outer-trigger").getAttribute("aria-expanded")).toBe("false");
    expect(byTestId("inner-sidebar").dataset.state).toBe("expanded");
    expect(byTestId("inner-sidebar").dataset.collapsible).toBe("");
    expect(byTestId("inner-trigger").getAttribute("aria-expanded")).toBe("true");
    expect(innerRoot().dataset.state).toBe("expanded");
});

test("toggling a nested provider leaves the outer provider untouched", async () => {
    await mountNested();

    byTestId("inner-trigger").click();
    await wait(0);

    expect(byTestId("inner-sidebar").dataset.state).toBe("collapsed");
    expect(byTestId("inner-sidebar").dataset.collapsible).toBe("icon");
    expect(byTestId("inner-trigger").getAttribute("aria-expanded")).toBe("false");
    expect(byTestId("outer-sidebar").dataset.state).toBe("expanded");
    expect(byTestId("outer-trigger").getAttribute("aria-expanded")).toBe("true");
    expect(outerRoot().dataset.state).toBe("expanded");
});

test("the outer provider mobile sync leaves a nested provider untouched", async () => {
    await mountNested(nestedTemplate({ innerOpen: false }));

    mounted.controllers[0].mediaQuery = { matches: true };
    mounted.controllers[0].syncMobileState("open");
    await wait(0);

    expect(byTestId("outer-sidebar").dataset.mobileState).toBe("open");
    expect(byTestId("outer-trigger").getAttribute("aria-expanded")).toBe("true");
    expect(byTestId("inner-sidebar").dataset.mobileState).toBe("closed");
    expect(byTestId("inner-trigger").getAttribute("aria-expanded")).toBe("false");
});

test("the outer provider skips a nested provider mounted on a custom identifier", async () => {
    await mountNested(nestedTemplate({ innerIdentifier: "panel" }), "panel");

    byTestId("outer-trigger").click();
    await wait(0);

    expect(byTestId("outer-sidebar").dataset.state).toBe("collapsed");
    expect(byTestId("inner-sidebar").dataset.state).toBe("expanded");
    expect(byTestId("inner-trigger").getAttribute("aria-expanded")).toBe("true");

    byTestId("inner-trigger").click();
    await wait(0);

    expect(byTestId("inner-sidebar").dataset.state).toBe("collapsed");
    expect(byTestId("outer-sidebar").dataset.state).toBe("collapsed");
    expect(byTestId("outer-trigger").getAttribute("aria-expanded")).toBe("false");
});

test("the outer provider does not adopt the overlay of a nested provider", async () => {
    await mountNested(nestedTemplate({ innerIdentifier: "panel" }), "panel");

    const outer = mounted.controllers[0];
    outer.mediaQuery = { matches: true };
    outer.toggle();
    await wait(20);

    expect(outer.modalTarget).toBe(byTestId("outer-sidebar"));
    expect(byTestId("outer-sidebar").dataset.mobileState).toBe("open");
    expect(byTestId("inner-sidebar").dataset.mobileState).toBe("closed");
});

test("Turbo renders preserve the outer state without rewriting the nested provider", async () => {
    await mountNested(nestedTemplate({ outerOpen: false, innerOpen: false }));

    const newBody = document.createElement("body");
    newBody.innerHTML = nestedTemplate();

    mounted.controllers[0].preserveStateForRender({ detail: { newBody } });

    expect(byTestId("outer-sidebar", newBody).dataset.state).toBe("collapsed");
    expect(byTestId("outer-trigger", newBody).getAttribute("aria-expanded")).toBe("false");
    expect(innerRoot(newBody).dataset.state).toBe("expanded");
    expect(byTestId("inner-sidebar", newBody).dataset.state).toBe("expanded");
    expect(byTestId("inner-sidebar", newBody).dataset.collapsible).toBe("");
    expect(byTestId("inner-trigger", newBody).getAttribute("aria-expanded")).toBe("true");

    mounted.controllers[1].preserveStateForRender({ detail: { newBody } });

    expect(innerRoot(newBody).dataset.state).toBe("collapsed");
    expect(byTestId("inner-sidebar", newBody).dataset.state).toBe("collapsed");
    expect(byTestId("outer-sidebar", newBody).dataset.state).toBe("collapsed");
});

test("Turbo renders leave a next body where the outer provider is gone alone", async () => {
    await mountNested(nestedTemplate({ outerOpen: false }));

    const newBody = document.createElement("body");
    newBody.innerHTML = standaloneInnerTemplate();

    mounted.controllers[0].preserveStateForRender({ detail: { newBody } });

    expect(innerRoot(newBody).dataset.state).toBe("expanded");
    expect(innerRoot(newBody).dataset.sidebarOpenValue).toBe("true");
    expect(byTestId("inner-sidebar", newBody).dataset.state).toBe("expanded");
    expect(byTestId("inner-trigger", newBody).getAttribute("aria-expanded")).toBe("true");
});

async function mountNested(html = nestedTemplate(), extraIdentifier = null) {
    mounted = await mountControllers("sidebar", SidebarController, html);
    if (extraIdentifier) {
        mounted.application.register(extraIdentifier, SidebarController);
        await wait(0);
    }
}

// Mirrors what <hw:sidebar.provider> + <hw:sidebar> render, including the overlay targets
// namespaced to the provider identifier.
function nestedTemplate({ innerIdentifier = "sidebar", outerOpen = true, innerOpen = true } = {}) {
    return `
        <div data-controller="sidebar"
             data-slot="sidebar-wrapper"
             data-sidebar-open-value="${outerOpen}"
             data-sidebar-cookie-name-value="sidebar_state"
             data-state="${outerOpen ? "expanded" : "collapsed"}">
            <button data-slot="sidebar-trigger"
                    data-testid="outer-trigger"
                    aria-expanded="${outerOpen}"
                    data-action="click->sidebar#toggle">Outer</button>
            ${sidebarPart("sidebar", "outer-sidebar", "offcanvas", outerOpen)}
            <main>
                <div data-controller="${innerIdentifier}"
                     data-slot="sidebar-wrapper"
                     data-testid="inner-root"
                     data-${innerIdentifier}-open-value="${innerOpen}"
                     data-${innerIdentifier}-cookie-name-value="panel_state"
                     data-state="${innerOpen ? "expanded" : "collapsed"}">
                    <button data-slot="sidebar-trigger"
                            data-testid="inner-trigger"
                            aria-expanded="${innerOpen}"
                            data-action="click->${innerIdentifier}#toggle">Inner</button>
                    ${sidebarPart(innerIdentifier, "inner-sidebar", "icon", innerOpen)}
                </div>
            </main>
        </div>
    `;
}

// The nested provider alone at the top level, as a page without the shell would render it.
function standaloneInnerTemplate(open = true) {
    return `
        <div data-controller="sidebar"
             data-slot="sidebar-wrapper"
             data-testid="inner-root"
             data-sidebar-open-value="${open}"
             data-sidebar-cookie-name-value="panel_state"
             data-state="${open ? "expanded" : "collapsed"}">
            <button data-slot="sidebar-trigger"
                    data-testid="inner-trigger"
                    aria-expanded="${open}"
                    data-action="click->sidebar#toggle">Inner</button>
            ${sidebarPart("sidebar", "inner-sidebar", "icon", open)}
        </div>
    `;
}

function sidebarPart(identifier, testId, collapsible, open) {
    return `
        <div data-slot="sidebar"
             data-testid="${testId}"
             data-${identifier}-target="modal"
             data-sidebar-collapsible="${collapsible}"
             data-state="${open ? "expanded" : "collapsed"}"
             data-mobile-state="closed"
             data-motion="default"
             data-collapsible="${open ? "" : collapsible}">
            <div data-slot="sidebar-backdrop" data-${identifier}-target="backdrop" data-action="click->${identifier}#clickOutside"></div>
            <div data-slot="sidebar-container" data-${identifier}-target="dialog">
                <aside data-slot="sidebar-inner"></aside>
            </div>
        </div>
    `;
}

function byTestId(id, scope = document) {
    return scope.querySelector(`[data-testid='${id}']`);
}

function outerRoot(scope = document) {
    return scope.querySelector("[data-controller~='sidebar']");
}

function innerRoot(scope = document) {
    return byTestId("inner-root", scope);
}

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
