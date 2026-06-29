import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

const HTML = `
    <div data-controller="drawer"
         data-drawer-hidden-class="opacity-0 pointer-events-none"
         data-drawer-visible-class="opacity-100 pointer-events-auto"
         data-drawer-backdrop-hidden-class="bd-hidden"
         data-drawer-backdrop-visible-class="bd-visible"
         data-drawer-panel-hidden-class="-translate-x-full"
         data-drawer-panel-visible-class="translate-x-0"
         data-drawer-lock-scroll-class="overflow-hidden"
         data-drawer-open-duration-value="1"
         data-drawer-close-duration-value="1">
        <button id="trigger" data-action="drawer#open">Open</button>

        <div data-drawer-target="container" role="dialog" aria-modal="true" hidden
             class="opacity-0 pointer-events-none">
            <div data-drawer-target="backdrop"
                 data-action="click->drawer#clickOutside"
                 class="bd-hidden"></div>
            <div data-drawer-target="panel"
                 class="-translate-x-full">
                <button id="close" data-action="drawer#close">Close</button>
                <a href="#anchor" id="link">Anchor</a>
            </div>
        </div>
    </div>
`;

function dispatchClick(element, init = {}) {
    return element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, ...init }));
}

async function mount() {
    mounted = await mountController("drawer", DrawerController, HTML);
}

// --- starts closed ---

test.serial("starts closed with container hidden", async () => {
    await mount();
    const container = document.querySelector('[data-drawer-target="container"]');

    expect(container.hidden).toBe(true);
    expect(mounted.controller.isOpen).toBe(false);
});

// --- open() ---

test.serial("open() reveals the container after rAF and applies visible classes", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const container = document.querySelector('[data-drawer-target="container"]');
    const panel = document.querySelector('[data-drawer-target="panel"]');
    const backdrop = document.querySelector('[data-drawer-target="backdrop"]');

    dispatchClick(trigger);

    expect(container.hidden).toBe(false);
    expect(mounted.controller.isOpen).toBe(true);

    await wait(10);

    expect(container.classList.contains("opacity-100")).toBe(true);
    expect(container.classList.contains("pointer-events-auto")).toBe(true);
    expect(backdrop.classList.contains("bd-visible")).toBe(true);
    expect(panel.classList.contains("translate-x-0")).toBe(true);
});

