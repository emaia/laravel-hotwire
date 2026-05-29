import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import TabsController from "../../resources/js/controllers/tabs_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("activates the first tab by default", async () => {
    await mount();

    const tabs = tabEls();
    const panels = panelEls();

    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
    expect(tabs[0].getAttribute("tabindex")).toBe("0");
    expect(tabs[1].getAttribute("aria-selected")).toBe("false");
    expect(tabs[1].getAttribute("tabindex")).toBe("-1");

    expect(panels[0].hidden).toBe(false);
    expect(panels[1].hidden).toBe(true);
    expect(panels[2].hidden).toBe(true);
});

test.serial("respects a tab pre-selected in the DOM", async () => {
    await mount({ selectedAttr: 1 });

    const tabs = tabEls();
    expect(tabs[1].getAttribute("aria-selected")).toBe("true");
    expect(panelEls()[1].hidden).toBe(false);
    expect(panelEls()[0].hidden).toBe(true);
});

test.serial("respects selectedIndexValue", async () => {
    await mount({ selectedIndexValue: 2 });

    expect(tabEls()[2].getAttribute("aria-selected")).toBe("true");
    expect(panelEls()[2].hidden).toBe(false);
});

test.serial("selects a tab on click", async () => {
    await mount();
    const tabs = tabEls();

    tabs[1].dispatchEvent(new MouseEvent("click", { bubbles: true }));
    await wait(0);

    expect(tabs[1].getAttribute("aria-selected")).toBe("true");
    expect(tabs[0].getAttribute("aria-selected")).toBe("false");
    expect(panelEls()[1].hidden).toBe(false);
    expect(panelEls()[0].hidden).toBe(true);
});

test.serial("ArrowRight moves to the next tab and wraps", async () => {
    await mount();
    const tabs = tabEls();

    press(tabs[0], "ArrowRight");
    expect(tabs[1].getAttribute("aria-selected")).toBe("true");

    press(tabs[1], "ArrowRight");
    expect(tabs[2].getAttribute("aria-selected")).toBe("true");

    press(tabs[2], "ArrowRight");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
});

test.serial("ArrowLeft moves to the previous tab and wraps", async () => {
    await mount();
    const tabs = tabEls();

    press(tabs[0], "ArrowLeft");
    expect(tabs[2].getAttribute("aria-selected")).toBe("true");
});

test.serial("Home and End jump to the first and last tabs", async () => {
    await mount({ selectedIndexValue: 1 });
    const tabs = tabEls();

    press(tabs[1], "End");
    expect(tabs[2].getAttribute("aria-selected")).toBe("true");

    press(tabs[2], "Home");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
});

test.serial("ignores unrelated keys", async () => {
    await mount();
    const tabs = tabEls();

    press(tabs[0], "Enter");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
});

test.serial("dispatches tabs:change with index, tab and panel", async () => {
    await mount();
    const tabs = tabEls();

    let detail = null;
    mounted.root.addEventListener("tabs:change", (event) => (detail = event.detail));

    tabs[2].dispatchEvent(new MouseEvent("click", { bubbles: true }));
    await wait(0);

    expect(detail.index).toBe(2);
    expect(detail.tab).toBe(tabs[2]);
    expect(detail.panel).toBe(panelEls()[2]);
});

// --- helpers ---

function tabEls() {
    return [...document.querySelectorAll('[role="tab"]')];
}

function panelEls() {
    return [...document.querySelectorAll('[role="tabpanel"]')];
}

function press(tab, key) {
    tab.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true }));
}

async function mount({ selectedAttr = null, selectedIndexValue = null } = {}) {
    const selected = (i) => (selectedAttr === i ? 'aria-selected="true"' : "");
    const valueAttr = selectedIndexValue === null ? "" : `data-tabs-selected-index-value="${selectedIndexValue}"`;

    mounted = await mountController(
        "tabs",
        TabsController,
        `
        <div data-controller="tabs" ${valueAttr}>
            <div role="tablist" data-tabs-target="tablist"
                 data-action="click->tabs#select keydown->tabs#navigate">
                <button role="tab" id="t1" aria-controls="p1" data-tabs-target="tab" ${selected(0)}>One</button>
                <button role="tab" id="t2" aria-controls="p2" data-tabs-target="tab" ${selected(1)}>Two</button>
                <button role="tab" id="t3" aria-controls="p3" data-tabs-target="tab" ${selected(2)}>Three</button>
            </div>
            <div role="tabpanel" id="p1" data-tabs-target="panel" tabindex="0">P1</div>
            <div role="tabpanel" id="p2" data-tabs-target="panel" tabindex="0">P2</div>
            <div role="tabpanel" id="p3" data-tabs-target="panel" tabindex="0">P3</div>
        </div>`,
    );
}
