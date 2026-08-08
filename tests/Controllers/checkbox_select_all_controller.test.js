import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import CheckboxSelectAllController from "../../resources/js/controllers/checkbox_select_all_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("master becomes indeterminate when some children are checked", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
    `);

    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(true);
});

test.serial("master is fully checked when all children are checked", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
    `);

    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    expect(master.checked).toBe(true);
    expect(master.indeterminate).toBe(false);
});

test.serial("keeps a partial selection binary when indeterminate is disabled", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
    `, 'data-checkbox-select-all-disable-indeterminate-value="true"');

    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(false);
});

test.serial("keeps the master unchecked when an indeterminate-disabled group is empty", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
    `, 'data-checkbox-select-all-disable-indeterminate-value="true"');

    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(false);
});

test.serial("refreshes and clears stale state when disable-indeterminate changes", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
    `);

    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    expect(master.indeterminate).toBe(true);

    mounted.root.setAttribute("data-checkbox-select-all-disable-indeterminate-value", "true");
    mounted.controller.disableIndeterminateValueChanged();

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(false);

    mounted.root.removeAttribute("data-checkbox-select-all-disable-indeterminate-value");
    mounted.controller.disableIndeterminateValueChanged();

    expect(master.indeterminate).toBe(true);
});

test.serial("re-syncs master state after turbo:render (morph scenario)", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
    `);

    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    const items = document.querySelectorAll('[data-checkbox-select-all-target="checkbox"]');

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(false);

    // Simulate morph: idiomorph updates children's checked state from server HTML.
    // No change event fires — targetConnected doesn't trigger either.
    items[0].checked = true;

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(true);
});

test.serial("re-syncs master state after a native form reset", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
    `, "", { form: true });

    const form = document.querySelector("form");
    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    const items = document.querySelectorAll('[data-checkbox-select-all-target="checkbox"]');
    items[1].checked = true;
    items[1].dispatchEvent(new Event("change", { bubbles: true }));
    expect(master.checked).toBe(true);

    form.reset();
    await wait(0);

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(true);
});

test.serial("re-syncs when the controller contains the reset form", async () => {
    mounted = await mountController(
        "checkbox-select-all",
        CheckboxSelectAllController,
        `<div data-controller="checkbox-select-all">
            <form id="checkbox-form">
                <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
                <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
                <input type="checkbox" data-checkbox-select-all-target="checkbox" />
            </form>
        </div>`,
    );

    const form = document.querySelector("form");
    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    const items = document.querySelectorAll('[data-checkbox-select-all-target="checkbox"]');
    items[1].checked = true;
    items[1].dispatchEvent(new Event("change", { bubbles: true }));
    expect(master.checked).toBe(true);

    form.reset();
    await wait(0);

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(true);
});

test.serial("rebinds reset handling when a wrapped form is replaced", async () => {
    mounted = await mountController(
        "checkbox-select-all",
        CheckboxSelectAllController,
        `<div data-controller="checkbox-select-all">
            <form>
                <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
                <input type="checkbox" data-checkbox-select-all-target="checkbox" />
            </form>
        </div>`,
    );

    mounted.root.innerHTML = `
        <form id="replacement-form">
            <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
            <input type="checkbox" data-checkbox-select-all-target="checkbox" checked />
            <input type="checkbox" data-checkbox-select-all-target="checkbox" />
        </form>
    `;
    await wait(0);
    // Rebinding rides on Stimulus target callbacks, which happy-dom's MutationObserver does not
    // deliver reliably. Drive it directly here; the observer wiring is covered in Playwright.
    mounted.controller.syncForm();

    const form = document.querySelector("#replacement-form");
    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    const items = document.querySelectorAll('[data-checkbox-select-all-target="checkbox"]');
    items[1].checked = true;
    // The replaced target's change listener also rides on the same MutationObserver callbacks as
    // syncForm(); drive the state directly here and leave the real observer wiring to Playwright.
    mounted.controller.refresh();
    expect(master.checked).toBe(true);

    form.reset();
    await wait(0);

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(true);
});

test.serial("re-syncs only for the Turbo Frame that contains the group", async () => {
    await mount(`
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
        <input type="checkbox" data-checkbox-select-all-target="checkbox" />
    `, "", { frame: true });

    const frame = document.querySelector("#checkbox-frame");
    const master = document.querySelector('[data-checkbox-select-all-target="checkboxAll"]');
    const item = document.querySelector('[data-checkbox-select-all-target="checkbox"]');
    const unrelated = document.body.appendChild(document.createElement("turbo-frame"));
    item.checked = true;

    unrelated.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    expect(master.indeterminate).toBe(false);

    frame.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    expect(master.indeterminate).toBe(true);
});

async function mount(html, values = "", { form = false, frame = false } = {}) {
    mounted = await mountController(
        "checkbox-select-all",
        CheckboxSelectAllController,
        `${frame ? '<turbo-frame id="checkbox-frame">' : ""}
        ${form ? '<form id="checkbox-form">' : ""}
        <div data-controller="checkbox-select-all" ${values}>${html}</div>
        ${form ? "</form>" : ""}
        ${frame ? "</turbo-frame>" : ""}`,
    );
}
