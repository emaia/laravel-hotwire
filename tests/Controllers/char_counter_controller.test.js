import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import CharCounterController from "../../resources/js/controllers/char_counter_controller.ts";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("initializes counter from pre-filled textarea value on connect", async () => {
    await mountWrapper(`
        <textarea data-char-counter-target="input" maxlength="500">hello world</textarea>
        <span data-char-counter-target="counter">0</span>
    `);

    const counter = document.querySelector('[data-char-counter-target="counter"]');
    expect(counter.innerHTML).toBe("11");
});

test.serial("updates counter as the user types", async () => {
    await mountWrapper(`
        <textarea data-char-counter-target="input" maxlength="500"></textarea>
        <span data-char-counter-target="counter">0</span>
    `);

    const textarea = document.querySelector("textarea");
    textarea.value = "abc";
    dispatchEvent(textarea, "input");
    await wait(0);

    const counter = document.querySelector('[data-char-counter-target="counter"]');
    expect(counter.innerHTML).toBe("3");
});

test.serial("re-syncs counter after turbo:render (morph scenario)", async () => {
    await mountWrapper(`
        <textarea data-char-counter-target="input" maxlength="500">hello</textarea>
        <span data-char-counter-target="counter">0</span>
    `);

    const textarea = document.querySelector("textarea");
    const counter = document.querySelector('[data-char-counter-target="counter"]');

    expect(counter.innerHTML).toBe("5");

    // Simulate Turbo morph: server-rendered HTML lands on the page,
    // textarea content changes, counter innerHTML is reset to the server default.
    textarea.value = "this is morphed content";
    counter.innerHTML = "0";

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(counter.innerHTML).toBe("23");
});

test.serial("countdown mode subtracts the value length from maxlength", async () => {
    await mountWrapper(
        `
        <textarea data-char-counter-target="input" maxlength="10">hello</textarea>
        <span data-char-counter-target="counter">0</span>
    `,
        { "data-char-counter-countdown-value": "true" },
    );

    const counter = document.querySelector('[data-char-counter-target="counter"]');
    expect(counter.innerHTML).toBe("5");
});

async function mountWrapper(innerHTML, extraAttrs = {}) {
    const attrs = Object.entries(extraAttrs)
        .map(([k, v]) => `${k}="${v}"`)
        .join(" ");

    mounted = await mountController(
        "char-counter",
        CharCounterController,
        `<span data-controller="char-counter" ${attrs}>${innerHTML}</span>`,
    );
}
