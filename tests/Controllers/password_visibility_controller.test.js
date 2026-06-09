import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import PasswordVisibilityController from "../../resources/js/controllers/password_visibility_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("connect forces type=password even if markup starts as text", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="text" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"></button>
        </div>
    `);

    const input = document.querySelector("input");
    expect(input.type).toBe("password");
});

test.serial("connect sets initial aria state on the button", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="password" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"></button>
        </div>
    `);

    const button = document.querySelector("button");
    expect(button.getAttribute("aria-pressed")).toBe("false");
    expect(button.getAttribute("aria-label")).toBe("Show password");
});

test.serial("toggle action flips input type and aria state", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="password" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"
                    data-action="password-visibility#toggle"></button>
        </div>
    `);

    const input = document.querySelector("input");
    const button = document.querySelector("button");

    button.click();
    expect(input.type).toBe("text");
    expect(button.getAttribute("aria-pressed")).toBe("true");
    expect(button.getAttribute("aria-label")).toBe("Hide password");

    button.click();
    expect(input.type).toBe("password");
    expect(button.getAttribute("aria-pressed")).toBe("false");
    expect(button.getAttribute("aria-label")).toBe("Show password");
});

test.serial("custom show/hide labels from values are applied", async () => {
    await mount(`
        <div data-controller="password-visibility"
             data-password-visibility-show-label-value="Mostrar senha"
             data-password-visibility-hide-label-value="Ocultar senha">
            <input type="password" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"
                    data-action="password-visibility#toggle"></button>
        </div>
    `);

    const button = document.querySelector("button");
    expect(button.getAttribute("aria-label")).toBe("Mostrar senha");

    button.click();
    expect(button.getAttribute("aria-label")).toBe("Ocultar senha");
});

test.serial("show action is idempotent and dispatches once per transition", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="password" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"></button>
        </div>
    `);

    const root = document.querySelector("[data-controller='password-visibility']");
    const input = document.querySelector("input");
    const controller = mounted.controller;

    const events = [];
    root.addEventListener("password-visibility:change", (event) => events.push(event.detail));

    controller.show();
    controller.show();

    expect(input.type).toBe("text");
    expect(events).toEqual([{ visible: true }]);
});

test.serial("hide action is idempotent and dispatches once per transition", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="text" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"></button>
        </div>
    `);

    const root = document.querySelector("[data-controller='password-visibility']");
    const input = document.querySelector("input");
    const controller = mounted.controller;

    const events = [];
    root.addEventListener("password-visibility:change", (event) => events.push(event.detail));

    // connect() already forced type=password and dispatched once for the initial transition.
    events.length = 0;

    controller.hide();
    controller.hide();

    expect(input.type).toBe("password");
    expect(events).toEqual([]);
});

test.serial("button target is optional — toggle works without it", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="password" data-password-visibility-target="input" />
        </div>
    `);

    const input = document.querySelector("input");
    mounted.controller.toggle();
    expect(input.type).toBe("text");

    mounted.controller.toggle();
    expect(input.type).toBe("password");
});

test.serial("dispatch event detail carries the new visibility", async () => {
    await mount(`
        <div data-controller="password-visibility">
            <input type="password" data-password-visibility-target="input" />
            <button type="button" data-password-visibility-target="button"
                    data-action="password-visibility#toggle"></button>
        </div>
    `);

    const root = document.querySelector("[data-controller='password-visibility']");
    const events = [];
    root.addEventListener("password-visibility:change", (event) => events.push(event.detail));

    document.querySelector("button").click();
    document.querySelector("button").click();

    expect(events).toEqual([{ visible: true }, { visible: false }]);
});

async function mount(html) {
    mounted = await mountController("password-visibility", PasswordVisibilityController, html);
}
