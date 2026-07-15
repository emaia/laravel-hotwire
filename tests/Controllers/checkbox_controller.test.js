import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import CheckboxController from "../../resources/js/controllers/checkbox_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("sets indeterminate property on connect", async () => {
    await mount(`<input type="checkbox" data-checkbox-indeterminate-value="true" />`);

    const checkbox = document.querySelector("input");
    expect(checkbox.indeterminate).toBe(true);
});

test.serial("clears indeterminate property when the value is false", async () => {
    await mount(`<input type="checkbox" data-checkbox-indeterminate-value="false" />`);

    const checkbox = document.querySelector("input");
    checkbox.indeterminate = true;
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(checkbox.indeterminate).toBe(false);
});

async function mount(html) {
    mounted = await mountController(
        "checkbox",
        CheckboxController,
        html.replace("<input", '<input data-controller="checkbox"'),
    );
}
