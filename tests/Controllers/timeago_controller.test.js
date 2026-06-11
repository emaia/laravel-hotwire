import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import TimeagoController from "../../resources/js/controllers/timeago_controller.js";

// --- date-fns mock ---
// The controller imports { formatDistanceToNow } from "date-fns".
// We mock it to control the output and avoid "Invalid time value" errors from non-deterministic dates.

let formatDistanceToNowCalls = [];
const formatDistanceToNowMock = mock((date, options) => {
    formatDistanceToNowCalls.push({ date, options });
    return "5 minutes ago";
});

mock.module("date-fns", () => ({
    formatDistanceToNow: formatDistanceToNowMock,
}));

// --- Fake timers ---
// We use real timers but control setInterval / clearInterval to test refresh logic.

let intervalCallbacks = [];
let intervalIdCounter = 0;
const originalSetInterval = globalThis.setInterval;
const originalClearInterval = globalThis.clearInterval;

let mounted;

beforeEach(() => {
    formatDistanceToNowCalls = [];
    formatDistanceToNowMock.mockClear();
    intervalCallbacks = [];
    intervalIdCounter = 0;
    globalThis.setInterval = mock((fn, ms) => {
        const id = ++intervalIdCounter;
        intervalCallbacks.push({ id, fn, ms });
        return id;
    });
    globalThis.clearInterval = mock((id) => {
        intervalCallbacks = intervalCallbacks.filter((cb) => cb.id !== id);
    });
});

afterEach(async () => {
    globalThis.setInterval = originalSetInterval;
    globalThis.clearInterval = originalClearInterval;
    await mounted?.cleanup();
    mounted = null;
});

// --- basic formatting ---

test.serial("formats datetime using date-fns on connect", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z"></time>`);

    expect(formatDistanceToNowMock).toHaveBeenCalled();
    expect(formatDistanceToNowCalls[0].date).toBe(new Date("2020-01-01T00:00:00Z").getTime());
});

test.serial("sets innerHTML to the formatted distance", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z"></time>`);

    expect(mounted.root.innerHTML).toBe("5 minutes ago");
});

test.serial("sets the dateTime attribute on the element", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z"></time>`);

    expect(mounted.root.dateTime).toBe("2020-01-01T00:00:00Z");
});

// --- options: addSuffix, includeSeconds ---

test.serial("passes addSuffix option when value is true", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z" data-timeago-add-suffix-value="true"></time>`);

    expect(formatDistanceToNowCalls[0].options.addSuffix).toBe(true);
});

test.serial("passes includeSeconds option when value is true", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z" data-timeago-include-seconds-value="true"></time>`);

    expect(formatDistanceToNowCalls[0].options.includeSeconds).toBe(true);
});

test.serial("addSuffix defaults to false", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z"></time>`);

    expect(formatDistanceToNowCalls[0].options.addSuffix).toBe(false);
});

// --- invalid date ---

test.serial("handles invalid datetime by displaying the raw value", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="not-a-date"></time>`);

    expect(mounted.root.innerHTML).toBe("not-a-date");
    expect(mounted.root.dateTime).toBe("not-a-date");
    expect(formatDistanceToNowMock).not.toHaveBeenCalled();
});

test.serial("logs error for invalid datetime", async () => {
    const originalError = console.error;
    let logged = null;
    console.error = (msg) => { logged = msg; };

    await mount(`<time data-controller="timeago" data-timeago-datetime-value="not-a-date"></time>`);

    expect(logged).toContain("is not a valid date");
    console.error = originalError;
});

// --- refresh ---

test.serial("starts refreshing when refreshIntervalValue is set", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z" data-timeago-refresh-interval-value="1000"></time>`);

    expect(globalThis.setInterval).toHaveBeenCalled();
    expect(intervalCallbacks).toHaveLength(1);
    expect(intervalCallbacks[0].ms).toBe(1000);
});

test.serial("does not start refreshing when refreshInterval is not set", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z"></time>`);

    expect(globalThis.setInterval).not.toHaveBeenCalled();
    expect(intervalCallbacks).toHaveLength(0);
});

test.serial("does not start refreshing when datetime is invalid", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="not-a-date" data-timeago-refresh-interval-value="1000"></time>`);

    expect(globalThis.setInterval).not.toHaveBeenCalled();
});

test.serial("refresh calls load again to update the formatted text", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z" data-timeago-refresh-interval-value="1000"></time>`);

    expect(formatDistanceToNowCalls).toHaveLength(1);

    // Manually call the interval callback to simulate a refresh tick
    intervalCallbacks[0].fn();

    expect(formatDistanceToNowCalls).toHaveLength(2);
});

test.serial("stopRefreshing cancels the interval on disconnect", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z" data-timeago-refresh-interval-value="1000"></time>`);

    mounted.controller.disconnect();

    expect(globalThis.clearInterval).toHaveBeenCalled();
});

test.serial("stopRefreshing is a no-op if no timer is running", async () => {
    await mount(`<time data-controller="timeago" data-timeago-datetime-value="2020-01-01T00:00:00Z"></time>`);

    expect(() => mounted.controller.disconnect()).not.toThrow();
});

async function mount(html) {
    mounted = await mountController("timeago", TimeagoController, html);
}
