import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import RemoteFormController from "../../resources/js/controllers/remote_form_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("remoteSubmit clicks the submit button target", async () => {
    await mount(`
        <button type="button" data-remote-form-target="submitBtn">Submit</button>
    `);

    const button = document.querySelector("button");
    let clicked = false;
    button.click = () => { clicked = true; };

    mounted.controller.remoteSubmit();

    expect(clicked).toBe(true);
});

test.serial("remoteSubmit is a no-op when no submitBtn target", async () => {
    await mount(``);

    expect(() => mounted.controller.remoteSubmit()).not.toThrow();
});

test.serial("reset calls form.reset", async () => {
    await mount(``);

    let resetCalled = false;
    mounted.root.reset = () => { resetCalled = true; };

    mounted.controller.reset();

    expect(resetCalled).toBe(true);
});

async function mount(innerHTML) {
    mounted = await mountController(
        "remote-form",
        RemoteFormController,
        `<form data-controller="remote-form">${innerHTML}</form>`,
    );
}
