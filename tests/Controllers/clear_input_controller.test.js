import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ClearInputController from "../../resources/js/controllers/clear_input_controller.js";
import { normalizeSnapshot } from "../../resources/js/controllers/_snapshot.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial(
    "normalizes detached search snapshots without changing the live control or application classes",
    async () => {
        await mount(`
        <span data-controller="clear-input" class="app-root">
            <input type="search" data-clear-input-target="input" class="app-input" value="Trees" />
            <button data-clear-input-target="clearButton" class="hidden app-button"></button>
        </span>
    `);
        const input = document.querySelector("input");
        input.focus();
        const copy = mounted.root.cloneNode(true);
        normalizeSnapshot(copy);

        expect(copy.hasAttribute("data-clear-input-connected")).toBe(false);
        expect(copy.className).toBe("app-root");
        expect(copy.querySelector("input").className).toBe("app-input");
        expect(copy.querySelector("input").value).toBe("Trees");
        expect([...copy.querySelector("button").classList].sort()).toEqual(["app-button", "hidden"]);
        expect(mounted.root.hasAttribute("data-clear-input-connected")).toBe(true);
        expect(input.classList.contains("clear-input--touched")).toBe(true);
        expect(document.querySelector("button").classList.contains("hidden")).toBe(false);
    },
);

test.serial("tracks connection independently of the input's visual touched state", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input type="search" data-clear-input-target="input" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    expect(mounted.root.hasAttribute("data-clear-input-connected")).toBe(true);
    expect(document.querySelector("input").classList.contains("clear-input--touched")).toBe(false);
    mounted.application.unload("clear-input");
    expect(mounted.root.hasAttribute("data-clear-input-connected")).toBe(false);
    const input = document.querySelector("input");
    input.value = "Trees";
    window.dispatchEvent(new Event("pageshow"));
    expect(input.classList.contains("clear-input--touched")).toBe(false);
});

test.serial("resynchronizes programmatic values on keydown without consuming normal keys", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input type="search" data-clear-input-target="input" />
            <button data-clear-input-target="clearButton" class="hidden"></button>
        </span>
    `);

    const input = document.querySelector("input");
    input.focus();
    input.value = "Trees";
    const event = new KeyboardEvent("keydown", { key: "ArrowLeft", bubbles: true, cancelable: true });
    input.dispatchEvent(event);

    expect(input.classList.contains("clear-input--touched")).toBe(true);
    expect(document.querySelector("button").classList.contains("hidden")).toBe(false);
    expect(input.value).toBe("Trees");
    expect(event.defaultPrevented).toBe(false);
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

test.serial("leaves clear button visibility CSS to the shared foundation", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    expect(document.head.querySelectorAll("style")).toHaveLength(0);
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

test.serial("dispatches native input before inputCleared when cleared", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);

    const input = document.querySelector("input");
    const button = document.querySelector("[data-clear-input-target='clearButton']");
    const events = [];

    input.addEventListener("input", () => events.push(`input:${input.value}`));
    input.addEventListener("inputCleared", () => events.push(`inputCleared:${input.value}`));

    button.click();

    expect(input.value).toBe("");
    expect(events).toEqual(["input:", "inputCleared:"]);
});

for (const attribute of ["disabled", "readonly"]) {
    test.serial(`does not clear or emit events when the input becomes ${attribute}`, async () => {
        await mount(`
            <span data-controller="clear-input">
                <input data-clear-input-target="input" value="hello" />
                <button data-clear-input-target="clearButton"></button>
            </span>
        `);

        const input = document.querySelector("input");
        const events = [];
        input.setAttribute(attribute, "");
        input.addEventListener("input", () => events.push("input"));
        input.addEventListener("inputCleared", () => events.push("inputCleared"));

        document.querySelector("button").click();

        expect(input.value).toBe("hello");
        expect(events).toEqual([]);
    });
}

test.serial("Escape emits the clear contract for search inputs and consumes only that key", async () => {
    await mount(`
        <span data-controller="clear-input">
            <input type="search" data-clear-input-target="input" value="hello" />
            <button data-clear-input-target="clearButton"></button>
        </span>
    `);
    const input = document.querySelector("input");
    const events = [];
    input.addEventListener("input", () => events.push("input"));
    input.addEventListener("inputCleared", () => events.push("inputCleared"));
    const escape = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    input.dispatchEvent(escape);

    expect(input.value).toBe("");
    expect(events).toEqual(["input", "inputCleared"]);
    expect(escape.defaultPrevented).toBe(true);
    const emptyEscape = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    input.dispatchEvent(emptyEscape);
    expect(emptyEscape.defaultPrevented).toBe(false);
    expect(events).toHaveLength(2);
});

for (const scenario of ["text", "readonly", "disabled", "composing", "legacy-composing", "prevented"]) {
    test.serial(`does not consume Escape when ${scenario}`, async () => {
        await mount(`
            <span data-controller="clear-input">
                <input type="${scenario === "text" ? "text" : "search"}" data-clear-input-target="input" value="hello" />
                <button data-clear-input-target="clearButton"></button>
            </span>
        `);
        const input = document.querySelector("input");
        if (["disabled", "readonly"].includes(scenario)) input.setAttribute(scenario, "");
        const event = new KeyboardEvent("keydown", {
            key: "Escape",
            bubbles: true,
            cancelable: true,
            isComposing: scenario === "composing",
            keyCode: scenario === "legacy-composing" ? 229 : 0,
        });
        if (scenario === "prevented") event.preventDefault();
        input.dispatchEvent(event);
        expect(input.value).toBe("hello");
        expect(event.defaultPrevented).toBe(scenario === "prevented");
    });
}

async function mount(html) {
    mounted = await mountController("clear-input", ClearInputController, html);
}
