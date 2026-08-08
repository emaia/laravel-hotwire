import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import TimeagoController from "../../resources/js/controllers/timeago_controller.js";

const NOW = Date.parse("2026-08-05T12:00:00Z");

let currentTime;
let intervalCallbacks = [];
let intervalIdCounter = 0;
const originalDateNow = Date.now;
const originalSetInterval = globalThis.setInterval;
const originalClearInterval = globalThis.clearInterval;
const originalConsoleError = console.error;

let mounted;
let consoleErrorMock;

beforeEach(() => {
    currentTime = NOW;
    Date.now = mock(() => currentTime);
    intervalCallbacks = [];
    intervalIdCounter = 0;
    globalThis.setInterval = mock((fn, ms) => {
        const id = ++intervalIdCounter;
        intervalCallbacks.push({ id, fn, ms });
        return id;
    });
    globalThis.clearInterval = mock((id) => {
        intervalCallbacks = intervalCallbacks.filter((callback) => callback.id !== id);
    });
    consoleErrorMock = mock(() => {});
    console.error = consoleErrorMock;
});

afterEach(async () => {
    Date.now = originalDateNow;
    globalThis.setInterval = originalSetInterval;
    globalThis.clearInterval = originalClearInterval;
    console.error = originalConsoleError;
    await mounted?.cleanup();
    mounted = null;
});

// --- Relative distance ---

test.serial.each([
    [30, true, "30 seconds ago"],
    [5 * 60, false, "5 minutes ago"],
    [3 * 60 * 60, false, "3 hours ago"],
    [24 * 60 * 60, false, "yesterday"],
    [2 * 7 * 24 * 60 * 60, false, "2 weeks ago"],
    [2 * 30.43668 * 24 * 60 * 60, false, "2 months ago"],
    [2 * 365.24016 * 24 * 60 * 60, false, "2 years ago"],
])("formats the unit ladder at %p elapsed seconds", async (elapsed, includeSeconds, expected) => {
    await mount(datetime(-elapsed), attributes({ includeSeconds }));

    expect(mounted.root.textContent).toBe(expected);
});

test.serial("formats future dates with a suffix", async () => {
    await mount(datetime(5 * 60 * 60));

    expect(mounted.root.textContent).toBe("in 5 hours");
});

test.serial("formats an absolute localized unit without a suffix", async () => {
    await mount(datetime(-3 * 60 * 60), attributes({ addSuffix: false }));

    expect(mounted.root.textContent).toBe("3 hours");
});

test.serial("defaults to an absolute localized unit when add-suffix is omitted", async () => {
    await mount(datetime(-3 * 60 * 60), "");

    expect(mounted.root.textContent).toBe("3 hours");
});

test.serial("uses minutes for sub-minute differences by default", async () => {
    await mount(datetime(-5));

    expect(mounted.root.textContent).toBe("1 minute ago");
});

test.serial("allows seconds for sub-minute differences", async () => {
    await mount(datetime(-5), attributes({ includeSeconds: true }));

    expect(mounted.root.textContent).toBe("5 seconds ago");
});

test.serial("keeps zero seconds so RelativeTimeFormat can render now", async () => {
    await mount(datetime(0), attributes({ includeSeconds: true }));

    expect(mounted.root.textContent).toBe("now");
});

test.serial("clamps zero minutes to one minute", async () => {
    await mount(datetime(0));

    expect(mounted.root.textContent).toBe("1 minute ago");
});

test.serial("calculates one distance pair for both format modes", async () => {
    await mount(datetime(-90));
    const relativeDistance = mounted.controller.distance(Date.parse(datetime(-90)));

    mounted.root.dataset.timeagoAddSuffixValue = "false";
    await wait(0);
    mounted.controller.load();

    expect(relativeDistance).toEqual({ value: -1, unit: "minute" });
    expect(mounted.root.textContent).toBe("1 minute");
});

// --- Localization and formatter cache ---

test.serial("uses an explicit BCP 47 locale", async () => {
    await mount(datetime(-24 * 60 * 60), attributes({ locale: "pt-BR" }));

    expect(mounted.root.textContent).toBe("ontem");
});

test.serial("inherits locale from the document language", async () => {
    class DocumentLocaleTimeagoController extends TimeagoController {
        connect() {
            document.documentElement.lang = "ja";
            super.connect();
        }
    }

    await mount(datetime(5 * 60 * 60), attributes(), DocumentLocaleTimeagoController);

    expect(mounted.root.textContent).toBe("5 時間後");
});

