import { Application } from "@hotwired/stimulus";
import { Window } from "happy-dom";

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
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.CustomEvent = testWindow.CustomEvent;
    globalThis.Event = testWindow.Event;
    globalThis.Element = testWindow.Element;
    globalThis.FormData = testWindow.FormData;
    globalThis.HTMLElement = testWindow.HTMLElement;
    globalThis.KeyboardEvent = testWindow.KeyboardEvent;
    globalThis.MouseEvent = testWindow.MouseEvent;
    globalThis.MutationObserver = testWindow.MutationObserver;
    globalThis.Node = testWindow.Node;
    globalThis.DataTransfer = testWindow.DataTransfer;
    globalThis.File = testWindow.File;
    globalThis.Blob = testWindow.Blob;
    globalThis.requestAnimationFrame = testWindow.requestAnimationFrame.bind(testWindow);
    globalThis.cancelAnimationFrame = testWindow.cancelAnimationFrame.bind(testWindow);
    globalThis.getComputedStyle = testWindow.getComputedStyle.bind(testWindow);
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
            application.stop();
            document.body.innerHTML = "";
            await wait(0);
            testWindow.close();
        },
    };
}
