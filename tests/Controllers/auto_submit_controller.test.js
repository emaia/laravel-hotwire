import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import AutoSubmitController from "../../resources/js/controllers/auto_submit_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("submit fires immediately", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <select data-action="change->auto-submit#submit"></select>
        </form>
    `);

    const { select, submits } = elements();

    dispatchEvent(select, "change");

    expect(submits()).toBe(1);
});

test.serial("debouncedSubmit coalesces rapid events into a single request", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="20">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");
    dispatchEvent(input, "input");
    dispatchEvent(input, "input");

    expect(submits()).toBe(0);

    await wait(40);

    expect(submits()).toBe(1);
});

test.serial("debouncedSubmit is debounced by default", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");

    expect(submits()).toBe(0);
});

test.serial("a delay of 0 makes debouncedSubmit immediate", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");

    expect(submits()).toBe(1);
});

test.serial("submit cancels a pending debounced submit", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="50">
            <input data-action="input->auto-submit#debouncedSubmit">
            <select data-action="change->auto-submit#submit"></select>
        </form>
    `);

    const { input, select, submits } = elements();

    dispatchEvent(input, "input");
    dispatchEvent(select, "change");

    expect(submits()).toBe(1);

    await wait(70);

    expect(submits()).toBe(1);
});

test.serial("disconnect cancels a pending debounced submit", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="50">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");
    mounted.controller.disconnect();

    await wait(70);

    expect(submits()).toBe(0);
});

function elements() {
    const form = document.querySelector("form");
    let count = 0;
    form.requestSubmit = () => {
        count++;
    };

    return {
        form,
        input: document.querySelector("input"),
        select: document.querySelector("select"),
        submits: () => count,
    };
}

async function setup(html) {
    mounted = await mountController("auto-submit", AutoSubmitController, html);
}
