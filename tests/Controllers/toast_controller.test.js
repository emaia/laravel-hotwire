import { afterEach, beforeEach, expect, test } from "bun:test";

const calls = [];

function record(type) {
    return (message, options) => {
        calls.push({ type, message, options });
    };
}

const toast = Object.assign(record("default"), {
    success: record("success"),
    error: record("error"),
    warning: record("warning"),
    info: record("info"),
});

const { mountController } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: ToastController } = await import(
    "../../resources/js/controllers/toast_controller.js"
);

class TestToastController extends ToastController {
    get toast() {
        return toast;
    }
}

let mounted;

beforeEach(() => {
    calls.length = 0;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("passes position to toast when set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Heads up"
            data-toast-type-value="warning"
            data-toast-position-value="top-center"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].type).toBe("warning");
    expect(calls[0].options.position).toBe("top-center");
});

test.serial("omits position from options when not set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].options.position).toBeUndefined();
});

test.serial("omits position when value is empty string", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
            data-toast-position-value=""
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].options.position).toBeUndefined();
});

test.serial("passes className to toast when set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Done"
            data-toast-type-value="success"
            data-toast-class-name-value="custom-toast"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].type).toBe("success");
    expect(calls[0].options.className).toBe("custom-toast");
});

test.serial("omits className from options when not set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].options.className).toBeUndefined();
});

async function mount(html) {
    mounted = await mountController("toast", TestToastController, html);
}
