import { afterEach, beforeEach, expect, test } from "bun:test";

const calls = [];

const { mountController } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: ToastController } = await import(
    "../../resources/js/controllers/toast_controller.js"
);

// The controller hands a payload to the toast manager; the manager itself is covered in
// _toaster.test.js. Recording the payload keeps these tests about what the component promises.
class TestToastController extends ToastController {
    emit(payload) {
        calls.push(payload);

        return payload.id ?? null;
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

// --- Contract ---
//
// What survives the native port: one emission per connect, the trigger element leaving the DOM,
// and the shape of the payload the controller hands to whatever renders the toast. The renderer
// itself is not contract.

test.serial("emits exactly one toast per connect", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].message).toBe("Saved");
});

test.serial("removes the trigger element from the DOM after emitting", async () => {
    await mount(`
        <div
            id="trigger"
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(mounted.document.getElementById("trigger")).toBeNull();
    expect(mounted.root.isConnected).toBe(false);
});

test.serial("emits the default type when none is given", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Just so you know"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].type).toBe("default");
});

test.serial("emits each named type", async () => {
    for (const type of ["success", "error", "warning", "info"]) {
        await mount(`
            <div
                data-controller="toast"
                data-toast-message-value="Message"
                data-toast-type-value="${type}"
            ></div>
        `);

        expect(calls.at(-1).type).toBe(type);
        await mounted.cleanup();
        mounted = null;
    }

    expect(calls).toHaveLength(4);
});

test.serial("passes description when set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
            data-toast-description-value="Record updated"
        ></div>
    `);

    expect(calls[0].description).toBe("Record updated");
});

test.serial("omits description when not set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(calls[0].description).toBeUndefined();
});

// --- Optional payload fields ---

test.serial("passes position when set", async () => {
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
    expect(calls[0].position).toBe("top-center");
});

test.serial("omits position when not set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].position).toBeUndefined();
});

test.serial("omits position when the value is an empty string", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
            data-toast-position-value=""
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].position).toBeUndefined();
});

test.serial("passes className when set", async () => {
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
    expect(calls[0].className).toBe("custom-toast");
});

test.serial("omits className when not set", async () => {
    await mount(`
        <div
            data-controller="toast"
            data-toast-message-value="Saved"
            data-toast-type-value="success"
        ></div>
    `);

    expect(calls).toHaveLength(1);
    expect(calls[0].className).toBeUndefined();
});

async function mount(html) {
    mounted = await mountController("toast", TestToastController, html);
}
