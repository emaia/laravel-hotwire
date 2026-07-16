import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ToggleController from "../../resources/js/controllers/toggle_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("syncs the initial pressed state on connect", async () => {
    await mount(`<button data-toggle-pressed-value="true" aria-pressed="false" data-state="off">Bold</button>`);

    expect(mounted.root.getAttribute("aria-pressed")).toBe("true");
    expect(mounted.root.dataset.state).toBe("on");
});

test.serial("toggles aria-pressed and data-state on click", async () => {
    await mount(`<button data-toggle-pressed-value="false" aria-pressed="false" data-state="off">Bold</button>`);

    dispatchEvent(mounted.root, "click");
    await wait(0);

    expect(mounted.root.getAttribute("aria-pressed")).toBe("true");
    expect(mounted.root.dataset.state).toBe("on");

    dispatchEvent(mounted.root, "click");
    await wait(0);

    expect(mounted.root.getAttribute("aria-pressed")).toBe("false");
    expect(mounted.root.dataset.state).toBe("off");
});

test.serial("syncs an associated hidden input", async () => {
    mounted = await mountController(
        "toggle",
        ToggleController,
        `<form>
            <input id="featured-input" data-toggle-input type="hidden" name="featured" value="featured" disabled>
            <button
                data-controller="toggle"
                data-action="click->toggle#toggle"
                data-toggle-input-id-value="featured-input"
                data-toggle-pressed-value="false"
                data-toggle-value-value="featured"
                aria-pressed="false"
                data-state="off"
            >Featured</button>
        </form>`,
    );

    const input = document.querySelector("input");
    expect(input.disabled).toBe(true);

    dispatchEvent(mounted.root, "click");
    await wait(0);

    expect(input.disabled).toBe(false);
    expect(input.value).toBe("featured");

    dispatchEvent(mounted.root, "click");
    await wait(0);

    expect(input.disabled).toBe(true);
});

test.serial("dispatches a bubbling change event after toggling", async () => {
    await mount(`<button data-toggle-pressed-value="false">Bold</button>`);

    const events = [];
    document.body.addEventListener("change", (event) => events.push(event));

    dispatchEvent(mounted.root, "click");
    await wait(0);

    expect(events).toHaveLength(1);
    expect(events[0].target).toBe(mounted.root);
    expect(events[0].detail).toEqual({ pressed: true, value: "on" });
});

test.serial("does not toggle when disabled", async () => {
    await mount(`<button data-toggle-pressed-value="false" disabled>Bold</button>`);

    dispatchEvent(mounted.root, "click");
    await wait(0);

    expect(mounted.root.getAttribute("aria-pressed")).toBe("false");
    expect(mounted.root.dataset.state).toBe("off");
});

async function mount(html) {
    mounted = await mountController(
        "toggle",
        ToggleController,
        html.replace("<button", '<button data-controller="toggle" data-action="click->toggle#toggle"'),
    );
}
