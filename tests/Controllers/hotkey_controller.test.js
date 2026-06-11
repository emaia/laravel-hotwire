import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import HotkeyController from "../../resources/js/controllers/hotkey_controller.js";

let mounted;
let clickSpy;
let focusSpy;

beforeEach(() => {
    clickSpy = mock(() => {});
    focusSpy = mock(() => {});
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- click action ---

test.serial("click action prevents default and clicks the element", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: document.body });
    mounted.root.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(clickSpy).toHaveBeenCalled();
});

test.serial("click action: ignores when pressed inside input", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;

    const input = document.createElement("input");
    mounted.root.appendChild(input);

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: input });
    mounted.root.dispatchEvent(event);

    expect(clickSpy).not.toHaveBeenCalled();
    expect(event.defaultPrevented).toBe(false);
});

test.serial("click action: ignores when pressed inside textarea", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;

    const textarea = document.createElement("textarea");
    mounted.root.appendChild(textarea);

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: textarea });
    mounted.root.dispatchEvent(event);

    expect(clickSpy).not.toHaveBeenCalled();
});

test.serial("click action: ignores when pressed inside lexxy-editor", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;

    const editor = document.createElement("lexxy-editor");
    mounted.root.appendChild(editor);

    const input = document.createElement("input");
    editor.appendChild(input);

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: input });
    mounted.root.dispatchEvent(event);

    expect(clickSpy).not.toHaveBeenCalled();
});

test.serial("click action: ignores when pointerEvents is none", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    // Override getComputedStyle on the element's owner window after mount
    const originalGCS = globalThis.getComputedStyle;
    globalThis.getComputedStyle = mock(() => ({ pointerEvents: "none" }));

    mounted.root.click = clickSpy;

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: document.body });
    mounted.root.dispatchEvent(event);

    expect(clickSpy).not.toHaveBeenCalled();

    globalThis.getComputedStyle = originalGCS;
});

test.serial("click action: ignores when event is defaultPrevented", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });

    // Wrap in a capturing listener to preventDefault before hotkey runs
    const captureHandler = (e) => e.preventDefault();
    document.addEventListener("keydown", captureHandler, true);

    Object.defineProperty(event, "target", { value: document.body });
    mounted.root.dispatchEvent(event);

    document.removeEventListener("keydown", captureHandler, true);

    expect(clickSpy).not.toHaveBeenCalled();
});

// --- focus action ---

test.serial("focus action: prevents default and focuses the element", async () => {
    await mount(`<input type="text" data-controller="hotkey" data-action="keydown->hotkey#focus" />`);

    mounted.root.focus = focusSpy;

    const event = new KeyboardEvent("keydown", { key: "f", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: document.body });
    mounted.root.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(focusSpy).toHaveBeenCalled();
});

test.serial("focus action: ignores when pressed inside input", async () => {
    await mount(`<input type="text" data-controller="hotkey" data-action="keydown->hotkey#focus" />`);

    mounted.root.focus = focusSpy;

    const nestedInput = document.createElement("input");
    mounted.root.appendChild(nestedInput);

    const event = new KeyboardEvent("keydown", { key: "f", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: nestedInput });
    mounted.root.dispatchEvent(event);

    expect(focusSpy).not.toHaveBeenCalled();
});

test.serial("focus action: ignores when pointerEvents is none", async () => {
    await mount(`<input type="text" data-controller="hotkey" data-action="keydown->hotkey#focus" />`);

    const originalGCS = globalThis.getComputedStyle;
    globalThis.getComputedStyle = mock(() => ({ pointerEvents: "none" }));

    mounted.root.focus = focusSpy;

    const event = new KeyboardEvent("keydown", { key: "f", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: document.body });
    mounted.root.dispatchEvent(event);

    expect(focusSpy).not.toHaveBeenCalled();

    globalThis.getComputedStyle = originalGCS;
});

test.serial("focus action: ignores when event is defaultPrevented", async () => {
    await mount(`<input type="text" data-controller="hotkey" data-action="keydown->hotkey#focus" />`);

    mounted.root.focus = focusSpy;

    const event = new KeyboardEvent("keydown", { key: "f", cancelable: true, bubbles: true });

    const captureHandler = (e) => e.preventDefault();
    document.addEventListener("keydown", captureHandler, true);

    Object.defineProperty(event, "target", { value: document.body });
    mounted.root.dispatchEvent(event);

    document.removeEventListener("keydown", captureHandler, true);

    expect(focusSpy).not.toHaveBeenCalled();
});

// --- target edge cases ---

test.serial("shouldIgnore returns true when target is null (no element)", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;

    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(event, "target", { value: null });
    mounted.root.dispatchEvent(event);

    // target is null -> shouldIgnore returns false -> click fires
    expect(clickSpy).toHaveBeenCalled();
});

async function mount(html) {
    mounted = await mountController("hotkey", HotkeyController, html);
}
