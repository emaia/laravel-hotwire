import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import AutoSubmitController from "../../resources/js/controllers/auto_submit_controller.js";
import ToggleController from "../../resources/js/controllers/toggle_controller.js";
import ToggleGroupController from "../../resources/js/controllers/toggle_group_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("single groups keep only one item pressed", async () => {
    await mount("single");

    const [left, center] = items();
    const [leftInput, centerInput] = inputs();

    dispatchEvent(left, "click");
    await wait(0);

    expect(left.getAttribute("aria-pressed")).toBe("true");
    expect(leftInput.disabled).toBe(false);

    dispatchEvent(center, "click");
    await wait(0);

    expect(left.getAttribute("aria-pressed")).toBe("false");
    expect(left.dataset.state).toBe("off");
    expect(leftInput.disabled).toBe(true);
    expect(center.getAttribute("aria-pressed")).toBe("true");
    expect(centerInput.disabled).toBe(false);
});

test.serial("single groups allow the selected item to be cleared", async () => {
    await mount("single");

    const [left] = items();
    const [leftInput] = inputs();

    dispatchEvent(left, "click");
    await wait(0);
    dispatchEvent(left, "click");
    await wait(0);

    expect(left.getAttribute("aria-pressed")).toBe("false");
    expect(left.dataset.state).toBe("off");
    expect(leftInput.disabled).toBe(true);
});

test.serial("multiple groups keep independent pressed items", async () => {
    await mount("multiple");

    const [left, center] = items();
    const [leftInput, centerInput] = inputs();

    dispatchEvent(left, "click");
    await wait(0);
    dispatchEvent(center, "click");
    await wait(0);

    expect(left.getAttribute("aria-pressed")).toBe("true");
    expect(center.getAttribute("aria-pressed")).toBe("true");
    expect(leftInput.disabled).toBe(false);
    expect(centerInput.disabled).toBe(false);
});

test.serial("single groups normalize duplicate pressed items on connect", async () => {
    mounted = await mountMultipleControllers(
        { "toggle-group": ToggleGroupController, toggle: ToggleController },
        groupHtml("single", { leftPressed: true, centerPressed: true }),
    );

    const [left, center] = items();
    const [leftInput, centerInput] = inputs();

    expect(left.getAttribute("aria-pressed")).toBe("true");
    expect(leftInput.disabled).toBe(false);
    expect(center.getAttribute("aria-pressed")).toBe("false");
    expect(centerInput.disabled).toBe(true);
});

test.serial("disabled items are not changed by the group", async () => {
    await mount("single", { leftDisabled: true });

    const [left] = items();
    const [leftInput] = inputs();

    dispatchEvent(left, "click");
    await wait(0);

    expect(left.getAttribute("aria-pressed")).toBe("false");
    expect(leftInput.disabled).toBe(true);
});

test.serial("auto-submit runs from an ancestor form after the group syncs inputs", async () => {
    mounted = await mountMultipleControllers(
        { "auto-submit": AutoSubmitController, "toggle-group": ToggleGroupController, toggle: ToggleController },
        `<form data-controller="auto-submit">
            <div
                data-controller="toggle-group"
                data-action="change->toggle-group#sync change->auto-submit#submit"
                data-toggle-group-type-value="single"
            >
                ${toggleItem("left", false)}
            </div>
        </form>`,
    );

    let submits = 0;
    const form = document.querySelector("form");
    form.requestSubmit = () => submits++;

    const [left] = items();
    const [leftInput] = inputs();

    dispatchEvent(left, "click");
    await wait(0);

    expect(leftInput.disabled).toBe(false);
    expect(submits).toBe(1);
});

async function mount(type, options = {}) {
    mounted = await mountMultipleControllers(
        { "toggle-group": ToggleGroupController, toggle: ToggleController },
        groupHtml(type, options),
    );
}

function groupHtml(type, options = {}) {
    const {
        leftPressed = false,
        centerPressed = false,
        leftDisabled = false,
    } = options;

    return `<form>
        <div
            data-controller="toggle-group"
            data-action="change->toggle-group#sync"
            data-toggle-group-type-value="${type}"
        >
            ${toggleItem("left", leftPressed, leftDisabled)}
            ${toggleItem("center", centerPressed)}
        </div>
    </form>`;
}

function toggleItem(value, pressed, disabled = false) {
    return `<input id="${value}-input" data-toggle-input type="hidden" name="alignment" value="${value}" ${pressed && !disabled ? "" : "disabled"}>
        <button
            type="button"
            data-controller="toggle"
            data-action="click->toggle#toggle"
            data-toggle-group-target="item"
            data-toggle-input-id-value="${value}-input"
            data-toggle-pressed-value="${pressed ? "true" : "false"}"
            data-toggle-value-value="${value}"
            aria-pressed="${pressed ? "true" : "false"}"
            data-state="${pressed ? "on" : "off"}"
            ${disabled ? "disabled" : ""}
        >${value}</button>`;
}

function items() {
    return [...document.querySelectorAll("[data-toggle-group-target~='item']")];
}

function inputs() {
    return [...document.querySelectorAll("[data-toggle-input]")];
}
