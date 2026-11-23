import { afterEach, expect, test } from "bun:test";

import {
    dispatchTurboSubmitEnd,
    dispatchTurboSubmitStart,
    mountController,
} from "../../resources/js/helpers/test_stimulus.js";
import UnsavedChangesController from "../../resources/js/controllers/unsaved_changes_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("preserves form and submit button actions", async () => {
    await mount(`
        <form data-controller="unsaved-changes" data-action="turbo:submit-end->other#finish">
            <input name="title" />
            <button type="submit" data-action="other#save">Save</button>
        </form>
    `);

    const form = document.querySelector("form");
    const submit = form.querySelector("button");

    expect(form.dataset.action).toBe("turbo:submit-end->other#finish");
    expect(submit.dataset.action).toBe("other#save");
});

test.serial("allows navigation when submission starts", async () => {
    await mountForm();

    dispatchTurboSubmitStart(mounted.root);

    expect(navigationIsBlocked()).toBe(false);
});

for (const detail of [{ success: false }, { success: false, error: new Error("Network error") }, {}]) {
    test.serial(`rearms navigation protection after unsuccessful submission: ${JSON.stringify(detail)}`, async () => {
        await mountForm();
        dispatchTurboSubmitStart(mounted.root);
        mounted.root.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail }));

        expect(navigationIsBlocked()).toBe(true);
    });
}

test.serial("keeps successful submission navigation allowed", async () => {
    await mountForm();
    dispatchTurboSubmitStart(mounted.root);
    dispatchTurboSubmitEnd(mounted.root, true);

    expect(navigationIsBlocked()).toBe(false);
});

test.serial("does not allow navigation on a prevented native submit", async () => {
    await mountForm();
    mounted.root.addEventListener("submit", (event) => event.preventDefault());

    mounted.root.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));

    expect(navigationIsBlocked()).toBe(true);
});

test.serial("ignores another form's submission lifecycle", async () => {
    await mountForm();
    const other = document.createElement("form");
    document.body.append(other);

    dispatchTurboSubmitStart(other);
    expect(navigationIsBlocked()).toBe(true);

    dispatchTurboSubmitStart(mounted.root);
    dispatchTurboSubmitEnd(other, false);
    expect(navigationIsBlocked()).toBe(false);
});

test.serial("keeps protection after form actions are rewritten by a render", async () => {
    await mountForm();
    mounted.root.dataset.action = "";

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));

    expect(navigationIsBlocked()).toBe(true);
});

test.serial("removes listeners on disconnect and restores them once on reconnect", async () => {
    await mountForm();
    let warnings = 0;
    window.confirm = () => {
        warnings++;
        return false;
    };

    mounted.controller.disconnect();
    mounted.root.dispatchEvent(new CustomEvent("turbo:submit-start", { bubbles: true }));
    expect(mounted.controller.allow).toBe(false);
    expect(navigationIsBlocked()).toBe(false);

    mounted.controller.connect();
    expect(navigationIsBlocked()).toBe(true);
    expect(warnings).toBe(1);
    mounted.root.dispatchEvent(new CustomEvent("turbo:submit-start", { bubbles: true }));
    expect(mounted.controller.allow).toBe(true);

    mounted.controller.disconnect();
    mounted.root.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success: false } }));
    expect(mounted.controller.allow).toBe(true);
});

async function mount(html) {
    mounted = await mountController("unsaved-changes", UnsavedChangesController, html);
}

async function mountForm() {
    await mount(`
        <form data-controller="unsaved-changes">
            <input name="title" value="Original" />
            <button type="submit">Save</button>
        </form>
    `);
    mounted.root.querySelector("input").value = "Changed";
    window.confirm = () => false;
}

function navigationIsBlocked() {
    const event = new Event("turbo:before-visit", { bubbles: true, cancelable: true });
    document.documentElement.dispatchEvent(event);

    return event.defaultPrevented;
}
