import { afterEach, beforeEach, expect, mock, test } from "bun:test";

const visitCalls = [];
const visitFn = mock((url, options) => visitCalls.push({ url, options }));

mock.module("@hotwired/turbo", () => ({ visit: visitFn, session: {} }));

const { mountController, wait } = await import("../../../resources/js/helpers/test_stimulus.js");
const { default: PollingController } = await import(
    "../../../resources/js/controllers/turbo/polling_controller.js"
);

let mounted;
let originalError;

beforeEach(() => {
    visitCalls.length = 0;
    visitFn.mockClear();
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    if (originalError) {
        console.error = originalError;
        originalError = null;
    }
});

// --- scheduling ---

test.serial("calls Turbo.visit with the frame value after the timeout fires", async () => {
    await mount({ timeout: 20 });

    await wait(40);

    expect(visitFn).toHaveBeenCalledTimes(1);
    expect(visitCalls[0].options.frame).toBe("posts");
    expect(visitCalls[0].options.action).toBe("replace");
});

test.serial("does not fire when frame value is empty", async () => {
    await mount({ timeout: 20, frame: "" });

    await wait(40);

    expect(visitFn).not.toHaveBeenCalled();
});

test.serial("does not fire when enabled is false", async () => {
    await mount({ timeout: 20, enabled: false });

    await wait(40);

    expect(visitFn).not.toHaveBeenCalled();
});

// --- value reactivity ---

test.serial("toggling enabled to false cancels the pending timer", async () => {
    await mount({ timeout: 50 });

    mounted.controller.enabledValue = false;

    await wait(80);

    expect(visitFn).not.toHaveBeenCalled();
});

test.serial("toggling enabled to true restarts the scheduler", async () => {
    await mount({ timeout: 50, enabled: false });

    mounted.controller.enabledValue = true;
    await wait(80);

    expect(visitFn).toHaveBeenCalledTimes(1);
});

test.serial("changing timeoutValue while enabled reschedules with the new delay", async () => {
    await mount({ timeout: 1000 });

    mounted.controller.timeoutValue = 20;
    await wait(40);

    expect(visitFn).toHaveBeenCalledTimes(1);
});

// --- imperative refresh action ---

test.serial("refresh() visits immediately and reschedules", async () => {
    await mount({ timeout: 1000 });

    mounted.controller.refresh();

    expect(visitFn).toHaveBeenCalledTimes(1);
});

// --- disconnect cleanup ---

test.serial("disconnect cancels the pending timer", async () => {
    await mount({ timeout: 30 });

    mounted.controller.disconnect();
    await wait(60);

    expect(visitFn).not.toHaveBeenCalled();
});

// --- error handling ---

test.serial("logs and reschedules when Turbo.visit throws", async () => {
    originalError = console.error;
    console.error = mock(() => {});
    visitFn.mockImplementationOnce(() => {
        throw new Error("boom");
    });

    await mount({ timeout: 20 });
    await wait(40); // first fire throws → reschedule
    await wait(40); // second fire succeeds

    expect(visitFn).toHaveBeenCalledTimes(2);
});

async function mount({ timeout = 100, frame = "posts", enabled = true } = {}) {
    const attrs = [
        `data-turbo--polling-frame-value="${frame}"`,
        `data-turbo--polling-timeout-value="${timeout}"`,
        `data-turbo--polling-enabled-value="${enabled}"`,
    ].join(" ");
    mounted = await mountController(
        "turbo--polling",
        PollingController,
        `<div data-controller="turbo--polling" ${attrs}></div>`,
    );
}
