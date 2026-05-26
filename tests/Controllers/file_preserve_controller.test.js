import { afterEach, expect, test } from "bun:test";

import { mountController, mountControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import FilePreserveController from "../../resources/js/controllers/file_preserve_controller.js";

let mounted;

function mockFile(name = "test.pdf") {
    return new File(["content"], name, { type: "application/pdf" });
}

function setFileOnInput(input, file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    try {
        input.files = dt.files;
    } catch {
        let captured = [file];
        Object.defineProperty(input, "files", {
            get() { return { length: captured.length, [0]: captured[0], [Symbol.iterator]: () => captured[Symbol.iterator]() }; },
            set(v) { captured = Array.from(v); },
            configurable: true,
        });
    }
}

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- Validation error: restore ---

test.serial("restores file when form has errors after submit", async () => {
    await mount(`
        <div data-controller="file-preserve">
            <input type="file" name="avatar" />
        </div>
    `);

    const root = document.querySelector("[data-controller='file-preserve']");
    const input = root.querySelector("input[type='file']");
    setFileOnInput(input, mockFile());

    dispatchTurboSubmitEnd(document.body);

    document.dispatchEvent(new Event("turbo:before-render", { bubbles: true }));

    const newInput = document.createElement("input");
    newInput.setAttribute("type", "file");
    newInput.setAttribute("name", "avatar");
    root.innerHTML = "";
    root.appendChild(newInput);
    // Mark form as having errors
    const form = document.createElement("form");
    document.body.appendChild(form);
    form.appendChild(root);
    const indicator = document.createElement("span");
    indicator.setAttribute("aria-invalid", "true");
    form.appendChild(indicator);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(newInput.files.length).toBe(1);
});

// --- Successful submit: don't restore ---

test.serial("does not restore when form has no errors after submit", async () => {
    await mount(`
        <div data-controller="file-preserve">
            <input type="file" name="avatar" />
        </div>
    `);

    const root = document.querySelector("[data-controller='file-preserve']");
    const input = root.querySelector("input[type='file']");
    setFileOnInput(input, mockFile());

    dispatchTurboSubmitEnd(document.body);

    document.dispatchEvent(new Event("turbo:before-render", { bubbles: true }));

    const newInput = document.createElement("input");
    newInput.setAttribute("type", "file");
    newInput.setAttribute("name", "avatar");
    root.innerHTML = "";
    root.appendChild(newInput);
    // Form without errors
    const form = document.createElement("form");
    document.body.appendChild(form);
    form.appendChild(root);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(newInput.files.length).toBe(0);
});

// --- No submit: don't restore ---

test.serial("does not restore when no submit happened", async () => {
    await mount(`
        <div data-controller="file-preserve">
            <input type="file" name="avatar" />
        </div>
    `);

    const root = document.querySelector("[data-controller='file-preserve']");
    const input = root.querySelector("input[type='file']");
    setFileOnInput(input, mockFile());

    document.dispatchEvent(new Event("turbo:before-render", { bubbles: true }));

    const newInput = document.createElement("input");
    newInput.setAttribute("type", "file");
    newInput.setAttribute("name", "avatar");
    root.innerHTML = "";
    root.appendChild(newInput);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(newInput.files.length).toBe(0);
});

// --- Disconnect restores via new instance ---

test.serial("new instance restores after disconnect with errors", async () => {
    const { mounted: m1, controller: c1, root: root1 } = await mountRaw(`
        <div data-controller="file-preserve">
            <input type="file" name="avatar" />
        </div>
    `);

    const input1 = root1.querySelector("input[type='file']");
    setFileOnInput(input1, mockFile());

    dispatchTurboSubmitEnd(document.body);
    c1.disconnect();
    await m1.cleanup();

    const { mounted: m2, root: root2 } = await mountRaw(`
        <div data-controller="file-preserve">
            <input type="file" name="avatar" />
        </div>
    `);

    const input2 = root2.querySelector("input[type='file']");
    const form = document.createElement("form");
    document.body.appendChild(form);
    form.appendChild(root2);
    const indicator = document.createElement("span");
    indicator.setAttribute("aria-invalid", "true");
    form.appendChild(indicator);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(input2.files.length).toBe(1);

    await m2.cleanup();
});

// --- Multiple file fields on the same page ---

test.serial("restores every file field, not just the first", async () => {
    mounted = await mountControllers("file-preserve", FilePreserveController, `
        <form>
            <div data-controller="file-preserve"><input type="file" name="avatar" /></div>
            <div data-controller="file-preserve"><input type="file" name="document" /></div>
        </form>
    `);

    const [rootA, rootB] = mounted.roots;
    const inputA = rootA.querySelector("input[type='file']");
    const inputB = rootB.querySelector("input[type='file']");
    setFileOnInput(inputA, mockFile("a.pdf"));
    setFileOnInput(inputB, mockFile("b.pdf"));

    const form = document.querySelector("form");
    dispatchTurboSubmitEnd(form);
    document.dispatchEvent(new Event("turbo:before-render", { bubbles: true }));

    // Re-render both fields with a validation error on the form
    const newA = makeFileInput("avatar");
    const newB = makeFileInput("document");
    rootA.innerHTML = "";
    rootA.appendChild(newA);
    rootB.innerHTML = "";
    rootB.appendChild(newB);
    const indicator = document.createElement("span");
    indicator.setAttribute("aria-invalid", "true");
    form.appendChild(indicator);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(newA.files.length).toBe(1);
    expect(newB.files.length).toBe(1);
});

// --- Submit of an unrelated form must not arm capture ---

test.serial("ignores submit from a form that does not contain the field", async () => {
    await mount(`
        <form id="mine">
            <div data-controller="file-preserve"><input type="file" name="avatar" /></div>
        </form>
    `);

    const root = document.querySelector("[data-controller='file-preserve']");
    const input = root.querySelector("input[type='file']");
    setFileOnInput(input, mockFile());

    // A different form elsewhere submits
    const other = document.createElement("form");
    document.body.appendChild(other);
    dispatchTurboSubmitEnd(other);

    document.dispatchEvent(new Event("turbo:before-render", { bubbles: true }));
    const newInput = makeFileInput("avatar");
    root.innerHTML = "";
    root.appendChild(newInput);
    const indicator = document.createElement("span");
    indicator.setAttribute("aria-invalid", "true");
    document.querySelector("#mine").appendChild(indicator);

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(newInput.files.length).toBe(0);
});

// --- Cleanup ---

test.serial("removes listeners on disconnect", async () => {
    const { mounted: m, controller, root } = await mountRaw(`
        <div data-controller="file-preserve">
            <input type="file" name="avatar" />
        </div>
    `);

    const input = root.querySelector("input[type='file']");
    setFileOnInput(input, mockFile());

    dispatchTurboSubmitEnd(document.body);
    controller.disconnect();

    document.dispatchEvent(new Event("turbo:before-render", { bubbles: true }));
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(input.files.length).toBe(1);

    await m.cleanup();
});

// --- Helpers ---

async function mount(html) {
    mounted = await mountController("file-preserve", FilePreserveController, html);
}

async function mountRaw(html) {
    const result = await mountController("file-preserve", FilePreserveController, html);
    return { mounted: result, controller: result.controller, root: result.root };
}

function dispatchTurboSubmitEnd(target) {
    target.dispatchEvent(
        new CustomEvent("turbo:submit-end", { bubbles: true, detail: {} })
    );
}

function makeFileInput(name) {
    const input = document.createElement("input");
    input.setAttribute("type", "file");
    input.setAttribute("name", name);
    return input;
}
