import { afterEach, beforeEach, expect, mock, test } from "bun:test";

const visitCalls = [];
const visitFn = mock((url, options) => visitCalls.push({ url, options }));

mock.module("@hotwired/turbo", () => ({ visit: visitFn, session: {} }));

const { mountController } = await import("../../../resources/js/helpers/test_stimulus.js");
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

test.serial("calls Turbo.visit with the frame value after the scheduled timer fires", async () => {
    await mount({ timeout: 20, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    scheduler.runNext();

    expect(visitFn).toHaveBeenCalledTimes(1);
    expect(visitCalls[0].options.frame).toBe("posts");
    expect(visitCalls[0].options.action).toBe("replace");
});

test.serial("does not fire when frame value is empty", async () => {
    await mount({ timeout: 20, frame: "", enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);

    expect(visitFn).not.toHaveBeenCalled();
    expect(scheduler.pending()).toHaveLength(0);
});

test.serial("does not fire when enabled is false", async () => {
    await mount({ timeout: 20, enabled: false });
    const scheduler = installFakeScheduler();

    mounted.controller.scheduleRefresh();

    expect(visitFn).not.toHaveBeenCalled();
    expect(scheduler.pending()).toHaveLength(0);
});

// --- value reactivity ---

test.serial("toggling enabled to false cancels the pending timer", async () => {
    await mount({ timeout: 50, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    const pendingTimer = scheduler.pending()[0];

    setEnabled(false);

    expect(visitFn).not.toHaveBeenCalled();
    expect(pendingTimer.cancelled).toBe(true);
    expect(scheduler.pending()).toHaveLength(0);
});

test.serial("toggling enabled to true restarts the scheduler", async () => {
    await mount({ timeout: 50, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    scheduler.runNext();

    expect(visitFn).toHaveBeenCalledTimes(1);
});

test.serial("changing timeoutValue while enabled reschedules with the new delay", async () => {
    await mount({ timeout: 1000, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    const firstTimer = scheduler.pending()[0];
    setTimeoutValue(20);
    const secondTimer = scheduler.pending()[0];

    expect(firstTimer.cancelled).toBe(true);
    expect(secondTimer.delay).toBe(20);
    scheduler.runNext();

    expect(visitFn).toHaveBeenCalledTimes(1);
});

// --- imperative refresh action ---

test.serial("refresh() visits immediately and reschedules", async () => {
    await mount({ timeout: 1000, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    scheduler.clear();

    mounted.controller.refresh();

    expect(visitFn).toHaveBeenCalledTimes(1);
    expect(scheduler.pending()).toHaveLength(1);
});

// --- disconnect cleanup ---

test.serial("disconnect cancels the pending timer", async () => {
    await mount({ timeout: 30, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    const pendingTimer = scheduler.pending()[0];

    mounted.controller.disconnect();

    expect(visitFn).not.toHaveBeenCalled();
    expect(pendingTimer.cancelled).toBe(true);
    expect(scheduler.pending()).toHaveLength(0);
});

// --- error handling ---

test.serial("logs and reschedules when Turbo.visit throws", async () => {
    originalError = console.error;
    console.error = mock(() => {});
    visitFn.mockImplementationOnce(() => {
        throw new Error("boom");
    });

    await mount({ timeout: 20, enabled: false });
    const scheduler = installFakeScheduler();

    setEnabled(true);
    scheduler.runNext(); // first fire throws -> reschedule
    scheduler.runNext(); // second fire succeeds

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

function installFakeScheduler() {
    const timers = [];

    mounted.controller.setRefreshTimer = (callback, delay) => {
        const timer = { callback, delay, cancelled: false };
        timers.push(timer);

        return timer;
    };

    mounted.controller.clearRefreshTimer = (timer) => {
        timer.cancelled = true;
    };

    return {
        pending() {
            return timers.filter((timer) => !timer.cancelled);
        },
        runNext() {
            const timer = this.pending()[0];

            if (!timer) {
                throw new Error("Expected a pending polling timer.");
            }

            timer.cancelled = true;
            timer.callback();
        },
        clear() {
            timers.length = 0;
        },
    };
}

function setEnabled(enabled) {
    mounted.controller.enabledValue = enabled;
    mounted.controller.enabledValueChanged();
}

function setTimeoutValue(timeout) {
    mounted.controller.timeoutValue = timeout;
    mounted.controller.timeoutValueChanged();
}
