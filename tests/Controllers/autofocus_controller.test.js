import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import AutofocusController from "../../resources/js/controllers/autofocus_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- autofocus-attribute strategy (default) ---

test.serial("focuses the first [autofocus] element on connect", async () => {
    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" />
            <input type="text" name="slug" autofocus />
            <input type="text" name="body" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

test.serial("does nothing when no [autofocus] element exists", async () => {
    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" />
        </form>
    `);

    expect(document.activeElement).toBe(document.body);
});

// Note: the "guard against stealing focus on connect" path is covered indirectly
// by the turbo:frame-load test below — both call the same internal shouldFocus()
// check. Testing connect-time with pre-focused DOM is awkward because
// mountController resets document.body.innerHTML before registering the controller.

// --- first-focusable strategy ---

test.serial("first-focusable focuses the first input/select/textarea/button", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
            <button type="button">Click</button>
            <input type="text" name="title" />
        </form>
    `);

    expect(document.activeElement.tagName).toBe("BUTTON");
});

test.serial("first-focusable skips disabled fields", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
            <input type="text" name="title" disabled />
            <input type="text" name="slug" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

test.serial("first-focusable skips [type=hidden] inputs", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
            <input type="hidden" name="csrf" value="xyz" />
            <input type="text" name="slug" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

test.serial("first-focusable skips elements with [tabindex=-1]", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
            <button type="button" tabindex="-1">Skip</button>
            <input type="text" name="slug" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

test.serial("first-focusable skips elements inside [hidden] ancestors", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
            <div hidden>
                <input type="text" name="hidden_field" />
            </div>
            <input type="text" name="slug" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

test.serial("first-focusable skips elements inside [aria-hidden=true] ancestors", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="first-focusable">
            <div aria-hidden="true">
                <input type="text" name="hidden_field" />
            </div>
            <input type="text" name="slug" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

// --- target strategy ---

test.serial("target strategy focuses the field target", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="target">
            <input type="text" name="title" />
            <input type="text" name="slug" data-autofocus-target="field" />
        </form>
    `);

    expect(document.activeElement.name).toBe("slug");
});

test.serial("target strategy does nothing when target is missing", async () => {
    await mount(`
        <form data-controller="autofocus" data-autofocus-strategy-value="target">
            <input type="text" name="title" />
        </form>
    `);

    expect(document.activeElement).toBe(document.body);
});

// --- turbo:frame-load reapplication ---

test.serial("re-applies focus on turbo:frame-load when scope lost focus", async () => {
    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" autofocus />
        </form>
    `);

    document.body.focus();
    expect(document.activeElement).toBe(document.body);

    document.dispatchEvent(new Event("turbo:frame-load", { bubbles: true }));
    await wait(0);

    expect(document.activeElement.name).toBe("title");
});

test.serial("does not re-steal focus on turbo:frame-load when something in scope is focused", async () => {
    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" />
            <input type="text" name="slug" autofocus />
        </form>
    `);

    document.querySelector("[name='title']").focus();
    expect(document.activeElement.name).toBe("title");

    document.dispatchEvent(new Event("turbo:frame-load", { bubbles: true }));
    await wait(0);

    expect(document.activeElement.name).toBe("title");
});

test.serial("does NOT focus on turbo:render", async () => {
    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" autofocus />
        </form>
    `);

    document.body.focus();
    expect(document.activeElement).toBe(document.body);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(document.activeElement).toBe(document.body);
});

// --- focus options ---

test.serial("focuses with preventScroll=true by default", async () => {
    const calls = [];
    spyOnFocusOnce((options) => calls.push(options));

    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" autofocus />
        </form>
    `);

    expect(calls).toEqual([{ preventScroll: true }]);
});

test.serial("scroll-into-view value flips preventScroll to false", async () => {
    const calls = [];
    spyOnFocusOnce((options) => calls.push(options));

    await mount(`
        <form data-controller="autofocus" data-autofocus-scroll-into-view-value="true">
            <input type="text" name="title" autofocus />
        </form>
    `);

    expect(calls).toEqual([{ preventScroll: false }]);
});

// --- cleanup ---

test.serial("disconnect removes the turbo:frame-load listener", async () => {
    await mount(`
        <form data-controller="autofocus">
            <input type="text" name="title" autofocus />
        </form>
    `);

    await mounted.cleanup();
    mounted = null;

    document.body.innerHTML = `
        <form>
            <input type="text" name="title" autofocus />
        </form>
    `;
    document.body.focus();

    document.dispatchEvent(new Event("turbo:frame-load", { bubbles: true }));
    await wait(0);

    expect(document.activeElement).toBe(document.body);
});

async function mount(html) {
    mounted = await mountController("autofocus", AutofocusController, html);
}

function spyOnFocusOnce(capture) {
    const original = HTMLElement.prototype.focus;
    HTMLElement.prototype.focus = function (options) {
        capture(options);
        HTMLElement.prototype.focus = original;
        return original.call(this, options);
    };
}
