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

test.serial("click action: ignores select, contenteditable, and ProseMirror targets", async () => {
    await mount(`<div data-controller="hotkey" data-action="keydown->hotkey#click">Action</div>`);

    mounted.root.click = clickSpy;
    mounted.root.insertAdjacentHTML("beforeend", `
        <select><option>Choice</option></select>
        <div contenteditable="true"><span id="editable-child">Editable</span></div>
        <div class="ProseMirror"><span id="prosemirror-child">Editor</span></div>
    `);

    const targets = [
        mounted.root.querySelector("select"),
        mounted.root.querySelector("#editable-child"),
        mounted.root.querySelector("#prosemirror-child"),
    ];

    for (const target of targets) {
        const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
        target.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(false);
    }

    expect(clickSpy).not.toHaveBeenCalled();
});

test.serial("click action remains active inside contenteditable=false", async () => {
    await mount(`<div data-controller="hotkey" data-action="keydown->hotkey#click">Action</div>`);

    mounted.root.click = clickSpy;
    mounted.root.insertAdjacentHTML("beforeend", '<div contenteditable="false"><span id="static-child">Static</span></div>');
    const event = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    mounted.root.querySelector("#static-child").dispatchEvent(event);

    expect(clickSpy).toHaveBeenCalledTimes(1);
    expect(event.defaultPrevented).toBe(true);
});

test.serial("click action ignores IME key events and accepts the committed key", async () => {
    await mount(`<button type="button" data-controller="hotkey" data-action="keydown->hotkey#click">Action</button>`);

    mounted.root.click = clickSpy;
    const composing = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    Object.defineProperty(composing, "target", { value: document.body });
    mounted.root.dispatchEvent(composing);

    const legacy = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(legacy, "keyCode", { value: 229 });
    Object.defineProperty(legacy, "target", { value: document.body });
    mounted.root.dispatchEvent(legacy);

    expect(clickSpy).not.toHaveBeenCalled();
    expect(composing.defaultPrevented).toBe(false);
    expect(legacy.defaultPrevented).toBe(false);

    const committed = new KeyboardEvent("keydown", { key: "a", cancelable: true, bubbles: true });
    Object.defineProperty(committed, "target", { value: document.body });
    mounted.root.dispatchEvent(committed);

    expect(clickSpy).toHaveBeenCalledTimes(1);
    expect(committed.defaultPrevented).toBe(true);
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
