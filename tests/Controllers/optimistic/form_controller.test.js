import { afterEach, expect, test } from "bun:test";

import {
    dispatchTurboSubmitEnd,
    dispatchTurboSubmitStart,
    mountController,
} from "../../../resources/js/helpers/test_stimulus.js";
import FormController from "../../../resources/js/controllers/optimistic/form_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- submit-start dispatches optimistic streams ---

test.serial("submit-start on the form dispatches a stream per template", async () => {
    await mount(`
        <input name="title" value="Hello" />
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div><span data-field="title">placeholder</span></div>
        </template>
    `);

    dispatchTurboSubmitStart(mounted.root);

    const streams = document.body.querySelectorAll("turbo-stream");
    expect(streams).toHaveLength(1);
    const span = streams[0].querySelector("template").content.querySelector("[data-field]");
    expect(span.textContent).toBe("Hello");
});

test.serial("submit-start from a nested form is ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>Should not appear</div>
        </template>
        <fieldset>
            <form id="inner"></form>
        </fieldset>
    `);
    const inner = document.getElementById("inner");

    dispatchTurboSubmitStart(inner);

    expect(document.body.querySelectorAll("turbo-stream")).toHaveLength(0);
});

// --- submit-end + reset value ---

test.serial("submit-end with reset=true and success=true calls form.reset()", async () => {
    await mount(``, { reset: true });
    let resetCalls = 0;
    mounted.root.reset = () => { resetCalls++; };

    dispatchTurboSubmitEnd(mounted.root, true);

    expect(resetCalls).toBe(1);
});

test.serial("submit-end with reset=false does not reset", async () => {
    await mount(``);
    let resetCalls = 0;
    mounted.root.reset = () => { resetCalls++; };

    dispatchTurboSubmitEnd(mounted.root, true);

    expect(resetCalls).toBe(0);
});

test.serial("submit-end with reset=true but success=false does not reset", async () => {
    await mount(``, { reset: true });
    let resetCalls = 0;
    mounted.root.reset = () => { resetCalls++; };

    dispatchTurboSubmitEnd(mounted.root, false);

    expect(resetCalls).toBe(0);
});

test.serial("submit-end from a nested form is ignored even with reset=true", async () => {
    await mount(`<form id="inner"></form>`, { reset: true });
    let resetCalls = 0;
    mounted.root.reset = () => { resetCalls++; };

    dispatchTurboSubmitEnd(document.getElementById("inner"), true);

    expect(resetCalls).toBe(0);
});

// --- disconnect removes listeners ---

test.serial("disconnect detaches turbo:submit-start so later dispatches are ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>A</div>
        </template>
    `);

    mounted.controller.disconnect();
    dispatchTurboSubmitStart(mounted.root);

    expect(document.body.querySelectorAll("turbo-stream")).toHaveLength(0);
});

async function mount(innerHTML, { reset = false } = {}) {
    const attrs = reset ? ` data-optimistic--form-reset-value="true"` : "";
    mounted = await mountController(
        "optimistic--form",
        FormController,
        `<form data-controller="optimistic--form"${attrs}>${innerHTML}</form>`,
    );
}
