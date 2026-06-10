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

// --- activate / priming ---

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

test.serial("first Tab after activate focuses the first focusable (priming)", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
    `);

    trap.activate();
    dispatchTab();

    expect(document.activeElement.id).toBe("a");
});

test.serial("subsequent Tabs do not re-prime", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
            <button id="c">C</button>
        </div>
    `);

    trap.activate();
    dispatchTab(); // primes → focuses "a"
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
    // Consume priming first.
    dispatchTab();
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

    trap.activate();
    dispatchTab(); // primes
    // active is "a" now
    dispatchTab({ shift: true });

    expect(document.activeElement.id).toBe("c");
});

// --- guards ---

test.serial("no focusable elements: handler is a safe no-op", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <p>Nothing focusable here</p>
        </div>
    `);

    trap.activate();

    expect(() => dispatchTab()).not.toThrow();
});

test.serial("container.hidden makes the listener inert", () => {
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
    dispatchTab();

    expect(document.activeElement).toBe(outside);
    expect(container.hidden).toBe(true);
});

test.serial("skips disabled buttons and [type='hidden'] inputs", () => {
    // NOTE: the shared focusable selector inherited from modal/confirm-dialog
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

    trap.activate();
    dispatchTab(); // primes → first focusable is c (a and b are skipped)

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

test.serial("activate is idempotent — a second call does not duplicate listeners", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
            <button id="c">C</button>
        </div>
    `);

    trap.activate();
    trap.activate();
    dispatchTab(); // primes once

    expect(document.activeElement.id).toBe("a");

    document.getElementById("c").focus();
    dispatchTab();
    expect(document.activeElement.id).toBe("a"); // still cycles, didn't double-fire
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

test.serial("re-activating after deactivate primes again on next Tab", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
    `);

    trap.activate();
    dispatchTab(); // primes → "a"
    document.getElementById("b").focus();

    trap.deactivate();
    trap.activate();
    dispatchTab(); // primes again

    expect(document.activeElement.id).toBe("a");
});
