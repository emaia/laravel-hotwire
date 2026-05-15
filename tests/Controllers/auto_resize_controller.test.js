import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import AutoResizeController from "../../resources/js/controllers/auto_resize_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("applies overflow:hidden and sizes the textarea on connect", async () => {
    await mount(`<textarea data-controller="auto-resize">hello</textarea>`);

    const textarea = document.querySelector("textarea");
    expect(textarea.style.overflow).toBe("hidden");
    expect(textarea.style.height).toMatch(/^\d+px$/);
});

test.serial("re-applies overflow and height after turbo:render (morph scenario)", async () => {
    await mount(`<textarea data-controller="auto-resize">hello</textarea>`);

    const textarea = document.querySelector("textarea");

    // Simulate Turbo morph: idiomorph wipes the inline style attribute.
    textarea.removeAttribute("style");
    textarea.value = "morphed content";

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(0);

    expect(textarea.style.overflow).toBe("hidden");
    expect(textarea.style.height).toMatch(/^\d+px$/);
});

async function mount(html) {
    mounted = await mountController("auto-resize", AutoResizeController, html);
}
