import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

import { FocusTrap } from "../../resources/js/controllers/_focus_trap.js";

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.KeyboardEvent = testWindow.KeyboardEvent;
    globalThis.HTMLElement = testWindow.HTMLElement;
});

afterEach(() => {
    testWindow.close();
});

function mountTrap(html) {
    document.body.innerHTML = html;
    const container = document.querySelector("[data-trap]");
    const trap = new FocusTrap(container);
    return { container, trap };
}

function dispatchTab({ shift = false } = {}) {
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab", shiftKey: shift }));
}

// --- activate (initial focus) ---

test.serial("does nothing before activate", () => {
    const { container } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
        <button id="outside">Outside</button>
    `);
    const outside = document.getElementById("outside");
    outside.focus();

    dispatchTab();

    expect(document.activeElement).toBe(outside);
});

test.serial("activate focuses the first focusable when nothing inside is focused", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
    `);

    trap.activate();

    expect(document.activeElement.id).toBe("a");
});

test.serial("activate leaves focus untouched when an element inside is already focused", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
    `);

    document.getElementById("b").focus();
    trap.activate();

    expect(document.activeElement.id).toBe("b");
});

test.serial("Tab from a middle element does not get intercepted", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
            <button id="c">C</button>
        </div>
    `);

    trap.activate(); // focuses "a"
    document.getElementById("b").focus();
    dispatchTab(); // b is middle, not last → handler does not preventDefault; happy-dom holds the active element

    expect(document.activeElement.id).toBe("b");
});

// --- cycling ---

test.serial("Tab on the last focusable cycles to the first", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
            <button id="c">C</button>
        </div>
    `);

    trap.activate();
    document.getElementById("c").focus();
    dispatchTab();

    expect(document.activeElement.id).toBe("a");
});

test.serial("Shift+Tab on the first focusable cycles to the last", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
            <button id="c">C</button>
        </div>
    `);

    trap.activate(); // focuses "a"
    dispatchTab({ shift: true });

    expect(document.activeElement.id).toBe("c");
});

// --- guards ---

test.serial("no focusable elements: activate and Tab are safe no-ops", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <p>Nothing focusable here</p>
        </div>
    `);

    expect(() => trap.activate()).not.toThrow();
    expect(() => dispatchTab()).not.toThrow();
});

test.serial("container.hidden: activate does not move focus and the listener is inert", () => {
    const { container, trap } = mountTrap(`
        <div data-trap hidden>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
        <button id="outside">Outside</button>
    `);
    const outside = document.getElementById("outside");
    outside.focus();

    trap.activate();

    expect(document.activeElement).toBe(outside);

    dispatchTab();

    expect(document.activeElement).toBe(outside);
    expect(container.hidden).toBe(true);
});

test.serial("skips disabled buttons and [type='hidden'] inputs when picking the first focusable", () => {
    // NOTE: the shared focusable selector inherited from modal/alert-dialog
    // does not exclude buttons that carry `tabindex="-1"` (the button clause
    // matches first via `button:not([disabled])`). Preserving 1:1 for now —
    // a separate PR can tighten the selector if needed.
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a" disabled>A disabled</button>
            <input id="b" type="hidden" />
            <button id="c">C focusable</button>
            <button id="d">D focusable</button>
        </div>
    `);

    trap.activate(); // first valid focusable is c (a and b skipped)

    expect(document.activeElement.id).toBe("c");

    document.getElementById("d").focus();
    dispatchTab(); // d is last → cycles to c

    expect(document.activeElement.id).toBe("c");
});

// --- deactivate ---

test.serial("deactivate detaches the listener", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
        <button id="outside">Outside</button>
    `);

    trap.activate();
    trap.deactivate();

    const outside = document.getElementById("outside");
    outside.focus();
    dispatchTab();

    expect(document.activeElement).toBe(outside);
});

// --- idempotence ---

test.serial("activate is idempotent — a second call does not move focus or duplicate listeners", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
            <button id="c">C</button>
        </div>
    `);

    trap.activate(); // focuses "a"
    document.getElementById("b").focus();
    trap.activate(); // no-op: already active, must not move focus back to "a"

    expect(document.activeElement.id).toBe("b");

    document.getElementById("c").focus();
    dispatchTab();
    expect(document.activeElement.id).toBe("a"); // cycles, didn't double-fire the listener
});

test.serial("deactivate is idempotent — calling twice is safe", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
        </div>
    `);

    trap.activate();
    trap.deactivate();
    expect(() => trap.deactivate()).not.toThrow();
});

test.serial("re-activating after deactivate focuses the first focusable again", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
    `);

    trap.activate(); // focuses "a"
    document.getElementById("b").focus();

    trap.deactivate();
    document.body.focus(); // simulate focus returning outside after modal close
    trap.activate();

    expect(document.activeElement.id).toBe("a");
});