test.serial("reuses cached formatters until locale changes", async () => {
    await mount(datetime(-24 * 60 * 60), attributes({ locale: "en" }));
    const relativeFormatter = mounted.controller.relativeTimeFormatter;
    const unitFormatters = mounted.controller.unitFormatters;

    mounted.controller.load();
    expect(mounted.controller.relativeTimeFormatter).toBe(relativeFormatter);
    expect(mounted.controller.unitFormatters).toBe(unitFormatters);

    // Driving this through the attribute would depend on happy-dom's MutationObserver, which
    // Stimulus needs to notice a value change and which does not fire reliably here. Invoking the
    // callback keeps the assertion on our cache invalidation rather than on the test environment.
    mounted.root.dataset.timeagoLocaleValue = "pt-BR";
    mounted.controller.localeValueChanged();
    await wait(0);

    expect(mounted.controller.relativeTimeFormatter).not.toBe(relativeFormatter);
    expect(mounted.controller.unitFormatters).not.toBe(unitFormatters);
    expect(mounted.root.textContent).toBe("ontem");
});

// --- Element and invalid date behavior ---

test.serial("sets the dateTime property on the element", async () => {
    const value = datetime(-5 * 60);
    await mount(value);

    expect(mounted.root.dateTime).toBe(value);
});

test.serial("handles invalid datetime by displaying the raw value", async () => {
    await mount("not-a-date");

    expect(mounted.root.textContent).toBe("not-a-date");
    expect(mounted.root.dateTime).toBe("not-a-date");
    expect(consoleErrorMock).toHaveBeenCalled();
    expect(consoleErrorMock.mock.calls[0][0]).toContain("is not a valid date");
});

// --- Refresh lifecycle ---

test.serial("starts refreshing at the configured interval", async () => {
    await mount(datetime(-5 * 60), attributes({ refreshInterval: 1000 }));

    expect(globalThis.setInterval).toHaveBeenCalled();
    expect(intervalCallbacks).toHaveLength(1);
    expect(intervalCallbacks[0].ms).toBe(1000);
});

test.serial("does not start refreshing without an interval or with an invalid date", async () => {
    await mount(datetime(-5 * 60));
    expect(globalThis.setInterval).not.toHaveBeenCalled();
    await mounted.cleanup();
    mounted = null;

    await mount("not-a-date", attributes({ refreshInterval: 1000 }));
    expect(globalThis.setInterval).not.toHaveBeenCalled();
});

test.serial("refreshes using the cached formatters", async () => {
    await mount(datetime(-5 * 60), attributes({ refreshInterval: 1000 }));
    const formatter = mounted.controller.relativeTimeFormatter;

    currentTime += 60 * 60 * 1000;
    intervalCallbacks[0].fn();

    expect(mounted.root.textContent).toBe("1 hour ago");
    expect(mounted.controller.relativeTimeFormatter).toBe(formatter);
});

test.serial("cancels the interval on disconnect", async () => {
    await mount(datetime(-5 * 60), attributes({ refreshInterval: 1000 }));

    mounted.controller.disconnect();

    expect(globalThis.clearInterval).toHaveBeenCalled();
});

test.serial("disconnect is a no-op when no timer is running", async () => {
    await mount(datetime(-5 * 60));

    expect(() => mounted.controller.disconnect()).not.toThrow();
});

async function mount(datetimeValue, extraAttributes = attributes(), Controller = TimeagoController) {
    mounted = await mountController("timeago", Controller, `
        <time
            data-controller="timeago"
            data-timeago-datetime-value="${datetimeValue}"
            ${extraAttributes}
        ></time>
    `);
}

function datetime(offsetSeconds) {
    return new Date(NOW + offsetSeconds * 1000).toISOString();
}

function attributes({ addSuffix, includeSeconds, locale, refreshInterval } = {}) {
    return [
        `data-timeago-add-suffix-value="${addSuffix !== false}"`,
        includeSeconds ? 'data-timeago-include-seconds-value="true"' : "",
        locale ? `data-timeago-locale-value="${locale}"` : "",
        refreshInterval ? `data-timeago-refresh-interval-value="${refreshInterval}"` : "",
    ].filter(Boolean).join(" ");
}
