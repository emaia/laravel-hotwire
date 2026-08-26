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

test.serial("ignores duplicated explicit ids", async () => {
    await mount(`
        <main data-controller="dev--duplicate-ids">
            <div id="task-editor"></div>
            <div id="task-editor"></div>
        </main>
    `);

    expect(console.warn).not.toHaveBeenCalled();
});

async function mount(html) {
    mounted = await mountController("dev--duplicate-ids", DevDuplicateIdsController, html);
}