test.serial("open() locks body scroll when lock-scroll is true (default)", async () => {
    await mount();

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

test.serial("open() does not lock scroll when lock-scroll is false", async () => {
    mounted = await mountController(
        "drawer",
        DrawerController,
        HTML.replace('data-drawer-open-duration-value="1"', 'data-drawer-open-duration-value="1" data-drawer-lock-scroll-value="false"'),
    );

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

test.serial("open() dispatches drawer:opened after the open duration", async () => {
    await mount();
    let openedDetail = null;
    mounted.root.addEventListener("drawer:opened", (e) => {
        openedDetail = e;
    });

    dispatchClick(document.getElementById("trigger"));
    await wait(20);

    expect(openedDetail).not.toBeNull();
});

test.serial("open() is idempotent — second call while open is a no-op", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    dispatchClick(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    mounted.controller.isAnimating = false;
    dispatchClick(trigger);

    expect(mounted.controller.isAnimating).toBe(false);
});

test.serial("open() ignores ctrl/meta/shift clicks and middle button", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    dispatchClick(trigger, { ctrlKey: true });
    dispatchClick(trigger, { metaKey: true });
    dispatchClick(trigger, { shiftKey: true });
    dispatchClick(trigger, { button: 1 });

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("open() captures the trigger element for focus return", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    dispatchClick(trigger);

    expect(mounted.controller.triggerElement).toBe(trigger);
});

// --- close() ---

test.serial("close() reverses classes and hides container after closeDuration", async () => {
    await mount();
    const container = document.querySelector('[data-drawer-target="container"]');
    const panel = document.querySelector('[data-drawer-target="panel"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    dispatchClick(document.getElementById("close"));

    expect(container.classList.contains("opacity-0")).toBe(true);
    expect(panel.classList.contains("-translate-x-full")).toBe(true);
    expect(mounted.controller.isOpen).toBe(false);

    await wait(20);
    expect(container.hidden).toBe(true);
});

test.serial("close() unlocks body scroll", async () => {
    await mount();

    dispatchClick(document.getElementById("trigger"));
    await wait(10);
    dispatchClick(document.getElementById("close"));

    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

test.serial("close() returns focus to the trigger element", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    dispatchClick(trigger);
    await wait(10);
    dispatchClick(document.getElementById("close"));

    expect(document.activeElement).toBe(trigger);
});

test.serial("close() dispatches drawer:closed after the close duration", async () => {
    await mount();
    let closedDispatched = false;
    mounted.root.addEventListener("drawer:closed", () => {
        closedDispatched = true;
    });

    dispatchClick(document.getElementById("trigger"));
    await wait(10);
    dispatchClick(document.getElementById("close"));
    await wait(20);

    expect(closedDispatched).toBe(true);
});

test.serial("close() is a no-op when already closed", async () => {
    await mount();

    mounted.controller.close();
    expect(mounted.controller.isOpen).toBe(false);
});

// --- toggle ---

test.serial("toggle opens when closed and closes when open", async () => {
    await mount();

    mounted.controller.toggle();
    expect(mounted.controller.isOpen).toBe(true);

    await wait(10);
    mounted.controller.toggle();
    expect(mounted.controller.isOpen).toBe(false);
});

// --- click outside (backdrop) ---

test.serial("backdrop click closes when closeOnClickOutside is true", async () => {
    await mount();
    const backdrop = document.querySelector('[data-drawer-target="backdrop"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    dispatchClick(backdrop);

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("backdrop click is a no-op when closeOnClickOutside is false", async () => {
    mounted = await mountController(
        "drawer",
        DrawerController,
        HTML.replace('data-drawer-open-duration-value="1"', 'data-drawer-open-duration-value="1" data-drawer-close-on-click-outside-value="false"'),
    );
    const backdrop = document.querySelector('[data-drawer-target="backdrop"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    dispatchClick(backdrop);

    expect(mounted.controller.isOpen).toBe(true);
});

test.serial("clicks inside the panel do not close", async () => {
    await mount();
    const link = document.getElementById("link");

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    dispatchClick(link);

    expect(mounted.controller.isOpen).toBe(true);
});

// --- Escape ---

test.serial("Escape closes when open and closeOnEscape is true (default)", async () => {
    await mount();

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("Escape is a no-op when closed", async () => {
    await mount();

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("Escape is a no-op when closeOnEscape is false", async () => {
    mounted = await mountController(
        "drawer",
        DrawerController,
        HTML.replace('data-drawer-open-duration-value="1"', 'data-drawer-open-duration-value="1" data-drawer-close-on-escape-value="false"'),
    );

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(true);
});

test.serial("Escape stops propagation while open so peer document listeners don't react", async () => {
    await mount();
    let bubbleListenerSawEscape = false;
    const spy = (event) => {
        if (event.key === "Escape") bubbleListenerSawEscape = true;
    };
    document.addEventListener("keydown", spy);

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(false);
    expect(bubbleListenerSawEscape).toBe(false);

    document.removeEventListener("keydown", spy);
});

// --- direction-agnostic transform handling ---

test.serial("handles right-position transform classes (translate-x-full ↔ translate-x-0)", async () => {
    mounted = await mountController(
        "drawer",
        DrawerController,
        HTML
            .replace('data-drawer-panel-hidden-class="-translate-x-full"', 'data-drawer-panel-hidden-class="translate-x-full"')
            .replace('class="-translate-x-full"', 'class="translate-x-full"'),
    );
    const panel = document.querySelector('[data-drawer-target="panel"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    expect(panel.classList.contains("translate-x-0")).toBe(true);
    expect(panel.classList.contains("translate-x-full")).toBe(false);
});

test.serial("handles top-position transform classes (-translate-y-full ↔ translate-y-0)", async () => {
    mounted = await mountController(
        "drawer",
        DrawerController,
        HTML
            .replace('data-drawer-panel-hidden-class="-translate-x-full"', 'data-drawer-panel-hidden-class="-translate-y-full"')
            .replace('data-drawer-panel-visible-class="translate-x-0"', 'data-drawer-panel-visible-class="translate-y-0"')
            .replace('class="-translate-x-full"', 'class="-translate-y-full"'),
    );
    const panel = document.querySelector('[data-drawer-target="panel"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    expect(panel.classList.contains("translate-y-0")).toBe(true);
    expect(panel.classList.contains("-translate-y-full")).toBe(false);
});

test.serial("handles bottom-position transform classes (translate-y-full ↔ translate-y-0)", async () => {
    mounted = await mountController(
        "drawer",
        DrawerController,
        HTML
            .replace('data-drawer-panel-hidden-class="-translate-x-full"', 'data-drawer-panel-hidden-class="translate-y-full"')
            .replace('data-drawer-panel-visible-class="translate-x-0"', 'data-drawer-panel-visible-class="translate-y-0"')
            .replace('class="-translate-x-full"', 'class="translate-y-full"'),
    );
    const panel = document.querySelector('[data-drawer-target="panel"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    expect(panel.classList.contains("translate-y-0")).toBe(true);
    expect(panel.classList.contains("translate-y-full")).toBe(false);
});

// --- disconnect cleanup ---

test.serial("disconnect cleans up the Escape listener", async () => {
    await mount();

    mounted.controller.disconnect();

    expect(() => {
        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
    }).not.toThrow();
});

test.serial("disconnect closes an open drawer without leaving body locked", async () => {
    await mount();

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    mounted.controller.disconnect();

    expect(mounted.controller.isOpen).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

// --- turbo:before-cache ---

test.serial("closeForCache hard-closes (no transition delay) for a clean Turbo snapshot", async () => {
    await mount();
    const container = document.querySelector('[data-drawer-target="container"]');

    dispatchClick(document.getElementById("trigger"));
    await wait(10);

    mounted.controller.closeForCache();

    expect(container.hidden).toBe(true);
    expect(mounted.controller.isOpen).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});
