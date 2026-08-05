import { afterEach, expect, test } from "bun:test";

import {
    dispatchEvent,
    dispatchTurboSubmitEnd,
    dispatchTurboSubmitStart,
    mountController,
    wait,
} from "../../resources/js/helpers/test_stimulus.js";
import AutoSaveController from "../../resources/js/controllers/auto_save_controller.js";

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
    const scheduler = installFakeSaveScheduler();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
        succeed(form);
    };

    input.value = "Updated";
    dispatchEvent(input, "input");

    scheduler.runNext();

    expect(submits).toBe(1);
    expect(form.dataset.autoSaveState).toBe("saved");
    expect(status.textContent).toBe("Saved");
});

test.serial("cancels pending work during composition and saves the committed value", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    const scheduler = installFakeSaveScheduler();
    let submits = 0;
    form.requestSubmit = () => {
        submits++;
        succeed(form);
    };

    input.value = "Started";
    dispatchEvent(input, "input");

    input.value = "Composing";
    input.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    const composing = new Event("input", { bubbles: true, cancelable: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    composing.preventDefault();
    input.dispatchEvent(composing);

    expect(scheduler.pending()).toHaveLength(0);
    expect(submits).toBe(0);

    input.dispatchEvent(new Event("compositionend", { bubbles: true }));
    input.dispatchEvent(new Event("input", { bubbles: true }));
    scheduler.runNext();

    expect(submits).toBe(1);
    expect(form.dataset.autoSaveState).toBe("saved");
});

test.serial("does not requeue after an in-flight save finishes during composition", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    const scheduler = installFakeSaveScheduler();
    let submits = 0;
    form.requestSubmit = () => {
        submits++;
        dispatchTurboSubmitStart(form);
    };

    input.value = "First update";
    dispatchEvent(input, "input");
    scheduler.runNext();

    input.value = "Composing update";
    input.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);
    dispatchTurboSubmitEnd(form);

    expect(scheduler.pending()).toHaveLength(0);
    expect(form.dataset.autoSaveState).toBe("dirty");

    input.dispatchEvent(new Event("compositionend", { bubbles: true }));
    input.dispatchEvent(new Event("input", { bubbles: true }));
    scheduler.runNext();

    expect(submits).toBe(2);
});

test.serial("composition in an ignored field keeps an unrelated save scheduled", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
            <input name="active_tab" value="content" data-auto-save-ignore>
        </form>
    `);

    const input = document.querySelector('[name="title"]');
    const ignored = document.querySelector("[data-auto-save-ignore]");
    const scheduler = installFakeSaveScheduler();

    input.value = "Updated";
    dispatchEvent(input, "input");
    const pending = scheduler.pending()[0];

    ignored.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    ignored.dispatchEvent(composing);

    expect(scheduler.pending()).toEqual([pending]);
});

test.serial("prunes composing fields removed by a morph before scheduling another edit", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="editor" value="Original editor">
            <input name="title" value="Original title">
        </form>
    `);

    const { form } = elements();
    const editor = document.querySelector('[name="editor"]');
    const title = document.querySelector('[name="title"]');
    const scheduler = installFakeSaveScheduler();
    let submits = 0;
    form.requestSubmit = () => {
        submits++;
    };

    editor.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    editor.remove();
    title.value = "Updated title";
    dispatchEvent(title, "input");

    expect(scheduler.pending()).toHaveLength(1);
    scheduler.runNext();
    expect(submits).toBe(1);
});

test.serial("resumes a pending save when its composing field leaves the DOM", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="editor" value="Original editor">
            <input name="title" value="Original title">
        </form>
    `);

    const { form } = elements();
    const editor = document.querySelector('[name="editor"]');
    const title = document.querySelector('[name="title"]');
    const scheduler = installFakeSaveScheduler();
    let submits = 0;
    form.requestSubmit = () => {
        submits++;
    };

    title.value = "Updated title";
    dispatchEvent(title, "input");
    expect(scheduler.pending()).toHaveLength(1);

    editor.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    expect(scheduler.pending()).toHaveLength(0);

    editor.remove();
    mounted.controller.compositionObserver.takeRecords();
    mounted.controller.handleCompositionMutations();

    expect(scheduler.pending()).toHaveLength(1);
    scheduler.runNext();
    expect(submits).toBe(1);
});

test.serial("preserves the change delay while another field is composing", async () => {
    await setup(`
        <form
            data-controller="auto-save"
            data-auto-save-delay-value="50"
            data-auto-save-change-delay-value="5"
        >
            <input name="editor" value="Original editor">
            <select name="status">
                <option value="draft" selected>Draft</option>
                <option value="published">Published</option>
            </select>
        </form>
    `);

    const editor = document.querySelector('[name="editor"]');
    const select = document.querySelector("select");
    const scheduler = installFakeSaveScheduler();

    editor.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    select.value = "published";
    dispatchEvent(select, "change");

    expect(scheduler.pending()).toHaveLength(0);

    editor.dispatchEvent(new Event("compositionend", { bubbles: true }));

    expect(scheduler.pending()).toHaveLength(1);
    expect(scheduler.pending()[0].delay).toBe(5);
});

test.serial("waits for every composing field before scheduling a save", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original title">
            <input name="summary" value="Original summary">
        </form>
    `);

    const title = document.querySelector('[name="title"]');
    const summary = document.querySelector('[name="summary"]');
    const scheduler = installFakeSaveScheduler();

    title.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    summary.dispatchEvent(new Event("compositionstart", { bubbles: true }));

    title.value = "Committed title";
    title.dispatchEvent(new Event("compositionend", { bubbles: true }));
    title.dispatchEvent(new Event("input", { bubbles: true }));

    expect(scheduler.pending()).toHaveLength(0);

    summary.value = "Committed summary";
    summary.dispatchEvent(new Event("compositionend", { bubbles: true }));
    summary.dispatchEvent(new Event("input", { bubbles: true }));

    expect(scheduler.pending()).toHaveLength(1);
});

