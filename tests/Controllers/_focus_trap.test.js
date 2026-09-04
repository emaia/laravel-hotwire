import { afterEach, beforeEach, expect, mock, test } from "bun:test";
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

function mountTrap(html, options = {}) {
    document.body.innerHTML = html;
    const container = document.querySelector("[data-trap]");
    const trap = new FocusTrap(container, options);
    return { container, trap };
}

function dispatchTab({ shift = false } = {}) {
    const event = new KeyboardEvent("keydown", { key: "Tab", shiftKey: shift, cancelable: true });
    document.dispatchEvent(event);

    return event;
}

test.serial("does not trap Tab during composition", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <input id="first" type="text">
            <button id="last">Last</button>
        </div>
    `);
    const last = document.getElementById("last");
    trap.activate();
    last.focus();

    const event = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
    Object.defineProperty(event, "isComposing", { value: true });
    document.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(document.activeElement).toBe(last);
});

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
    const first = document.getElementById("a");
    const focus = first.focus.bind(first);
    const focusSpy = mock((options) => focus(options));
    first.focus = focusSpy;

    trap.activate();

    expect(document.activeElement.id).toBe("a");
    expect(focusSpy).toHaveBeenCalledWith({ focusVisible: true, preventScroll: true });
});

test.serial("activate applies the configured strategy when an element inside is already focused", () => {
    const { trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <button id="a">A</button>
            <button id="b">B</button>
        </div>
    `,
        { initialFocus: "dialog" },
    );

    document.getElementById("b").focus();
    trap.activate();

    expect(document.activeElement).toBe(document.querySelector("[data-trap]"));
});

test.serial("activate ignores focusable elements inside hidden or inert subtrees", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <div hidden><button id="hidden">Hidden</button></div>
            <div inert><button id="inert">Inert</button></div>
            <button id="visible">Visible</button>
        </div>
    `);

    trap.activate();

    expect(document.activeElement.id).toBe("visible");
});

test.serial("auto prefers an eligible autofocus element over its fallback", () => {
    const { container, trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <div hidden><input id="hidden" autofocus /></div>
            <div inert><input id="inert" autofocus /></div>
            <input id="ordinary" />
            <button id="explicit" autofocus>Explicit</button>
        </div>
    `,
        {
            initialFocus: "auto",
            fallback: () => container,
        },
    );

    trap.activate();

    expect(document.activeElement.id).toBe("explicit");
});

test.serial("auto focuses its fallback instead of the first ordinary control", () => {
    const { container, trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <input id="first" />
        </div>
    `,
        {
            initialFocus: "auto",
            fallback: () => container,
        },
    );

    trap.activate();

    expect(document.activeElement).toBe(container);
});

test.serial("dialog ignores autofocus and focuses the semantic dialog surface", () => {
    const { container, trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <input id="explicit" autofocus />
        </div>
    `,
        { initialFocus: "dialog" },
    );

    trap.activate();

    expect(document.activeElement).toBe(container);
});

test.serial("first-focusable excludes disabled and negative-tabindex controls", () => {
    const { trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <button id="negative" tabindex="-1">Negative</button>
            <button id="disabled" disabled tabindex="0">Disabled</button>
            <fieldset disabled><input id="disabled-field" tabindex="0" /></fieldset>
            <button id="first">First</button>
        </div>
    `,
        { initialFocus: "first-focusable" },
    );

    trap.activate();

    expect(document.activeElement.id).toBe("first");
});

test.serial("first-focusable includes a contenteditable editing host", () => {
    const { trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <div id="editor" contenteditable="true">Editable</div>
            <button id="later">Later</button>
        </div>
    `,
        { initialFocus: "first-focusable" },
    );

    trap.activate();

    expect(document.activeElement.id).toBe("editor");
});

