import { afterAll } from "bun:test";
import { Application } from "@hotwired/stimulus";
import { Window } from "happy-dom";

let releaseGlobals = null;

// installGlobals leaves the last mounted Window reachable from globalThis for the
// rest of the file. One Window is cheap, but a serial run keeps every file's
// JSGlobalObject alive in the same process, so they accumulate and starve the
// scheduler — Stimulus' MutationObserver callbacks then arrive late or not at all.
// Releasing here runs after every afterEach, so tests can still touch `document`
// during teardown.
afterAll(() => {
    releaseGlobals?.();
    releaseGlobals = null;
});

export async function mountController(identifier, Controller, html) {
    const testWindow = new Window({ url: "http://localhost" });
    testWindow.SyntaxError = SyntaxError;

    installGlobals(testWindow);

    document.body.innerHTML = html;

    const root = document.querySelector(`[data-controller~="${identifier}"]`);
    const application = Application.start(root);
    application.register(identifier, Controller);

    await wait(0);

    return {
        application,
        controller: application.getControllerForElementAndIdentifier(root, identifier),
        document,
        root,
        window: testWindow,
        cleanup: async () => {
            application.unload(identifier);
            application.stop();
            document.body.innerHTML = "";
            await wait(0);
            testWindow.close();
        },
    };
}

export async function mountControllers(identifier, Controller, html) {
    const testWindow = new Window({ url: "http://localhost" });
    testWindow.SyntaxError = SyntaxError;

    installGlobals(testWindow);

    document.body.innerHTML = html;

    const application = Application.start(document.body);
    application.register(identifier, Controller);

    await wait(0);

    const roots = [...document.querySelectorAll(`[data-controller~="${identifier}"]`)];

    return {
        application,
        document,
        roots,
        controllers: roots.map((root) =>
            application.getControllerForElementAndIdentifier(root, identifier)
        ),
        window: testWindow,
        cleanup: async () => {
            application.unload(identifier);
            application.stop();
            document.body.innerHTML = "";
            await wait(0);
            testWindow.close();
        },
    };
}

export function dispatchEvent(element, type, options = {}) {
    element.dispatchEvent(new Event(type, { bubbles: true, ...options }));
}

export function dispatchTurboSubmitStart(form) {
    form.dispatchEvent(new CustomEvent("turbo:submit-start", { bubbles: true }));
}

export function dispatchTurboSubmitEnd(form, success = true) {
    form.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success } }));
}

export function wait(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function installGlobals(testWindow) {
    const globals = {
        window: testWindow,
        document: testWindow.document,
        CustomEvent: testWindow.CustomEvent,
        Event: testWindow.Event,
        Element: testWindow.Element,
        FormData: testWindow.FormData,
        HTMLElement: testWindow.HTMLElement,
        KeyboardEvent: testWindow.KeyboardEvent,
        MouseEvent: testWindow.MouseEvent,
        MutationObserver: testWindow.MutationObserver,
        Node: testWindow.Node,
        DataTransfer: testWindow.DataTransfer,
        File: testWindow.File,
        Blob: testWindow.Blob,
        requestAnimationFrame: testWindow.requestAnimationFrame.bind(testWindow),
        cancelAnimationFrame: testWindow.cancelAnimationFrame.bind(testWindow),
        getComputedStyle: testWindow.getComputedStyle.bind(testWindow),
    };

    Object.assign(globalThis, globals);

    releaseGlobals = () => {
        for (const key of Object.keys(globals)) {
            delete globalThis[key];
        }
    };
}

export async function mountMultipleControllers(controllers, html) {
    const entries = Object.entries(controllers);
    if (entries.length === 0) throw new Error("At least one controller required");

    const testWindow = new Window({ url: "http://localhost" });
    testWindow.SyntaxError = SyntaxError;

    installGlobals(testWindow);

    document.body.innerHTML = html;

    const application = Application.start(document.body);

    for (const [identifier, Controller] of entries) {
        application.register(identifier, Controller);
        await wait(0);
    }

    await wait(0);

    const root = document.querySelector(`[data-controller~="${entries[0][0]}"]`);

    return {
        application,
        controller: root ? application.getControllerForElementAndIdentifier(root, entries[0][0]) : null,
        document,
        root,
        window: testWindow,
        getController(identifier, element) {
            return application.getControllerForElementAndIdentifier(element, identifier);
        },
        cleanup: async () => {
            for (const [identifier] of entries) {
                application.unload(identifier);
            }
            application.stop();
            document.body.innerHTML = "";
            await wait(0);
            testWindow.close();
        },
    };
}
