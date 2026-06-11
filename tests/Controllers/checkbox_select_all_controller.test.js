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
    expect(master.checked).toBe(true);
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

    expect(master.checked).toBe(true);
    expect(master.indeterminate).toBe(true);
});

async function mount(html) {
    mounted = await mountController(
        "checkbox-select-all",
        CheckboxSelectAllController,
        `<div data-controller="checkbox-select-all">${html}</div>`,
    );
}
