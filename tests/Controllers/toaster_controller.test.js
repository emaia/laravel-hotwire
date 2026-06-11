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
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
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
