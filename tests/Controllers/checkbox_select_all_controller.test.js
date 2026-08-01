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
    await wait(0);

    expect(master.checked).toBe(false);
    expect(master.indeterminate).toBe(false);

    mounted.root.removeAttribute("data-checkbox-select-all-disable-indeterminate-value");
    await wait(0);

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

async function mount(html, values = "") {
    mounted = await mountController(
        "checkbox-select-all",
        CheckboxSelectAllController,
        `<div data-controller="checkbox-select-all" ${values}>${html}</div>`,
    );
}
