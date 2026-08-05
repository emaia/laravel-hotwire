import { afterEach, beforeEach, expect, test } from "bun:test";
import { Application } from "@hotwired/stimulus";
import { Window } from "happy-dom";

const { wait } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: ColorSchemeController } = await import(
    "../../resources/js/controllers/color_scheme_controller.js"
);

let mounted;
let media;
let reducedMotion;
let transitionCalls;

beforeEach(() => {
    media = createMedia(false);
    reducedMotion = createMedia(false, "(prefers-reduced-motion: reduce)");
    transitionCalls = 0;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;

    if (typeof document !== "undefined") {
        document.documentElement.removeAttribute("data-theme");
        document.documentElement.removeAttribute("data-color-scheme-mode");
        document.documentElement.style.colorScheme = "";
    }
});

// --- connect ---

test("applies stored dark mode on connect", async () => {
    await mount(`<button data-controller="color-scheme"></button>`, ({ window }) => {
        window.localStorage.setItem("hotwire.colorScheme", "dark");
    });

    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("dark");
    expect(document.documentElement.style.colorScheme).toBe("dark");
    expect(mounted.root.dataset.mode).toBe("dark");
    expect(mounted.root.dataset.scheme).toBe("dark");
});

test("resolves system mode from prefers-color-scheme", async () => {
    media.matches = true;

    await mount(`<button data-controller="color-scheme"></button>`);

    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("system");
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
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("light");
    expect(changes.at(-1)).toEqual({ mode: "light", scheme: "light" });

    mounted.controller.cycle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("dark");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("dark");
    expect(changes.at(-1)).toEqual({ mode: "dark", scheme: "dark" });
});

test("cycles restricted modes from a light system scheme without rewriting it on connect", async () => {
    await mount(
        `<button data-controller="color-scheme" data-color-scheme-modes-value="light dark"></button>`,
        ({ window }) => window.localStorage.setItem("hotwire.colorScheme", "system"),
    );

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("system");
    expect(mounted.root.dataset.mode).toBe("system");
    expect(mounted.root.dataset.scheme).toBe("light");

    mounted.controller.cycle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("dark");
    expect(mounted.root.dataset.mode).toBe("dark");
});

