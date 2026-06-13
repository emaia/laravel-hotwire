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

test.serial("shows button when input receives focus and has a value", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton" class="hidden"></button>
        </span>
    `);

    const input = document.querySelector("input");
    const button = document.querySelector("[data-clear-input-target='clearButton']");

    input.focus();
    await wait(0);

    expect(button.classList.contains("hidden")).toBe(false);
});

test.serial("keeps button visible when focus moves to clear button within span", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton" class="hidden"></button>
        </span>
    `);

    const input = document.querySelector("input");
    const button = document.querySelector("[data-clear-input-target='clearButton']");

    input.focus();
    await wait(0);
    expect(button.classList.contains("hidden")).toBe(false);

    const focusoutEvent = new Event("focusout", { bubbles: true });
    focusoutEvent.relatedTarget = button;
    input.dispatchEvent(focusoutEvent);
    await wait(0);

    expect(button.classList.contains("hidden")).toBe(false);
});

test.serial("hides button when focus leaves the span", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton" class="hidden"></button>
        </span>
        <button id="outside">Outside</button>
    `);

    const input = document.querySelector("input");
    const button = document.querySelector("[data-clear-input-target='clearButton']");
    const outside = document.getElementById("outside");

    input.focus();
    await wait(0);
    expect(button.classList.contains("hidden")).toBe(false);

    const focusoutEvent = new Event("focusout", { bubbles: true });
    focusoutEvent.relatedTarget = outside;
    input.dispatchEvent(focusoutEvent);
    await wait(0);

    expect(button.classList.contains("hidden")).toBe(true);
});

test.serial("hides button when input value becomes empty", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton" class="hidden"></button>
        </span>
    `);

    const input = document.querySelector("input");
    const button = document.querySelector("[data-clear-input-target='clearButton']");

    input.focus();
    await wait(0);
    expect(button.classList.contains("hidden")).toBe(false);

    input.value = "";
    input.dispatchEvent(new Event("input", { bubbles: true }));
    await wait(0);

    expect(button.classList.contains("hidden")).toBe(true);
});

async function mount(html) {
    mounted = await mountController("clear-input", ClearInputController, html);
}
