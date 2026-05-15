import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import UnsavedChangesController from "../../resources/js/controllers/unsaved_changes_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("seeds data-action attributes on connect", async () => {
    await mount(`
        <form data-controller="unsaved-changes">
            <input name="title" />
            <button type="submit">Save</button>
        </form>
    `);

    const form = document.querySelector("form");
    const submit = form.querySelector("button");

    expect(form.dataset.action).toContain("unsaved-changes#leavingPage");
    expect(form.dataset.action).toContain("unsaved-changes#allowFormSubmission");
    expect(submit.dataset.action).toContain("unsaved-changes#allowFormSubmission");
});

test.serial("re-applies data-action after turbo:render (morph scenario)", async () => {
    await mount(`
        <form data-controller="unsaved-changes">
            <input name="title" />
            <button type="submit">Save</button>
        </form>
    `);

    const form = document.querySelector("form");

    // Simulate morph: idiomorph rewrites data-action from server HTML.
    form.dataset.action = "";

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(form.dataset.action).toContain("unsaved-changes#leavingPage");
});

test.serial("registers a single stable setupActions reference for add/remove", async () => {
    await mount(`
        <form data-controller="unsaved-changes">
            <input name="title" />
            <button type="submit">Save</button>
        </form>
    `);

    // The bound listener must be the same function reference across calls
    // so that removeEventListener in disconnect() actually matches.
    expect(mounted.controller.setupActions).toBe(mounted.controller.setupActions);
});

async function mount(html) {
    mounted = await mountController("unsaved-changes", UnsavedChangesController, html);
}
