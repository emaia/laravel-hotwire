import { afterEach, beforeEach, expect, test } from "bun:test";
import { Application } from "@hotwired/stimulus";
import { Window } from "happy-dom";

const { wait } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: ColorSchemeController } = await import(
    "../../resources/js/controllers/color_scheme_controller.js"
);

let mounted;
let media;

beforeEach(() => {
    media = createMedia(false);
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;

    if (typeof document !== "undefined") {
        document.documentElement.removeAttribute("data-theme");
        document.documentElement.style.colorScheme = "";
    }
});

// --- connect ---

test("applies stored dark mode on connect", async () => {
    await mount(`<button data-controller="color-scheme"></button>`, ({ window }) => {
        window.localStorage.setItem("hotwire.colorScheme", "dark");
    });

    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
    expect(document.documentElement.style.colorScheme).toBe("dark");
    expect(mounted.root.dataset.mode).toBe("dark");
    expect(mounted.root.dataset.scheme).toBe("dark");
});

test("resolves system mode from prefers-color-scheme", async () => {
    media.matches = true;

    await mount(`<button data-controller="color-scheme"></button>`);

    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
    expect(mounted.root.dataset.mode).toBe("system");
    expect(mounted.root.dataset.scheme).toBe("dark");
});

// --- actions ---

test("cycle walks through configured modes and dispatches change", async () => {
    const changes = [];

    await mount(`<button data-controller="color-scheme" data-color-scheme-modes-value="light dark system"></button>`);
    window.addEventListener("color-scheme:change", (event) => changes.push(event.detail));

    mounted.controller.cycle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("light");
    expect(document.documentElement.getAttribute("data-theme")).toBe("light");
    expect(changes.at(-1)).toEqual({ mode: "light", scheme: "light" });

    mounted.controller.cycle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("dark");
    expect(changes.at(-1)).toEqual({ mode: "dark", scheme: "dark" });
});

test("toggle switches between resolved light and dark", async () => {
    await mount(`<button data-controller="color-scheme"></button>`);

    mounted.controller.toggle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("dark");
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");

    mounted.controller.toggle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("light");
    expect(document.documentElement.getAttribute("data-theme")).toBe("light");
});

test("set accepts a Stimulus action param and aliases set explicit modes", async () => {
    await mount(`<button data-controller="color-scheme"></button>`);

    mounted.controller.set({ params: { mode: "dark" } });
    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("dark");

    mounted.controller.light();
    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("light");

    mounted.controller.system();
    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("system");
});

// --- synchronization ---

test("synchronizes multiple connected instances", async () => {
    await mount(`
        <button id="a" data-controller="color-scheme"></button>
        <button id="b" data-controller="color-scheme"></button>
    `);

    mounted.controllers[0].dark();
    await wait(0);

    expect(mounted.roots[0].dataset.mode).toBe("dark");
    expect(mounted.roots[1].dataset.mode).toBe("dark");
    expect(mounted.roots[1].dataset.scheme).toBe("dark");
});

test("responds to storage events for the configured key", async () => {
    await mount(`<button data-controller="color-scheme"></button>`);

    window.localStorage.setItem("hotwire.colorScheme", "dark");
    window.dispatchEvent(new StorageEvent("storage", {
        key: "hotwire.colorScheme",
        newValue: "dark",
    }));
    await wait(0);

    expect(mounted.root.dataset.mode).toBe("dark");
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
});

test("updates system scheme when the media query changes", async () => {
    await mount(`<button data-controller="color-scheme"></button>`);

    media.matches = true;
    media.dispatch();
    await wait(0);

    expect(mounted.root.dataset.mode).toBe("system");
    expect(mounted.root.dataset.scheme).toBe("dark");
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
});

test("removes listeners on disconnect", async () => {
    await mount(`<button data-controller="color-scheme"></button>`);

    mounted.controller.disconnect();
    media.matches = true;
    media.dispatch();
    window.localStorage.setItem("hotwire.colorScheme", "dark");
    window.dispatchEvent(new StorageEvent("storage", {
        key: "hotwire.colorScheme",
        newValue: "dark",
    }));
    await wait(0);

    expect(mounted.root.dataset.scheme).toBe("light");
});

async function mount(html, beforeStart = null) {
    const testWindow = new Window({ url: "http://localhost" });
    testWindow.SyntaxError = SyntaxError;
    installGlobals(testWindow);
    installMatchMedia(testWindow);
    if (beforeStart) {
        beforeStart({ window: testWindow, document: testWindow.document });
    }

    document.body.innerHTML = html;

    const application = Application.start(document.body);
    application.register("color-scheme", ColorSchemeController);

    await wait(0);

    const roots = [...document.querySelectorAll('[data-controller~="color-scheme"]')];

    mounted = {
        application,
        controller: application.getControllerForElementAndIdentifier(roots[0], "color-scheme"),
        controllers: roots.map((root) => application.getControllerForElementAndIdentifier(root, "color-scheme")),
        document,
        root: roots[0],
        roots,
        window: testWindow,
        cleanup: async () => {
            application.unload("color-scheme");
            application.stop();
            document.body.innerHTML = "";
            await wait(0);
            testWindow.close();
        },
    };
}

function installMatchMedia(targetWindow) {
    targetWindow.matchMedia = () => media;
    globalThis.matchMedia = targetWindow.matchMedia;
}

function createMedia(matches) {
    const listeners = new Set();

    return {
        matches,
        media: "(prefers-color-scheme: dark)",
        addEventListener(_event, listener) {
            listeners.add(listener);
        },
        removeEventListener(_event, listener) {
            listeners.delete(listener);
        },
        dispatch() {
            for (const listener of listeners) {
                listener({ matches: this.matches, media: this.media });
            }
        },
    };
}

function installGlobals(testWindow) {
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.CustomEvent = testWindow.CustomEvent;
    globalThis.Event = testWindow.Event;
    globalThis.Element = testWindow.Element;
    globalThis.HTMLElement = testWindow.HTMLElement;
    globalThis.MutationObserver = testWindow.MutationObserver;
    globalThis.Node = testWindow.Node;
    globalThis.StorageEvent = testWindow.StorageEvent;
}
