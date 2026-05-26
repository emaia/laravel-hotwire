import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ResetFilesController from "../../resources/js/controllers/reset_files_controller.js";

let mounted;

function simulateFileSelection(input) {
    let captured = "C:\\fakepath\\test.pdf";
    Object.defineProperty(input, "value", {
        get() { return captured; },
        set(v) { captured = v; },
        configurable: true,
    });
}

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- Successful submit resets ---

test.serial("resets file input after successful submit (no errors)", async () => {
    await mount(`
        <div data-controller="reset-files" data-reset-on-success="true">
            <input type="file" />
        </div>
    `);

    const root = document.querySelector("[data-controller='reset-files']");
    const input = root.querySelector("input[type='file']");
    simulateFileSelection(input);

    const form = domEl("form");
    document.body.appendChild(form);
    form.appendChild(root);

    dispatchTurboSubmitEnd(form, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).toBe("");
});

test.serial("resets all file inputs inside the element after success", async () => {
    await mount(`
        <div data-controller="reset-files" data-reset-on-success="true">
            <input type="file" name="avatar" />
            <input type="file" name="document" />
        </div>
    `);

    const root = document.querySelector("[data-controller='reset-files']");
    const inputs = root.querySelectorAll("input[type='file']");
    simulateFileSelection(inputs[0]);
    simulateFileSelection(inputs[1]);

    const form = domEl("form");
    document.body.appendChild(form);
    form.appendChild(root);

    dispatchTurboSubmitEnd(form, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(inputs[0].value).toBe("");
    expect(inputs[1].value).toBe("");
});

// --- Validation error: don't reset ---

test.serial("does not reset after failed submit (form has errors)", async () => {
    await mount(`
        <div data-controller="reset-files" data-reset-on-success="true">
            <input type="file" />
        </div>
    `);

    const root = document.querySelector("[data-controller='reset-files']");
    const input = root.querySelector("input[type='file']");
    simulateFileSelection(input);

    const form = domEl("form");
    document.body.appendChild(form);
    form.appendChild(root);
    // Mark form as having errors
    const indicator = document.createElement("span");
    indicator.setAttribute("aria-invalid", "true");
    form.appendChild(indicator);

    dispatchTurboSubmitEnd(form, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).not.toBe("");
});

test.serial("does not reset when form has no errors but submit was not successful", async () => {
    await mount(`
        <div data-controller="reset-files" data-reset-on-success="true">
            <input type="file" />
        </div>
    `);

    const root = document.querySelector("[data-controller='reset-files']");
    const input = root.querySelector("input[type='file']");
    simulateFileSelection(input);

    const form = domEl("form");
    document.body.appendChild(form);
    form.appendChild(root);

    dispatchTurboSubmitEnd(form, false);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).not.toBe("");
});

// --- Disabled when data-reset-on-success not set ---

test.serial("does nothing when data-reset-on-success is not set", async () => {
    await mount(`
        <div data-controller="reset-files">
            <input type="file" />
        </div>
    `);

    const root = document.querySelector("[data-controller='reset-files']");
    const input = root.querySelector("input[type='file']");
    simulateFileSelection(input);

    const form = domEl("form");
    document.body.appendChild(form);
    form.appendChild(root);

    dispatchTurboSubmitEnd(form, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).not.toBe("");
});

// --- Controller on form itself ---

test.serial("resets when controller is on the form itself", async () => {
    await mount(`
        <form data-controller="reset-files" data-reset-on-success="true">
            <input type="file" name="avatar" />
        </form>
    `);

    const form = document.querySelector("form");
    const input = document.querySelector("input[type='file']");
    simulateFileSelection(input);

    dispatchTurboSubmitEnd(form, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).toBe("");
});

test.serial("does not reset when form has errors but controller is on form", async () => {
    await mount(`
        <form data-controller="reset-files" data-reset-on-success="true">
            <input type="file" name="avatar" />
        </form>
    `);

    const form = document.querySelector("form");
    const input = document.querySelector("input[type='file']");
    simulateFileSelection(input);
    // Mark form as having errors
    const indicator = document.createElement("span");
    indicator.setAttribute("aria-invalid", "true");
    form.appendChild(indicator);

    dispatchTurboSubmitEnd(form, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).not.toBe("");
});

// --- Different form ---

test.serial("does not reset when a different form submits", async () => {
    await mount(`
        <div data-controller="reset-files" data-reset-on-success="true">
            <input type="file" />
        </div>
    `);

    const root = document.querySelector("[data-controller='reset-files']");
    const input = root.querySelector("input[type='file']");
    simulateFileSelection(input);

    const otherForm = domEl("form");

    dispatchTurboSubmitEnd(otherForm, true);
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).not.toBe("");
});

// --- Cleanup ---

test.serial("stops listening after disconnect", async () => {
    const { mounted: m, controller, root } = await mountRaw(`
        <div data-controller="reset-files" data-reset-on-success="true">
            <input type="file" />
        </div>
    `);

    const input = root.querySelector("input[type='file']");
    simulateFileSelection(input);

    dispatchTurboSubmitEnd(domEl("form"), true);
    controller.disconnect();

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(input.value).not.toBe("");

    await m.cleanup();
});

// --- Helpers ---

async function mount(html) {
    mounted = await mountController("reset-files", ResetFilesController, html);
}

async function mountRaw(html) {
    const result = await mountController("reset-files", ResetFilesController, html);
    return { mounted: result, controller: result.controller, root: result.root };
}

function domEl(tag) {
    return document.createElement(tag);
}

function dispatchTurboSubmitEnd(form, success) {
    form.dispatchEvent(
        new CustomEvent("turbo:submit-end", {
            bubbles: true,
            detail: { success },
        })
    );
}
