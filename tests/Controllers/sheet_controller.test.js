import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import SheetController from "../../resources/js/controllers/sheet_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.body.className = "";
});

async function mount(markup = `
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
    `) {
    mounted = await mountController("sheet", SheetController, markup);
    await wait(0);
}

async function mountFrame() {
    mounted = await mountController("sheet", SheetController, `
        <div id="sheet-shell"
             data-controller="sheet"
             data-sheet-open-duration-value="1"
             data-sheet-close-duration-value="1"
             data-sheet-hidden-class="pointer-events-none"
             data-sheet-visible-class="pointer-events-auto"
             data-sheet-backdrop-hidden-class="opacity-0"
             data-sheet-backdrop-visible-class="opacity-100"
             data-sheet-dialog-hidden-class="translate-x-full"
             data-sheet-dialog-visible-class="translate-x-0"
             data-sheet-lock-scroll-class="overflow-hidden">
            <a href="/settings" data-turbo-frame="settings-panel">Settings</a>
            <div data-sheet-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-sheet-target="backdrop" data-action="click->sheet#clickOutside" class="opacity-0"></div>
                <div data-sheet-target="dialog" class="translate-x-full">
                    <turbo-frame id="settings-panel" data-sheet-target="dynamicContent"></turbo-frame>
                    <template data-sheet-target="loadingTemplate"><div class="loading-state">Loading sheet...</div></template>
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

test("connect applies visible state when the sheet is pre-rendered open", async () => {
    await mount(`
        <div data-controller="sheet"
             data-sheet-hidden-class="pointer-events-none"
             data-sheet-visible-class="pointer-events-auto"
             data-sheet-backdrop-hidden-class="opacity-0"
             data-sheet-backdrop-visible-class="opacity-100"
             data-sheet-dialog-hidden-class="translate-x-full"
             data-sheet-dialog-visible-class="translate-x-0"
             data-sheet-lock-scroll-class="overflow-hidden">
            <div data-sheet-target="modal" data-open="true" hidden class="pointer-events-none">
                <div data-sheet-target="backdrop" class="opacity-0"></div>
                <div data-sheet-target="dialog" class="translate-x-full">
                    <p>Sheet content</p>
                </div>
            </div>
        </div>
    `);

    expect(mounted.controller.isOpen).toBe(true);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(false);
    expect(document.querySelector('[data-sheet-target="modal"]').dataset.open).toBe("true");
    expect(document.querySelector('[data-sheet-target="modal"]').classList.contains("pointer-events-auto")).toBe(true);
    expect(document.querySelector('[data-sheet-target="backdrop"]').classList.contains("opacity-100")).toBe(true);
    expect(document.querySelector('[data-sheet-target="dialog"]').classList.contains("translate-x-0")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
    expect(mounted.controller.openDurationValue).toBe(300);
    expect(mounted.controller.closeDurationValue).toBe(300);
});

test("frame content opens the sheet and loading templates are injected", async () => {
    await mountFrame();
    const frame = document.getElementById("settings-panel");

    click(document.querySelector('a[href="/settings"]'));
    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));
    expect(frame.innerHTML).toContain("Loading sheet...");

    frame.innerHTML = "<p>Settings form</p>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(true);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(false);
});

test("refresh streams wait for the sheet close animation", async () => {
    await mountFrame();
    const frame = document.getElementById("settings-panel");

    frame.innerHTML = "<form>Settings form</form>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    const refresh = document.createElement("turbo-stream");
    refresh.setAttribute("action", "refresh");

    let refreshed = false;
    refresh.performAction = () => {
        refreshed = true;
    };

    document.body.appendChild(refresh);
    refresh.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(refreshed).toBe(false);
    expect(mounted.controller.isOpen).toBe(false);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(false);

    await wait(10);

    expect(refreshed).toBe(true);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(true);
});

test("empty root streams wait for the sheet close animation", async () => {
    await mountFrame();
    const frame = document.getElementById("settings-panel");

    frame.innerHTML = "<form>Settings form</form>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    const root = document.getElementById("sheet-shell");
    const empty = document.createElement("turbo-stream");
    empty.setAttribute("action", "update");
    empty.setAttribute("target", "sheet-shell");
    empty.innerHTML = "<template></template>";

    let rendered = false;
    empty.performAction = () => {
        rendered = true;
        root.innerHTML = "";
    };

    document.body.appendChild(empty);
    empty.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(rendered).toBe(false);
    expect(root.innerHTML).toContain("Settings form");

    await wait(10);

    expect(rendered).toBe(true);
    expect(root.innerHTML).toBe("");
});

test("frame replacement keeps the sheet dynamic content target", async () => {
    await mountFrame();
    const frame = document.getElementById("settings-panel");
    const replacement = document.createElement("turbo-frame");

    replacement.id = "settings-panel";
    replacement.innerHTML = "<p>Replaced sheet content</p>";
    frame.replaceWith(replacement);

    replacement.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    replacement.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    expect(replacement.getAttribute("data-sheet-target")).toContain("dynamicContent");
    expect(mounted.controller.isOpen).toBe(true);
    expect(replacement.innerHTML).toContain("Replaced sheet content");
});
