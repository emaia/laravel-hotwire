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
    expect(tabs[0].dataset.state).toBe("active");
    expect(tabs[0].getAttribute("tabindex")).toBe("0");
    expect(tabs[1].getAttribute("aria-selected")).toBe("false");
    expect(tabs[1].dataset.state).toBe("inactive");
    expect(tabs[1].getAttribute("tabindex")).toBe("-1");

    expect(panels[0].hidden).toBe(false);
    expect(panels[0].dataset.state).toBe("active");
    expect(panels[1].hidden).toBe(true);
    expect(panels[1].dataset.state).toBe("inactive");
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

test.serial("does not select a disabled tab on click", async () => {
    await mount({ disabled: 1 });
    const tabs = tabEls();

    tabs[1].dispatchEvent(new MouseEvent("click", { bubbles: true }));
    await wait(0);

    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
    expect(tabs[1].getAttribute("aria-selected")).toBe("false");
    expect(panelEls()[0].hidden).toBe(false);
    expect(panelEls()[1].hidden).toBe(true);
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

test.serial("keyboard navigation skips disabled tabs", async () => {
    await mount({ disabled: 1 });
    const tabs = tabEls();

    press(tabs[0], "ArrowRight");
    expect(tabs[2].getAttribute("aria-selected")).toBe("true");

    press(tabs[2], "ArrowLeft");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
});

test.serial("initial selection skips a disabled selectedIndexValue", async () => {
    await mount({ selectedIndexValue: 1, disabled: 1 });

    expect(tabEls()[0].getAttribute("aria-selected")).toBe("true");
    expect(panelEls()[0].hidden).toBe(false);
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

test.serial("does not dispatch tabs:change on connect", async () => {
    await mount();

    let fired = false;
    mounted.root.addEventListener("tabs:change", () => (fired = true));

    // connect is idempotent; re-running it must not emit the change event.
    mounted.controller.connect();
    await wait(0);

    expect(fired).toBe(false);
});

test.serial("vertical orientation navigates with ArrowDown and ArrowUp", async () => {
    await mount({ vertical: true });
    const tabs = tabEls();

    press(tabs[0], "ArrowDown");
    expect(tabs[1].getAttribute("aria-selected")).toBe("true");

    press(tabs[1], "ArrowUp");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
});

test.serial("vertical orientation ignores ArrowRight and ArrowLeft", async () => {
    await mount({ vertical: true });
    const tabs = tabEls();

    press(tabs[0], "ArrowRight");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");

    press(tabs[0], "ArrowLeft");
    expect(tabs[0].getAttribute("aria-selected")).toBe("true");
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

async function mount({ selectedAttr = null, selectedIndexValue = null, vertical = false, disabled = null } = {}) {
    const selected = (i) => (selectedAttr === i ? 'aria-selected="true"' : "");
    const disabledAttr = (i) => (disabled === i ? 'disabled aria-disabled="true"' : "");
    const valueAttr = selectedIndexValue === null ? "" : `data-tabs-selected-index-value="${selectedIndexValue}"`;
    const orientation = vertical ? 'aria-orientation="vertical"' : "";

    mounted = await mountController(
        "tabs",
        TabsController,
        `
        <div data-controller="tabs" ${valueAttr}>
            <div role="tablist" ${orientation}
                 data-action="click->tabs#select keydown->tabs#navigate">
                <button role="tab" id="t1" aria-controls="p1" data-tabs-target="tab" ${selected(0)} ${disabledAttr(0)}>One</button>
                <button role="tab" id="t2" aria-controls="p2" data-tabs-target="tab" ${selected(1)} ${disabledAttr(1)}>Two</button>
                <button role="tab" id="t3" aria-controls="p3" data-tabs-target="tab" ${selected(2)} ${disabledAttr(2)}>Three</button>
            </div>
            <div role="tabpanel" id="p1" data-tabs-target="panel" tabindex="0">P1</div>
            <div role="tabpanel" id="p2" data-tabs-target="panel" tabindex="0">P2</div>
            <div role="tabpanel" id="p3" data-tabs-target="panel" tabindex="0">P3</div>
        </div>`,
    );
}
