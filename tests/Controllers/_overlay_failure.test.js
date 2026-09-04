import { afterAll, afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

const openOperations = [];

mock.module("../../resources/js/controllers/_presence.js", () => ({
    createPresence: (element) => {
        let phase = "closed";
        let generation = 0;

        return {
            get phase() { return phase; },
            open: ({ beforeEnter, onEnter }) => {
                const current = ++generation;
                const operation = deferred();
                phase = "opening";
                openOperations.push(operation);

                return operation.promise.then(async (completed) => {
                    if (current !== generation) return false;
                    if (!completed || await beforeEnter?.() === false) {
                        phase = "closed";

                        return false;
                    }

                    element.hidden = false;
                    element.removeAttribute("inert");
                    element.dataset.state = "open";
                    onEnter?.();
                    phase = "open";

                    return true;
                });
            },
            close: () => {
                const current = ++generation;
                const operation = deferred();
                phase = "closing";

                return operation.promise.then((completed) => {
                    if (current !== generation) return false;
                    if (completed) phase = "closed";

                    return completed;
                });
            },
            sync: (open) => {
                generation++;
                phase = open ? "open" : "closed";

                return true;
            },
            cleanup: () => {
                generation++;
                phase = "closed";
            },
        };
    },
}));

const { createOverlay } = await import("../../resources/js/controllers/_overlay.js");

let overlay;
let testWindow;

beforeEach(() => {
    openOperations.length = 0;
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.getComputedStyle = testWindow.getComputedStyle.bind(testWindow);
});

afterEach(() => {
    overlay?.cleanup();
    overlay = null;
    testWindow.close();
});

afterAll(() => {
    delete globalThis.window;
    delete globalThis.document;
    delete globalThis.getComputedStyle;
});

test("failed opening removes its reserved stack entry", async () => {
    overlay = createTestOverlay();

    const opening = overlay.open();
    openOperations[0].resolve(false);

    expect(await opening).toBe(false);
    expect(overlay.isOpen).toBe(false);
    expect(overlay.stackPosition).toBe(-1);
});

test("a stale failed opening does not remove a newer stack entry", async () => {
    overlay = createTestOverlay();

    const firstOpening = overlay.open();
    overlay.close();
    const currentOpening = overlay.open();
    openOperations[0].resolve(false);

    expect(await firstOpening).toBe(false);
    expect(overlay.isOpen).toBe(true);
    expect(overlay.stackPosition).toBe(0);

    openOperations[1].resolve(true);
    expect(await currentOpening).toBe(true);
});

function createTestOverlay() {
    const modal = document.createElement("div");
    const backdrop = document.createElement("div");
    const dialog = document.createElement("div");
    modal.hidden = true;
    modal.setAttribute("inert", "");
    modal.dataset.state = "closed";
    modal.append(backdrop, dialog);
    document.body.append(modal);

    return createOverlay(null, {
        modalTarget: modal,
        backdropTarget: backdrop,
        dialogTarget: dialog,
    });
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}
