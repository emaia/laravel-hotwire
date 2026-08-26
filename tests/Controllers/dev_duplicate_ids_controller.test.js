import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import DevDuplicateIdsController from "../../resources/js/controllers/dev/duplicate_ids_controller.js";

let mounted;
let originalConsoleWarn;

beforeEach(() => {
    originalConsoleWarn = console.warn;
    console.warn = mock(() => {});
});

afterEach(async () => {
    console.warn = originalConsoleWarn;
    await mounted?.cleanup();
    mounted = null;
});

test.serial("warns when an automatic package id is duplicated", async () => {
    await mount(`
        <main data-controller="dev--duplicate-ids">
            <div id="hw-modal-page-1"></div>
            <div id="hw-modal-page-1"></div>
        </main>
    `);

    expect(console.warn).toHaveBeenCalledTimes(1);
    expect(console.warn.mock.calls[0][0]).toContain(
        'Duplicate package-style component id "hw-modal-page-1"',
    );
    expect(console.warn.mock.calls[0][1]).toHaveLength(2);
});

test.serial("warns when a model-derived component id is duplicated", async () => {
    await mount(`
        <main data-controller="dev--duplicate-ids">
            <div id="dropdown_task_42"></div>
            <div id="dropdown_task_42"></div>
        </main>
    `);

    expect(console.warn).toHaveBeenCalledTimes(1);
    expect(console.warn.mock.calls[0][0]).toContain(
        'Duplicate DOM id "dropdown_task_42"',
    );
    expect(console.warn.mock.calls[0][1]).toHaveLength(2);
});

async function mount(html) {
    mounted = await mountController("dev--duplicate-ids", DevDuplicateIdsController, html);
}
