import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import CopyToClipboardController from "../../resources/js/controllers/copy_to_clipboard_controller.js";

let mounted;
let writeTextMock;
let resolveClipboard;

beforeEach(() => {
    let resolve;
    writeTextMock = mock(() => {
        return new Promise((r) => { resolve = r; });
    });
    resolveClipboard = () => resolve();
    globalThis.navigator = { clipboard: { writeText: writeTextMock } };
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- copy action ---

test.serial("copy writes source target value to clipboard", async () => {
    await mount(`
        <input data-copy-to-clipboard-target="source" value="hello world" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>
    `);

    document.querySelector("button").click();
    await wait(0);

    expect(writeTextMock).toHaveBeenCalledWith("hello world");
});

test.serial("copy writes source target innerHTML when value is empty", async () => {
    await mount(`
        <span data-copy-to-clipboard-target="source">inner content</span>
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>
    `);

    document.querySelector("button").click();
    await wait(0);

    expect(writeTextMock).toHaveBeenCalledWith("inner content");
});

test.serial("copy prevents default event", async () => {
    await mount(`
        <input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>
    `);

    const button = document.querySelector("button");
    const event = new MouseEvent("click", { cancelable: true, bubbles: true });
    button.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
});

// --- copied feedback ---

test.serial("copied updates button innerHTML with success content", async () => {
    await mount(
        `<input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>`,
        { "data-copy-to-clipboard-success-content-value": "Copied!" },
    );

    const button = document.querySelector("button");
    const scheduler = installFakeFeedbackScheduler();
    button.click();
    resolveClipboard();
    await wait(0);

    expect(button.innerHTML).toBe("Copied!");
});

test.serial("copied resets button innerHTML after successDuration", async () => {
    await mount(
        `<input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>`,
        {
            "data-copy-to-clipboard-success-content-value": "Copied!",
            "data-copy-to-clipboard-success-duration-value": "50",
        },
    );

    const button = document.querySelector("button");
    const scheduler = installFakeFeedbackScheduler();
    button.click();
    resolveClipboard();
    await wait(0);

    expect(button.innerHTML).toBe("Copied!");

    expect(scheduler.pending()[0].duration).toBe(50);
    scheduler.runNext();

    expect(button.innerHTML).toBe("Copy");
});

test.serial("multiple rapid copies cancel the previous timeout", async () => {
    await mount(
        `<input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>`,
        {
            "data-copy-to-clipboard-success-content-value": "Copied!",
            "data-copy-to-clipboard-success-duration-value": "200",
        },
    );

    const button = document.querySelector("button");
    const scheduler = installFakeFeedbackScheduler();

    button.click();
    resolveClipboard();
    await wait(0);
    expect(button.innerHTML).toBe("Copied!");
    const firstTimer = scheduler.pending()[0];

    // Second copy before timeout fires
    let resolve2;
    writeTextMock = mock(() => new Promise((r) => { resolve2 = r; }));
    globalThis.navigator = { clipboard: { writeText: writeTextMock } };

    button.click();
    resolve2();
    await wait(0);

    expect(firstTimer.cancelled).toBe(true);
    expect(button.innerHTML).toBe("Copied!");

    scheduler.runNext();
    expect(button.innerHTML).toBe("Copy");
});

test.serial("successDuration defaults to 2000ms", async () => {
    await mount(
        `<input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy">Copy</button>`,
        { "data-copy-to-clipboard-success-content-value": "Copied!" },
    );

    const button = document.querySelector("button");
    const scheduler = installFakeFeedbackScheduler();
    button.click();
    resolveClipboard();
    await wait(0);

    expect(button.innerHTML).toBe("Copied!");
});

// --- no button target ---

test.serial("is a no-op when no button target exists", async () => {
    await mount(`
        <input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-action="copy-to-clipboard#copy">Copy</button>
    `);

    const button = document.querySelector("button");
    expect(() => button.click()).not.toThrow();
});

// --- connect stores original content ---

test.serial("connect stores the original button innerHTML", async () => {
    await mount(
        `<input data-copy-to-clipboard-target="source" value="text" />
        <button type="button" data-copy-to-clipboard-target="button"
                data-action="copy-to-clipboard#copy"><strong>Copy text</strong></button>`,
        { "data-copy-to-clipboard-success-content-value": "Copied!" },
    );

    const button = document.querySelector("button");
    const scheduler = installFakeFeedbackScheduler();
    button.click();
    resolveClipboard();
    await wait(0);

    expect(button.innerHTML).toBe("Copied!");

    expect(scheduler.pending()[0].duration).toBe(2000);
    scheduler.runNext();

    expect(button.innerHTML).toBe("<strong>Copy text</strong>");
});

async function mount(html, extraAttrs = {}) {
    const attrs = Object.entries(extraAttrs)
        .map(([k, v]) => `${k}="${v}"`)
        .join(" ");
    mounted = await mountController(
        "copy-to-clipboard",
        CopyToClipboardController,
        `<div data-controller="copy-to-clipboard" ${attrs}>${html}</div>`,
    );
}

function installFakeFeedbackScheduler() {
    const timers = [];

    mounted.controller.setFeedbackTimer = (callback, duration) => {
        const timer = { callback, duration, cancelled: false };
        timers.push(timer);

        return timer;
    };

    mounted.controller.clearFeedbackTimer = (timer) => {
        if (timer) timer.cancelled = true;
    };

    return {
        pending() {
            return timers.filter((timer) => !timer.cancelled);
        },
        runNext() {
            const timer = this.pending()[0];

            if (!timer) {
                throw new Error("Expected a pending copy feedback timer.");
            }

            timer.cancelled = true;
            timer.callback();
        },
    };
}
