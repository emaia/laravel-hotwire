import { afterEach, expect, test } from "bun:test";

import {
    dispatchEvent,
    dispatchTurboSubmitEnd,
    dispatchTurboSubmitStart,
    mountController,
    wait,
} from "../helpers/test_stimulus.js";
import AutoSaveController from "./auto_save_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("saves a changed form after the input debounce", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
            <span data-auto-save-target="status"></span>
        </form>
    `);

    const { form, input, status } = elements();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
        succeed(form);
    };

    input.value = "Updated";
    dispatchEvent(input, "input");

    await wait(10);

    expect(submits).toBe(1);
    expect(form.dataset.autoSaveState).toBe("saved");
    expect(status.textContent).toBe("Saved");
});

test.serial("does not save when the value returns to its last saved state", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
    };

    input.value = "Changed";
    dispatchEvent(input, "input");
    input.value = "Original";
    dispatchEvent(input, "input");

    await wait(10);

    expect(submits).toBe(0);
    expect(form.dataset.autoSaveState).toBe("idle");
});

test.serial("uses the change delay for change events", async () => {
    await setup(`
        <form
            data-controller="auto-save"
            data-auto-save-delay-value="50"
            data-auto-save-change-delay-value="5"
        >
            <select name="status">
                <option value="draft" selected>Draft</option>
                <option value="published">Published</option>
            </select>
        </form>
    `);

    const form = document.querySelector("form");
    const select = document.querySelector("select");
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
        succeed(form);
    };

    select.value = "published";
    dispatchEvent(select, "change");

    await wait(10);

    expect(submits).toBe(1);
});

test.serial("queues one more save when the form changes during an in-flight save", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    let submits = 0;
    let finishSubmit;

    form.requestSubmit = () => {
        submits++;
        dispatchTurboSubmitStart(form);
        finishSubmit = (success = true) => {
            dispatchTurboSubmitEnd(form, success);
        };
    };

    input.value = "First update";
    dispatchEvent(input, "input");

    await wait(10);

    input.value = "Second update";
    dispatchEvent(input, "input");
    finishSubmit();

    await wait(10);

    expect(submits).toBe(2);
});

test.serial("uses the submitter target when present", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
            <button type="submit" data-auto-save-target="submitter" formaction="/drafts">Save draft</button>
        </form>
    `);

    const { form, input } = elements();
    const submitter = document.querySelector("button");
    let usedSubmitter = null;

    form.requestSubmit = (button) => {
        usedSubmitter = button;
        succeed(form);
    };

    input.value = "Updated";
    dispatchEvent(input, "input");

    await wait(10);

    expect(usedSubmitter).toBe(submitter);
});

test.serial("ignores fields marked with data-auto-save-ignore", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="active_tab" value="content" data-auto-save-ignore>
            <input name="title" value="Original">
        </form>
    `);

    const form = document.querySelector("form");
    const ignored = document.querySelector("[data-auto-save-ignore]");
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
    };

    ignored.value = "seo";
    dispatchEvent(ignored, "input");

    await wait(10);

    expect(submits).toBe(0);
    expect(form.dataset.autoSaveState).toBe("idle");
});

test.serial("applies configured state classes and dispatches lifecycle events", async () => {
    await setup(`
        <form
            data-controller="auto-save"
            data-auto-save-delay-value="5"
            data-auto-save-dirty-class="is-dirty"
            data-auto-save-saving-class="is-saving"
            data-auto-save-saved-class="is-saved"
        >
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    const events = [];

    form.addEventListener("auto-save:dirty", () => events.push("dirty"));
    form.addEventListener("auto-save:saving", () => events.push("saving"));
    form.addEventListener("auto-save:saved", () => events.push("saved"));

    form.requestSubmit = () => {
        expect(form.classList.contains("is-dirty")).toBe(true);
        succeed(form);
    };

    input.value = "Updated";
    dispatchEvent(input, "input");

    await wait(10);

    expect(form.classList.contains("is-dirty")).toBe(false);
    expect(form.classList.contains("is-saving")).toBe(false);
    expect(form.classList.contains("is-saved")).toBe(true);
    expect(events).toEqual(["dirty", "saving", "saved"]);
});

test.serial("sets the error state and dispatches error when submit fails", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5" data-auto-save-error-class="is-error">
            <input name="title" value="Original">
            <span data-auto-save-target="status"></span>
        </form>
    `);

    const { form, input, status } = elements();
    let errorEvents = 0;

    form.addEventListener("auto-save:error", () => {
        errorEvents++;
    });

    form.requestSubmit = () => {
        dispatchTurboSubmitStart(form);
        dispatchTurboSubmitEnd(form, false);
    };

    input.value = "Updated";
    dispatchEvent(input, "input");

    await wait(10);

    expect(form.dataset.autoSaveState).toBe("error");
    expect(form.classList.contains("is-error")).toBe(true);
    expect(status.textContent).toBe("Could not save");
    expect(errorEvents).toBe(1);
});

test.serial("cancel clears a pending save", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="50">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
    };

    input.value = "Updated";
    dispatchEvent(input, "input");
    mounted.controller.cancel();

    await wait(60);

    expect(submits).toBe(0);
    expect(form.dataset.autoSaveState).toBe("dirty");
});

function elements() {
    return {
        form: document.querySelector("form"),
        input: document.querySelector("input"),
        status: document.querySelector("[data-auto-save-target='status']"),
    };
}

function succeed(form) {
    dispatchTurboSubmitStart(form);
    dispatchTurboSubmitEnd(form);
}

async function setup(html) {
    mounted = await mountController("auto-save", AutoSaveController, html);
}
