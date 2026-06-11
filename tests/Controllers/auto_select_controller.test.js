import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController } from "../../resources/js/helpers/test_stimulus.js";
import AutoSelectController from "../../resources/js/controllers/auto_select_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("selects content on focus", async () => {
    await mount(`<input type="text" data-controller="auto-select" value="hello world" />`);

    const input = mounted.root;
    let selectCalled = false;
    input.select = () => { selectCalled = true; };

    dispatchEvent(input, "focus");
    expect(selectCalled).toBe(true);
});

test.serial("selects textarea content on focus", async () => {
    await mount(`<textarea data-controller="auto-select">hello world</textarea>`);

    const input = mounted.root;
    let selectCalled = false;
    input.select = () => { selectCalled = true; };

    dispatchEvent(input, "focus");
    expect(selectCalled).toBe(true);
});

test.serial("disconnect removes focus listener", async () => {
    await mount(`<input type="text" data-controller="auto-select" value="hello world" />`);

    const input = mounted.root;

    mounted.controller.disconnect();

    let selectCalled = false;
    input.select = () => { selectCalled = true; };

    dispatchEvent(input, "focus");
    expect(selectCalled).toBe(false);
});

async function mount(html) {
    mounted = await mountController("auto-select", AutoSelectController, html);
}