test.serial("resumes an unrelated pending save when composition ends without input", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original title">
            <input name="summary" value="Original summary">
        </form>
    `);

    const { form } = elements();
    const title = document.querySelector('[name="title"]');
    const summary = document.querySelector('[name="summary"]');
    const scheduler = installFakeSaveScheduler();
    let submits = 0;
    form.requestSubmit = () => {
        submits++;
    };

    title.value = "Updated title";
    dispatchEvent(title, "input");
    summary.dispatchEvent(new Event("compositionstart", { bubbles: true }));

    expect(scheduler.pending()).toHaveLength(0);

    summary.dispatchEvent(new Event("compositionend", { bubbles: true }));

    expect(scheduler.pending()).toHaveLength(1);
    scheduler.runNext();
    expect(submits).toBe(1);
});

test.serial("returns to idle when canceled composition restores the saved value", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    const scheduler = installFakeSaveScheduler();
    form.requestSubmit = () => {
        dispatchTurboSubmitStart(form);
    };

    input.value = "Saved update";
    dispatchEvent(input, "input");
    scheduler.runNext();

    input.dispatchEvent(new Event("compositionstart", { bubbles: true }));
    input.value = "Candidate";
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);
    dispatchTurboSubmitEnd(form);

    expect(form.dataset.autoSaveState).toBe("dirty");

    input.value = "Saved update";
    input.dispatchEvent(new Event("compositionend", { bubbles: true }));

    expect(form.dataset.autoSaveState).toBe("idle");
    expect(scheduler.pending()).toHaveLength(0);
});

test.serial("does not save when the value returns to its last saved state", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    installFakeSaveScheduler();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
    };

    input.value = "Changed";
    dispatchEvent(input, "input");
    input.value = "Original";
    dispatchEvent(input, "input");

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
    const scheduler = installFakeSaveScheduler();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
        succeed(form);
    };

    select.value = "published";
    dispatchEvent(select, "change");

    expect(scheduler.pending()[0].delay).toBe(5);
    scheduler.runNext();

    expect(submits).toBe(1);
});

test.serial("queues one more save when the form changes during an in-flight save", async () => {
    await setup(`
        <form data-controller="auto-save" data-auto-save-delay-value="5">
            <input name="title" value="Original">
        </form>
    `);

    const { form, input } = elements();
    const scheduler = installFakeSaveScheduler();
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

    scheduler.runNext();

    input.value = "Second update";
    dispatchEvent(input, "input");
    finishSubmit();

    scheduler.runNext();

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
    const scheduler = installFakeSaveScheduler();
    let usedSubmitter = null;

    form.requestSubmit = (button) => {
        usedSubmitter = button;
        succeed(form);
    };

    input.value = "Updated";
    dispatchEvent(input, "input");

    scheduler.runNext();

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
    installFakeSaveScheduler();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
    };

    ignored.value = "seo";
    dispatchEvent(ignored, "input");

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
    const scheduler = installFakeSaveScheduler();
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

    scheduler.runNext();

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
    const scheduler = installFakeSaveScheduler();
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

    scheduler.runNext();

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
    const scheduler = installFakeSaveScheduler();
    let submits = 0;

    form.requestSubmit = () => {
        submits++;
    };

    input.value = "Updated";
    dispatchEvent(input, "input");
    const pendingTimer = scheduler.pending()[0];
    mounted.controller.cancel();

    expect(submits).toBe(0);
    expect(pendingTimer.cancelled).toBe(true);
    expect(scheduler.pending()).toHaveLength(0);
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

function installFakeSaveScheduler() {
    const timers = [];

    mounted.controller.setSaveTimer = (callback, delay) => {
        const timer = { callback, delay, cancelled: false };
        timers.push(timer);

        return timer;
    };

    mounted.controller.clearSaveTimer = (timer) => {
        if (timer) timer.cancelled = true;
    };

    return {
        pending() {
            return timers.filter((timer) => !timer.cancelled);
        },
        runNext() {
            const timer = this.pending()[0];

            if (!timer) {
                throw new Error("Expected a pending auto-save timer.");
            }

            timer.cancelled = true;
            timer.callback();
        },
    };
}
