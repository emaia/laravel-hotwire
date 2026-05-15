import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ClearInputController from "../../resources/js/controllers/clear_input_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("adds touched class on connect when input has a value", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    const input = document.querySelector("input");
    expect(input.classList.contains("clear-input--touched")).toBe(true);
});

test.serial("does not add touched class when input is empty", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    const input = document.querySelector("input");
    expect(input.classList.contains("clear-input--touched")).toBe(false);
});

test.serial("re-applies touched class after turbo:render (morph scenario)", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    const input = document.querySelector("input");
    expect(input.classList.contains("clear-input--touched")).toBe(true);

    // Simulate Turbo morph: idiomorph rewrites the class attribute from
    // server HTML which does not contain the runtime-added clear-input--touched class.
    input.classList.remove("clear-input--touched");
    // value remains because the morphed server HTML reflects old() input
    input.value = "morphed content";

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.classList.contains("clear-input--touched")).toBe(true);
});

test.serial("removes touched class after morph when input becomes empty", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    const input = document.querySelector("input");
    input.value = "";

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.classList.contains("clear-input--touched")).toBe(false);
});

async function mount(html) {
    mounted = await mountController("clear-input", ClearInputController, html);
}
