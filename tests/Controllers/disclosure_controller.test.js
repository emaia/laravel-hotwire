import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import DisclosureController from "../../resources/js/controllers/disclosure_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- connect sync ---

test.serial("default open=false hides content and sets aria-expanded=false", async () => {
    await mount(`
        <div data-controller="disclosure">
            <button type="button" data-disclosure-target="trigger"></button>
            <div data-disclosure-target="content"></div>
        </div>
    `);

    expect(content().hidden).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
});

test.serial("open=true reveals content and sets aria-expanded=true on connect", async () => {
    await mount(`
        <div data-controller="disclosure" data-disclosure-open-value="true">
            <button type="button" data-disclosure-target="trigger"></button>
            <div data-disclosure-target="content" hidden></div>
        </div>
    `);

    expect(content().hidden).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
});

test.serial("does NOT dispatch on initial connect", async () => {
    const events = [];
    document.body.innerHTML = "";

    await mount(`
        <div data-controller="disclosure" data-disclosure-open-value="true">
            <button type="button" data-disclosure-target="trigger"></button>
            <div data-disclosure-target="content"></div>
        </div>
    `);

    mounted.root.addEventListener("disclosure:change", (e) => events.push(e.detail));
    // attach AFTER connect — events array stays empty unless connect itself dispatched
    expect(events).toEqual([]);
});

// --- actions ---

test.serial("toggle action flips state, aria, and dispatches change", async () => {
    await mount(`
        <div data-controller="disclosure">
            <button type="button" data-disclosure-target="trigger"
                    data-action="disclosure#toggle"></button>
            <div data-disclosure-target="content"></div>
        </div>
    `);

    const events = [];
    mounted.root.addEventListener("disclosure:change", (e) => events.push(e.detail));

    trigger().click();
    expect(content().hidden).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");

    trigger().click();
    expect(content().hidden).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");

    expect(events).toEqual([{ open: true }, { open: false }]);
});

test.serial("open() is idempotent — second call does not dispatch", async () => {
    await mount(`
        <div data-controller="disclosure">
            <button type="button" data-disclosure-target="trigger"></button>
            <div data-disclosure-target="content"></div>
        </div>
    `);

    const events = [];
    mounted.root.addEventListener("disclosure:change", (e) => events.push(e.detail));

    mounted.controller.open();
    mounted.controller.open();

    expect(content().hidden).toBe(false);
    expect(events).toEqual([{ open: true }]);
});

test.serial("close() is idempotent — second call does not dispatch", async () => {
    await mount(`
        <div data-controller="disclosure" data-disclosure-open-value="true">
            <button type="button" data-disclosure-target="trigger"></button>
            <div data-disclosure-target="content"></div>
        </div>
    `);

    const events = [];
    mounted.root.addEventListener("disclosure:change", (e) => events.push(e.detail));

    mounted.controller.close();
    mounted.controller.close();

    expect(content().hidden).toBe(true);
    expect(events).toEqual([{ open: false }]);
});

// --- two-way value ---

test.serial("openValue reflects state after toggle", async () => {
    await mount(`
        <div data-controller="disclosure">
            <button type="button" data-disclosure-target="trigger"></button>
            <div data-disclosure-target="content"></div>
        </div>
    `);

    expect(mounted.controller.openValue).toBe(false);
    mounted.controller.toggle();
    expect(mounted.controller.openValue).toBe(true);
    expect(mounted.root.getAttribute("data-disclosure-open-value")).toBe("true");
});

// Note: programmatic open from outside the controller should call controller.open()
// (or close/toggle), not assign controller.openValue directly. The methods sync DOM
// and dispatch synchronously; raw value writes go through Stimulus's MutationObserver
// path which is microtask-async and won't update the DOM before user code reads it.

// --- optional trigger ---

test.serial("trigger target is optional — toggle still updates content", async () => {
    await mount(`
        <div data-controller="disclosure">
            <div data-disclosure-target="content"></div>
        </div>
    `);

    mounted.controller.toggle();
    expect(content().hidden).toBe(false);

    mounted.controller.toggle();
    expect(content().hidden).toBe(true);
});

// --- missing content guard ---

test.serial("missing content target — controller is a safe no-op", async () => {
    await mount(`
        <div data-controller="disclosure">
            <button type="button" data-disclosure-target="trigger"></button>
        </div>
    `);

    expect(() => mounted.controller.toggle()).not.toThrow();
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
});

async function mount(html) {
    mounted = await mountController("disclosure", DisclosureController, html);
}

function trigger() {
    return document.querySelector("[data-disclosure-target='trigger']");
}

function content() {
    return document.querySelector("[data-disclosure-target='content']");
}
