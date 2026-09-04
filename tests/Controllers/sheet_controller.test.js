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
             data-sheet-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-action="sheet#toggle">Open</button>
            <div data-sheet-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-sheet-target="backdrop" data-action="click->sheet#clickOutside"></div>
                <div data-sheet-target="dialog">
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
             data-sheet-lock-scroll-class="overflow-hidden">
            <a href="/settings" data-turbo-frame="settings-panel">Settings</a>
            <div data-sheet-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-sheet-target="backdrop" data-action="click->sheet#clickOutside"></div>
                <div data-sheet-target="dialog">
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
    expect(document.querySelector('[data-sheet-target="modal"]').dataset.state).toBe("open");

    click(document.getElementById("close"));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(true);
});

test("connect applies visible state when the sheet is pre-rendered open", async () => {
    await mount(`
        <div data-controller="sheet"
             data-sheet-lock-scroll-class="overflow-hidden">
            <div data-sheet-target="modal" data-state="open" data-motion="none">
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog">
                    <p>Sheet content</p>
                </div>
            </div>
        </div>
    `);

    expect(mounted.controller.isOpen).toBe(true);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(false);
    expect(document.querySelector('[data-sheet-target="modal"]').dataset.state).toBe("open");
    expect(document.querySelector('[data-sheet-target="modal"]').hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

test("none preserves opener focus while keeping the inherited trap active", async () => {
    await mount(`
        <div data-controller="sheet"
             data-sheet-initial-focus-value="none"
             data-sheet-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-action="sheet#toggle">Open</button>
            <div data-sheet-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog"><button id="inside">Inside</button></div>
            </div>
        </div>
    `);
    const trigger = document.getElementById("trigger");
    trigger.focus();

    click(trigger);
    await wait(10);

    expect(document.activeElement).toBe(trigger);

    const tab = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
    document.dispatchEvent(tab);

    expect(tab.defaultPrevented).toBe(true);
    expect(document.activeElement.id).toBe("inside");
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
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(true);

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
