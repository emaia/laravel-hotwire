import { afterEach, expect, spyOn, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import SheetController from "../../resources/js/controllers/sheet_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.body.className = "";
});

async function mount(
    markup = `
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
    `,
) {
    mounted = await mountController("sheet", SheetController, markup);
    await wait(0);
}

async function mountFrame() {
    mounted = await mountController(
        "sheet",
        SheetController,
        `
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
    `,
    );
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

test("refresh streams stay native and do not implicitly close a sheet", async () => {
    await mountFrame();
    const frame = document.getElementById("settings-panel");

    frame.innerHTML = "<form>Settings form</form>";
    frame.dispatchEvent(new CustomEvent("turbo:frame-load", { bubbles: true }));
    await wait(10);

    const refresh = document.createElement("turbo-stream");
    refresh.setAttribute("action", "refresh");

    document.body.appendChild(refresh);
    const event = new CustomEvent("turbo:before-stream-render", { bubbles: true, cancelable: true });
    refresh.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(mounted.controller.isOpen).toBe(true);
    expect(document.querySelector('[data-sheet-target="modal"]').hidden).toBe(false);
    expect(frame.innerHTML).toContain("Settings form");
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

test("a sheet link sizes the authored side before loading; the response can change sides and closing resets it", async () => {
    await mountFrame();
    const root = document.querySelector('[data-controller="sheet"]');
    root.style.setProperty("--sheet-width", "75%");
    const dialog = root.querySelector('[data-sheet-target="dialog"]');
    dialog.dataset.side = "right";
    const link = root.querySelector('a[href="/settings"]');
    link.dataset.overlayFamily = "sheet";
    link.dataset.overlaySize = "28rem";
    click(link);

    expect(dialog.dataset.side).toBe("right");
    expect(root.style.getPropertyValue("--sheet-width")).toBe("28rem");

    const frame = document.getElementById("settings-panel");
    const response = document.createElement("turbo-frame");
    response.dataset.overlayFamily = "sheet";
    response.dataset.overlaySide = "bottom";
    frame.dispatchEvent(
        new CustomEvent("turbo:before-frame-render", { bubbles: true, detail: { newFrame: response } }),
    );
    expect(dialog.dataset.side).toBe("bottom");
    expect(root.style.getPropertyValue("--sheet-height")).toBe("28rem");
    expect(root.style.getPropertyValue("--sheet-width")).toBe("");

    await mounted.controller.open();
    await mounted.controller.close();
    expect(dialog.dataset.side).toBe("right");
    expect(root.style.getPropertyValue("--sheet-width")).toBe("75%");
    expect(root.style.getPropertyValue("--sheet-height")).toBe("");
});

test("abandoned sheet hints reset and the next opening reads newly authored defaults", async () => {
    await mountFrame();
    const root = document.querySelector('[data-controller="sheet"]');
    const dialog = root.querySelector('[data-sheet-target="dialog"]');
    dialog.dataset.side = "right";
    const link = root.querySelector('a[href="/settings"]');
    link.dataset.overlayFamily = "sheet";
    link.dataset.overlaySide = "left";
    click(link);
    expect(dialog.dataset.side).toBe("left");

    const frame = document.getElementById("settings-panel");
    frame.dispatchEvent(new CustomEvent("turbo:frame-missing", { bubbles: true }));
    expect(dialog.dataset.side).toBe("right");

    dialog.dataset.side = "top";
    root.style.setProperty("--sheet-height", "40vh");
    link.removeAttribute("data-overlay-side");
    link.dataset.overlaySize = "60vh";
    click(link);
    expect(dialog.dataset.side).toBe("top");
    expect(root.style.getPropertyValue("--sheet-height")).toBe("60vh");
    await mounted.controller.close();
    expect(root.style.getPropertyValue("--sheet-height")).toBe("40vh");
});

test("morph keeps active sheet presentation while accepting unrelated attributes and updated host defaults", async () => {
    await mountFrame();
    const root = document.querySelector('[data-controller="sheet"]');
    const dialog = root.querySelector('[data-sheet-target="dialog"]');
    root.style.cssText = "--sheet-width: 75%; color: red";
    dialog.dataset.side = "right";
    const link = root.querySelector('a[href="/settings"]');
    link.dataset.overlayFamily = "sheet";
    link.dataset.overlaySide = "left";
    link.dataset.overlaySize = "28rem";
    click(link);

    const incomingRoot = root.cloneNode(false);
    incomingRoot.style.cssText = "--sheet-width: 90%; color: blue";
    root.dispatchEvent(
        new CustomEvent("turbo:before-morph-element", { bubbles: true, detail: { newElement: incomingRoot } }),
    );
    const styleMorph = new CustomEvent("turbo:before-morph-attribute", {
        bubbles: true,
        cancelable: true,
        detail: { attributeName: "style" },
    });
    root.dispatchEvent(styleMorph);
    expect(styleMorph.defaultPrevented).toBe(false);
    root.style.cssText = incomingRoot.style.cssText;
    root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));
    expect(root.style.color).toBe("blue");
    expect(root.style.getPropertyValue("--sheet-width")).toBe("28rem");

    const incomingDialog = dialog.cloneNode(false);
    incomingDialog.dataset.side = "top";
    dialog.dispatchEvent(
        new CustomEvent("turbo:before-morph-element", { bubbles: true, detail: { newElement: incomingDialog } }),
    );
    const sideMorph = new CustomEvent("turbo:before-morph-attribute", {
        bubbles: true,
        cancelable: true,
        detail: { attributeName: "data-side" },
    });
    dialog.dispatchEvent(sideMorph);
    expect(sideMorph.defaultPrevented).toBe(true);
    dialog.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));
    expect(dialog.dataset.side).toBe("left");

    await mounted.controller.open();
    await mounted.controller.close();
    expect(dialog.dataset.side).toBe("top");
    expect(root.style.getPropertyValue("--sheet-width")).toBe("90%");
    expect(root.style.color).toBe("blue");
});

test("an incompatible overlay family reports its configuration error", async () => {
    await mountFrame();
    const error = spyOn(console, "error").mockImplementation(() => {});
    try {
        const link = document.querySelector('a[href="/settings"]');
        link.dataset.overlayFamily = "modal";
        link.dataset.overlaySize = "xl";
        click(link);

        expect(error).toHaveBeenCalledWith(
            expect.stringContaining('expects "modal", but the receiving host is "sheet"'),
        );
    } finally {
        error.mockRestore();
    }
});

test("Turbo cache discards presentation even when loading has not opened the sheet", async () => {
    await mountFrame();
    const root = document.querySelector('[data-controller="sheet"]');
    const dialog = root.querySelector('[data-sheet-target="dialog"]');
    dialog.dataset.side = "right";
    const link = root.querySelector('a[href="/settings"]');
    link.dataset.overlayFamily = "sheet";
    link.dataset.overlaySide = "left";
    click(link);
    expect(dialog.dataset.side).toBe("left");

    mounted.controller.closeForCache();
    expect(dialog.dataset.side).toBe("right");
    expect(root.style.getPropertyValue("--sheet-width")).toBe("");
});
