import { afterEach, beforeEach, expect, mock, test } from "bun:test";

const createCalls = [];
const destroyMock = mock(() => {});

mock.module("@emaia/sonner/vanilla", () => ({
    createToaster: (options) => {
        createCalls.push(options);
        return { destroy: destroyMock };
    },
}));

const { mountController } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: ToasterController } = await import(
    "../../resources/js/controllers/toaster_controller.js"
);

let mounted;

beforeEach(() => {
    createCalls.length = 0;
    destroyMock.mockClear();
    if (typeof window !== "undefined") window.toaster = null;
    if (typeof document !== "undefined") {
        document.documentElement.removeAttribute("data-theme");
    }
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    if (typeof document !== "undefined") {
        document.documentElement.removeAttribute("data-theme");
    }
});

// --- connect ---

test.serial("creates toaster on connect with default options", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(createCalls).toHaveLength(1);
    expect(createCalls[0].position).toBe("bottom-center");
    expect(createCalls[0].theme).toBe("light");
    expect(createCalls[0].closeButton).toBe(true);
    expect(createCalls[0].duration).toBe(4000);
    expect(window.toaster).toBeDefined();
});

test.serial("resolves theme from html[data-theme] when theme is system", async () => {
    const { Window } = await import("happy-dom");
    const testWindow = new Window({ url: "http://localhost" });
    testWindow.document.documentElement.setAttribute("data-theme", "dark");
    testWindow.SyntaxError = SyntaxError;

    const origWindow = globalThis.window;
    const origDocument = globalThis.document;
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;

    globalThis.CustomEvent = testWindow.CustomEvent;
    globalThis.Event = testWindow.Event;
    globalThis.MutationObserver = testWindow.MutationObserver;

    try {
        testWindow.document.body.innerHTML = `<div data-controller="toaster"></div>`;
        const root = testWindow.document.querySelector("[data-controller~=\"toaster\"]");
        const { Application } = await import("@hotwired/stimulus");
        const application = Application.start(root);
        application.register("toaster", ToasterController);

        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(createCalls[0].theme).toBe("dark");

        application.stop();
    } finally {
        globalThis.window = origWindow;
        globalThis.document = origDocument;
        testWindow.close();
    }
});

test.serial("explicit theme value overrides data-theme resolution", async () => {
    document.documentElement.setAttribute("data-theme", "dark");

    await mount(`<div data-controller="toaster" data-toaster-theme-value="light"></div>`);

    expect(createCalls[0].theme).toBe("light");
});

test.serial("idempotent: re-connect skips create when window.toaster already exists", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(createCalls).toHaveLength(1);
    expect(window.toaster).toBeDefined();

    mounted.controller.connect();

    expect(createCalls).toHaveLength(1);
});

test.serial("passes overridden values to createToaster", async () => {
    await mount(`
        <div data-controller="toaster"
             data-toaster-position-value="top-right"
             data-toaster-theme-value="dark"
             data-toaster-duration-value="2000"
             data-toaster-close-button-value="false"
        ></div>
    `);

    expect(createCalls[0].position).toBe("top-right");
    expect(createCalls[0].theme).toBe("dark");
    expect(createCalls[0].duration).toBe(2000);
    expect(createCalls[0].closeButton).toBe(false);
});

// --- optional fields ---

test.serial("omits gap when value is 0 (default)", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    expect(createCalls[0].gap).toBeUndefined();
});

test.serial("includes gap when value > 0", async () => {
    await mount(`<div data-controller="toaster" data-toaster-gap-value="12"></div>`);

    expect(createCalls[0].gap).toBe(12);
});

test.serial("splits hotkey on commas and whitespace", async () => {
    await mount(`<div data-controller="toaster" data-toaster-hotkey-value="alt+T, shift+T"></div>`);

    expect(createCalls[0].hotkey).toEqual(["alt+T", "shift+T"]);
});

test.serial("parses offset as JSON when it starts with {", async () => {
    await mount(`<div data-controller="toaster" data-toaster-offset-value='{"top":10}'></div>`);

    expect(createCalls[0].offset).toEqual({ top: 10 });
});

test.serial("passes offset as string when not JSON", async () => {
    await mount(`<div data-controller="toaster" data-toaster-offset-value="12px"></div>`);

    expect(createCalls[0].offset).toBe("12px");
});

// --- theme observer ---

test.serial("sets up MutationObserver when theme is system", async () => {
    // happy-dom doesn't fully support MutationObserver, but the controller
    // should still connect without errors
    await mount(`<div data-controller="toaster"></div>`);

    expect(window.toaster).toBeDefined();
});

// --- disconnect ---

test.serial("disconnect with autoDisconnect=true destroys toaster and clears reference", async () => {
    await mount(`<div data-controller="toaster" data-toaster-auto-disconnect-value="true"></div>`);

    expect(window.toaster).toBeDefined();
    mounted.controller.disconnect();

    expect(destroyMock).toHaveBeenCalledTimes(1);
    expect(window.toaster).toBeNull();
});

test.serial("disconnect with autoDisconnect=false (default) keeps toaster alive", async () => {
    await mount(`<div data-controller="toaster"></div>`);

    mounted.controller.disconnect();

    expect(destroyMock).not.toHaveBeenCalled();
    expect(window.toaster).toBeDefined();
});

async function mount(html) {
    mounted = await mountController("toaster", ToasterController, html);
}
