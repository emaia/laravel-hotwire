import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import SheetController from "../../resources/js/controllers/sheet_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.body.className = "";
});

async function mount() {
    mounted = await mountController("sheet", SheetController, `
        <div data-controller="sheet"
             data-sheet-open-duration-value="1"
             data-sheet-close-duration-value="1"
             data-sheet-hidden-class="pointer-events-none"
             data-sheet-visible-class="pointer-events-auto"
             data-sheet-backdrop-hidden-class="opacity-0"
             data-sheet-backdrop-visible-class="opacity-100"
             data-sheet-dialog-hidden-class="translate-x-full"
             data-sheet-dialog-visible-class="translate-x-0"
             data-sheet-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-action="sheet#toggle">Open</button>
            <div data-sheet-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-sheet-target="backdrop" data-action="click->sheet#clickOutside" class="opacity-0"></div>
                <div data-sheet-target="dialog" class="translate-x-full">
                    <button id="close" data-action="sheet#close">Close</button>
                </div>
            </div>
        </div>
    `);
    await wait(0);
}

function click(element) {
    element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
}

test("toggle opens and closes the sheet", async () => {
    await mount();

    click(document.getElementById("trigger"));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(false);
    expect(document.querySelector('[data-sheet-target="dialog"]').classList.contains("translate-x-0")).toBe(true);

    click(document.getElementById("close"));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(true);
});