test.serial("none activates Tab containment without moving initial focus", () => {
    const { trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <button id="first">First</button>
            <button id="last">Last</button>
        </div>
        <button id="outside">Outside</button>
    `,
        { initialFocus: "none" },
    );
    const outside = document.getElementById("outside");
    outside.focus();

    trap.activate();

    expect(document.activeElement).toBe(outside);

    const tab = dispatchTab();

    expect(tab.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("first");
});

test.serial("activation can resume containment without reapplying initial focus", () => {
    const { trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <button id="first">First</button>
        </div>
        <button id="outside">Outside</button>
    `,
        { initialFocus: "first-focusable" },
    );
    const outside = document.getElementById("outside");

    trap.activate();
    trap.deactivate();
    outside.focus();
    trap.activate({ moveFocus: false });

    expect(document.activeElement).toBe(outside);
    expect(dispatchTab().defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("first");
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

test.serial("cycling focus allows the browser to scroll the destination into view", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="first">First</button>
            <button id="last">Last</button>
        </div>
    `);
    const first = document.getElementById("first");
    const last = document.getElementById("last");
    const focus = last.focus.bind(last);
    const focusSpy = mock((options) => focus(options));
    last.focus = focusSpy;

    trap.activate();
    first.focus();
    dispatchTab({ shift: true });

    expect(focusSpy).toHaveBeenCalledWith({ focusVisible: true });
});

test.serial("cycling focus makes only one bare fallback attempt", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="first">First</button>
            <button id="last">Last</button>
        </div>
    `);
    const first = document.getElementById("first");

    trap.activate();
    first.focus = mock(() => {
        throw new Error("focus failed");
    });
    document.getElementById("last").focus();
    dispatchTab();

    expect(first.focus).toHaveBeenCalledTimes(2);
});

test.serial("cycling ignores focusable elements inside hidden or inert subtrees", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <div hidden><button id="hidden-first">Hidden first</button></div>
            <button id="first">First</button>
            <button id="last">Last</button>
            <div inert><button id="inert-last">Inert last</button></div>
        </div>
    `);

    trap.activate();
    const backward = dispatchTab({ shift: true });

    expect(backward.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("last");

    const forward = dispatchTab();

    expect(forward.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("first");
});

test.serial("Tab from the dialog surface enters the trap in either direction", () => {
    const { container, trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <button id="first">First</button>
            <button id="last">Last</button>
        </div>
    `,
        { initialFocus: "dialog" },
    );

    trap.activate();
    expect(document.activeElement).toBe(container);

    dispatchTab();
    expect(document.activeElement.id).toBe("first");

    container.focus();
    dispatchTab({ shift: true });
    expect(document.activeElement.id).toBe("last");
});

test.serial("Tab from a programmatically focusable descendant follows native order", () => {
    const { trap } = mountTrap(`
        <div data-trap role="dialog" tabindex="-1">
            <button id="first">First</button>
            <div id="programmatic" tabindex="-1">Programmatic</div>
            <button id="next">Next</button>
        </div>
    `);
    const programmatic = document.getElementById("programmatic");

    trap.activate();
    programmatic.focus();
    const event = dispatchTab();

    expect(event.defaultPrevented).toBe(false);
    expect(document.activeElement).toBe(programmatic);
});

test.serial("Tab from a programmatically focusable descendant wraps at either boundary", () => {
    const { trap } = mountTrap(`
        <div data-trap role="dialog" tabindex="-1">
            <div id="before" tabindex="-1">Before</div>
            <button id="first">First</button>
            <button id="last">Last</button>
            <div id="after" tabindex="-1">After</div>
        </div>
    `);
    const before = document.getElementById("before");
    const after = document.getElementById("after");

    trap.activate();
    before.focus();
    const backward = dispatchTab({ shift: true });

    expect(backward.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("last");

    after.focus();
    const forward = dispatchTab();

    expect(forward.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("first");
});

test.serial("cycling keeps aria-disabled controls in the tab sequence", () => {
    const { trap } = mountTrap(`
        <div data-trap>
            <button id="first">First</button>
            <button id="disabled" aria-disabled="true">Unavailable</button>
            <button id="last">Last</button>
        </div>
    `);
    const disabled = document.getElementById("disabled");

    trap.activate();
    disabled.focus();
    const event = dispatchTab();

    expect(event.defaultPrevented).toBe(false);
    expect(document.activeElement).toBe(disabled);
});

test.serial("Tab stays contained when the dialog has no tabbable descendants", () => {
    const { container, trap } = mountTrap(
        `
        <div data-trap role="dialog" tabindex="-1">
            <p>Nothing tabbable</p>
        </div>
    `,
        { initialFocus: "dialog" },
    );

    trap.activate();
    const event = dispatchTab();

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(container);
});

test.serial("Tab from outside an active trap moves into the first focusable", () => {
    const { container, trap } = mountTrap(`
        <div data-trap></div>
        <button id="outside">Outside</button>
    `);
    const outside = document.getElementById("outside");
    outside.focus();

    trap.activate();
    expect(document.activeElement).toBe(outside);

    container.innerHTML = `
        <input id="first" type="text" />
        <button id="last">Last</button>
    `;

    const event = dispatchTab();

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("first");
});

test.serial("Shift+Tab from outside an active trap moves into the last focusable", () => {
    const { container, trap } = mountTrap(`
        <div data-trap></div>
        <button id="outside">Outside</button>
    `);
    const outside = document.getElementById("outside");
    outside.focus();

    trap.activate();
    expect(document.activeElement).toBe(outside);

    container.innerHTML = `
        <input id="first" type="text" />
        <button id="last">Last</button>
    `;

    const event = dispatchTab({ shift: true });

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("last");
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
