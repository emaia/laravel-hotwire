import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { dispatchEvent, mountController } from "../../resources/js/helpers/test_stimulus.js";
import GtmController from "../../resources/js/controllers/gtm_controller.js";

let mounted;
let originalConsoleError;

beforeEach(() => {
    originalConsoleError = console.error;
    console.error = mock(() => {});
});

afterEach(async () => {
    console.error = originalConsoleError;
    await mounted?.cleanup();
    mounted = null;
});

// --- initialize ---

test.serial("initialize sets window.dataLayer when not present", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123" data-gtm-lazy-value="false"></div>`);

    expect(window.dataLayer).toBeDefined();
    expect(Array.isArray(window.dataLayer)).toBe(true);
});

// --- assertIsGtmId ---

test.serial("rejects on invalid GTM ID", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="invalid" data-gtm-lazy-value="false"></div>`);

    expect(window.gtmDidInit).toBeUndefined();
});

test.serial("rejects on empty GTM ID", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="" data-gtm-lazy-value="false"></div>`);

    expect(window.gtmDidInit).toBeUndefined();
});

test.serial("gives suggestion for almost-correct GTM IDs", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-test123" data-gtm-lazy-value="false"></div>`);

    expect(window.gtmDidInit).toBeUndefined();
});

// --- non-lazy mode ---

test.serial("non-lazy: calls initGTM immediately on connect", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123" data-gtm-lazy-value="false"></div>`);

    expect(window.gtmDidInit).toBe(true);
});

test.serial("non-lazy: creates a script element with correct src", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123" data-gtm-lazy-value="false"></div>`);

    const script = document.querySelector('script[src*="gtm.js"]');
    expect(script).not.toBeNull();
    expect(script.src).toContain("GTM-TEST123");
});

// --- lazy mode (default) ---

test.serial("lazy: does not init GTM immediately on connect (default lazy=true)", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    expect(window.gtmDidInit).toBeUndefined();
});

test.serial("lazy: inits GTM on first mousemove", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    dispatchEvent(document, "mousemove");
    expect(window.gtmDidInit).toBe(true);
});

test.serial("lazy: inits GTM on first scroll", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    dispatchEvent(document, "scroll");
    expect(window.gtmDidInit).toBe(true);
});

test.serial("lazy: inits GTM on first touchstart", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    dispatchEvent(document, "touchstart");
    expect(window.gtmDidInit).toBe(true);
});

test.serial("lazy: only inits once (gtmDidInit guards subsequent triggers)", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    dispatchEvent(document, "mousemove");
    expect(window.gtmDidInit).toBe(true);

    let loadCalls = 0;
    const origLoadScript = mounted.controller.loadScript.bind(mounted.controller);
    mounted.controller.loadScript = () => { loadCalls++; return origLoadScript(); };

    dispatchEvent(document, "scroll");

    expect(loadCalls).toBe(0);
});

test.serial("lazy: each listener is removed after its own event fires", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    let initCalls = 0;
    const origInitGTM = mounted.controller.initGTM.bind(mounted.controller);
    mounted.controller.initGTM = () => { initCalls++; return origInitGTM(); };

    dispatchEvent(document, "mousemove");
    expect(initCalls).toBe(1);

    window.gtmDidInit = undefined;

    dispatchEvent(document, "mousemove");
    expect(initCalls).toBe(1);
});

test.serial("lazy: disconnect removes all pending lazy listeners", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    mounted.controller.disconnect();

    window.gtmDidInit = undefined;

    dispatchEvent(document, "mousemove");
    expect(window.gtmDidInit).toBeUndefined();

    dispatchEvent(document, "scroll");
    expect(window.gtmDidInit).toBeUndefined();

    dispatchEvent(document, "touchstart");
    expect(window.gtmDidInit).toBeUndefined();
});

// --- event action ---

test.serial("pushes event with payload to dataLayer", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    mounted.controller.event({
        params: {
            eventName: "purchase",
            eventPayload: { amount: 100, currency: "USD" },
        },
    });

    expect(window.dataLayer).toContainEqual({
        event: "purchase",
        amount: 100,
        currency: "USD",
    });
});

test.serial("pushes event without payload to dataLayer", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    mounted.controller.event({ params: { eventName: "pageview" } });

    expect(window.dataLayer).toContainEqual({ event: "pageview" });
});

test.serial("throws when eventName is missing", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    expect(() => mounted.controller.event({ params: {} })).toThrow("Event name is required.");
});

// --- loadScript ---

test.serial("loadScript appends a script to document.head", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    const before = document.querySelectorAll("script").length;

    mounted.controller.loadScript();

    expect(document.querySelectorAll("script").length).toBe(before + 1);
});

test.serial("loadScript sets correct script.src", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    mounted.controller.loadScript();

    const script = document.querySelector('script[src*="GTM-TEST123"]');
    expect(script.src).toBe("https://www.googletagmanager.com/gtm.js?id=GTM-TEST123");
});

test.serial("loadScript: lazy mode sets async=true on script", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123"></div>`);

    mounted.controller.loadScript();

    const script = document.querySelector('script[src*="gtm.js"]');
    expect(script.async).toBe(true);
});

test.serial("loadScript: non-lazy mode sets async=false on script", async () => {
    await mount(`<div data-controller="gtm" data-gtm-id-value="GTM-TEST123" data-gtm-lazy-value="false"></div>`);

    mounted.controller.loadScript();

    const script = document.querySelector('script[src*="gtm.js"]');
    expect(script.async).toBe(false);
});

async function mount(html) {
    mounted = await mountController("gtm", GtmController, html);
}