test("cycles restricted modes from a dark system scheme", async () => {
    media.matches = true;

    await mount(
        `<button data-controller="color-scheme" data-color-scheme-modes-value="light dark"></button>`,
        ({ window }) => window.localStorage.setItem("hotwire.colorScheme", "system"),
    );

    expect(mounted.root.dataset.mode).toBe("system");
    expect(mounted.root.dataset.scheme).toBe("dark");

    mounted.controller.cycle();

    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("light");
    expect(mounted.root.dataset.mode).toBe("light");
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

// --- view transitions ---

test("animates an opted-in user action and applies the theme", async () => {
    await mount(
        `<button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>`,
        ({ document }) => installViewTransition(document),
    );

    mounted.controller.dark();

    expect(transitionCalls).toBe(1);
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
    expect(mounted.root.dataset.mode).toBe("dark");
});

test("does not animate user actions without the opt-in", async () => {
    await mount(
        `<button data-controller="color-scheme"></button>`,
        ({ document }) => installViewTransition(document),
    );

    mounted.controller.dark();

    expect(transitionCalls).toBe(0);
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
});

test("does not animate when reduced motion is preferred", async () => {
    reducedMotion.matches = true;

    await mount(
        `<button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>`,
        ({ document }) => installViewTransition(document),
    );

    mounted.controller.dark();

    expect(transitionCalls).toBe(0);
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
});

test("does not animate a mode change that keeps the resolved scheme", async () => {
    await mount(
        `<button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>`,
        ({ document }) => installViewTransition(document),
    );

    mounted.controller.light();

    expect(transitionCalls).toBe(0);
    expect(document.documentElement.getAttribute("data-theme")).toBe("light");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("light");
});

test("never animates connect or synchronization updates", async () => {
    await mount(
        `<button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>`,
        ({ document }) => installViewTransition(document),
    );

    window.dispatchEvent(new CustomEvent("color-scheme:change", {
        detail: { mode: "dark", scheme: "dark" },
    }));
    window.dispatchEvent(new StorageEvent("storage", {
        key: "hotwire.colorScheme",
        newValue: "light",
    }));
    window.localStorage.setItem("hotwire.colorScheme", "system");
    media.matches = true;
    media.dispatch();

    expect(transitionCalls).toBe(0);
    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
});

test("applies the theme when the View Transitions API is unavailable", async () => {
    await mount(`<button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>`);

    expect(document.startViewTransition).toBeUndefined();

    mounted.controller.dark();

    expect(document.documentElement.getAttribute("data-theme")).toBe("dark");
});

test("synchronizes sibling instances inside the transition callback", async () => {
    let update;

    await mount(`
        <button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>
        <button data-controller="color-scheme"></button>
    `, ({ document }) => {
        document.startViewTransition = (callback) => {
            transitionCalls++;
            update = callback;

            return transitionResult();
        };
    });

    mounted.controllers[0].dark();

    expect(transitionCalls).toBe(1);
    expect(mounted.roots[0].dataset.scheme).toBe("light");
    expect(mounted.roots[1].dataset.scheme).toBe("light");

    update();
    await wait(0);

    expect(mounted.roots[0].dataset.scheme).toBe("dark");
    expect(mounted.roots[1].dataset.scheme).toBe("dark");
});

test("applies the latest action when a transition update is pending", async () => {
    const changes = [];
    let update;

    await mount(`
        <button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>
        <button data-controller="color-scheme"></button>
    `, ({ document }) => {
        document.startViewTransition = (callback) => {
            transitionCalls++;
            update = callback;

            return transitionResult();
        };
    });
    window.addEventListener("color-scheme:change", (event) => changes.push(event.detail));

    mounted.controllers[0].dark();
    mounted.controllers[0].light();

    expect(transitionCalls).toBe(1);
    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("light");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("system");

    update();
    await wait(0);

    expect(document.documentElement.getAttribute("data-theme")).toBe("light");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("light");
    expect(mounted.roots[0].dataset.scheme).toBe("light");
    expect(mounted.roots[1].dataset.scheme).toBe("light");
    expect(changes).toEqual([{ mode: "light", scheme: "light" }]);
});

test("ignores a pending callback invalidated by a sibling instance", async () => {
    let update;

    await mount(`
        <button data-controller="color-scheme" data-color-scheme-view-transition-value="true"></button>
        <button data-controller="color-scheme"></button>
    `, ({ document }) => {
        document.startViewTransition = (callback) => {
            transitionCalls++;
            update = callback;

            return transitionResult();
        };
    });

    mounted.controllers[0].dark();
    mounted.controllers[1].light();

    expect(transitionCalls).toBe(1);
    expect(window.localStorage.getItem("hotwire.colorScheme")).toBe("light");
    expect(document.documentElement.getAttribute("data-theme")).toBe("light");

    update();
    await wait(0);

    expect(document.documentElement.getAttribute("data-theme")).toBe("light");
    expect(document.documentElement.getAttribute("data-color-scheme-mode")).toBe("light");
    expect(mounted.roots[0].dataset.scheme).toBe("light");
    expect(mounted.roots[1].dataset.scheme).toBe("light");
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
    targetWindow.matchMedia = (query) => query === "(prefers-reduced-motion: reduce)" ? reducedMotion : media;
    globalThis.matchMedia = targetWindow.matchMedia;
}

function createMedia(matches, query = "(prefers-color-scheme: dark)") {
    const listeners = new Set();

    return {
        matches,
        media: query,
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

function installViewTransition(targetDocument) {
    targetDocument.startViewTransition = (callback) => {
        transitionCalls++;
        callback();

        return transitionResult();
    };
}

function transitionResult() {
    return {
        finished: Promise.resolve(),
        ready: Promise.resolve(),
        updateCallbackDone: Promise.resolve(),
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
