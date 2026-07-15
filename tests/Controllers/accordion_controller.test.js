import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import AccordionController from "../../resources/js/controllers/accordion_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("single accordions close sibling items when one opens", async () => {
    await mount();
    const [first, second] = items();

    first.open = true;
    first.dispatchEvent(new Event("toggle", { bubbles: false }));
    await wait(0);

    expect(first.open).toBe(true);
    expect(second.open).toBe(false);

    second.open = true;
    second.dispatchEvent(new Event("toggle", { bubbles: false }));
    await wait(0);

    expect(first.open).toBe(false);
    expect(second.open).toBe(true);
});

test.serial("multiple accordions keep siblings open", async () => {
    await mount({ type: "multiple" });
    const [first, second] = items();

    first.open = true;
    first.dispatchEvent(new Event("toggle", { bubbles: false }));
    second.open = true;
    second.dispatchEvent(new Event("toggle", { bubbles: false }));
    await wait(0);

    expect(first.open).toBe(true);
    expect(second.open).toBe(true);
});

test.serial("disabled items cannot be toggled open", async () => {
    await mount({ disabled: true });
    const disabled = items()[1];
    const summary = disabled.querySelector("summary");
    const click = new MouseEvent("click", { bubbles: true, cancelable: true });

    summary.dispatchEvent(click);
    disabled.open = true;
    disabled.dispatchEvent(new Event("toggle", { bubbles: false }));
    await wait(0);

    expect(click.defaultPrevented).toBe(true);
    expect(disabled.open).toBe(false);
});

test.serial("dispatches accordion:change with item value and open state", async () => {
    await mount();
    const [first] = items();
    let detail = null;

    mounted.root.addEventListener("accordion:change", (event) => (detail = event.detail));

    first.open = true;
    first.dispatchEvent(new Event("toggle", { bubbles: false }));
    await wait(0);

    expect(detail.value).toBe("shipping");
    expect(detail.open).toBe(true);
    expect(detail.item).toBe(first);
});

test.serial("initial value opens matching items on connect", async () => {
    await mount({ value: ["billing"] });

    expect(items()[0].open).toBe(false);
    expect(items()[1].open).toBe(true);
});

function items() {
    return [...document.querySelectorAll("details")];
}

async function mount({ type = "single", value = null, disabled = false } = {}) {
    const valueAttr = value === null ? "" : `data-accordion-value-value='${JSON.stringify(value)}'`;
    const disabledAttr = disabled ? 'aria-disabled="true"' : "";

    mounted = await mountController(
        "accordion",
        AccordionController,
        `
        <section data-controller="accordion"
                 data-accordion-type-value="${type}"
                 ${valueAttr}>
            <details data-accordion-target="item" data-value="shipping">
                <summary>Shipping</summary>
                <section>Shipping answers.</section>
            </details>
            <details data-accordion-target="item" data-value="billing" ${disabledAttr}>
                <summary>Billing</summary>
                <section>Billing answers.</section>
            </details>
        </section>`,
    );
}
