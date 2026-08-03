import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

import { createOverlay } from "../../resources/js/controllers/_overlay.js";

let overlay;
let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.CustomEvent = testWindow.CustomEvent;
    globalThis.Element = testWindow.Element;
    globalThis.KeyboardEvent = testWindow.KeyboardEvent;
    globalThis.getComputedStyle = testWindow.getComputedStyle.bind(testWindow);
    globalThis.requestAnimationFrame = (callback) => {
        callback();
        return 1;
    };
    globalThis.cancelAnimationFrame = () => {};
});

afterEach(() => {
    overlay?.cleanup();
    overlay = null;
    testWindow.close();
});

test("close keeps the overlay inert until descendant motion settles", async () => {
    const motion = fakeAnimation();
    const { modal, backdrop, dialog } = elements();
    const onClose = mock(() => {});
    dialog.getAnimations = () => modal.dataset.state === "closed" ? [motion.animation] : [];
    overlay = createOverlay(null, options({ modal, backdrop, dialog, onClose }));

    await overlay.open();
    const closing = overlay.close();
    await tick();

    expect(overlay.isClosing).toBe(true);
    expect(modal.dataset.state).toBe("closed");
    expect(modal.hidden).toBe(false);
    expect(modal.hasAttribute("inert")).toBe(true);
    expect(onClose).not.toHaveBeenCalled();

    motion.finish();

    expect(await closing).toBe(true);
    expect(modal.hidden).toBe(true);
    expect(onClose).toHaveBeenCalledTimes(1);
});

test("reopening during exit prevents stale teardown", async () => {
    const motion = fakeAnimation();
    const { modal, backdrop, dialog } = elements();
    const onClose = mock(() => {});
    dialog.getAnimations = () => modal.dataset.state === "closed" ? [motion.animation] : [];
    overlay = createOverlay(null, options({ modal, backdrop, dialog, onClose }));

    await overlay.open();
    const closing = overlay.close();

    expect(modal.dispatchEvent(morphAttributeEvent("data-state"))).toBe(false);
    expect(modal.dispatchEvent(morphAttributeEvent("hidden"))).toBe(false);

    const reopening = overlay.open();

    expect(await reopening).toBe(true);
    expect(await closing).toBe(false);

    motion.finish();
    await tick();

    expect(overlay.isOpen).toBe(true);
    expect(modal.dataset.state).toBe("open");
    expect(modal.hidden).toBe(false);
    expect(modal.hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
    expect(onClose).not.toHaveBeenCalled();
});

test("motion none closes immediately without inspecting descendant animations", async () => {
    const { modal, backdrop, dialog } = elements();
    let inspected = false;
    modal.dataset.motion = "none";
    dialog.getAnimations = () => {
        inspected = true;
        return [];
    };
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));

    await overlay.open();
    inspected = false;

    expect(await overlay.close()).toBe(true);
    expect(modal.hidden).toBe(true);
    expect(inspected).toBe(false);
});

test("reduced motion closes immediately without inspecting descendant animations", async () => {
    const { modal, backdrop, dialog } = elements();
    let inspected = false;
    window.matchMedia = (query) => ({ matches: query === "(prefers-reduced-motion: reduce)" });
    dialog.getAnimations = () => {
        inspected = true;
        return [];
    };
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));

    await overlay.open();
    inspected = false;

    expect(await overlay.close()).toBe(true);
    expect(modal.hidden).toBe(true);
    expect(inspected).toBe(false);
});

test("a custom state attribute preserves the sidebar desktop state", async () => {
    const { modal, backdrop, dialog } = elements();
    modal.dataset.state = "expanded";
    modal.dataset.mobileState = "closed";
    overlay = createOverlay(null, options({
        modal,
        backdrop,
        dialog,
        stateAttribute: "mobileState",
    }));

    await overlay.open();

    expect(modal.dataset.state).toBe("expanded");
    expect(modal.dataset.mobileState).toBe("open");

    expect(await overlay.close()).toBe(true);
    expect(modal.dataset.state).toBe("expanded");
    expect(modal.dataset.mobileState).toBe("closed");
});

test("Turbo morphs cannot overwrite managed presence attributes", async () => {
    const { modal, backdrop, dialog } = elements();
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));

    await overlay.open();

    for (const attributeName of ["data-state", "data-presence", "hidden", "inert"]) {
        const event = morphAttributeEvent(attributeName);

        expect(modal.dispatchEvent(event)).toBe(false);
        expect(event.defaultPrevented).toBe(true);
    }

    const motionEvent = morphAttributeEvent("data-motion");
    expect(modal.dispatchEvent(motionEvent)).toBe(true);
    expect(motionEvent.defaultPrevented).toBe(false);

    const descendant = dialog.querySelector("button");
    expect(descendant.dispatchEvent(morphAttributeEvent("hidden"))).toBe(true);
});

test("Turbo morph protection follows a custom state attribute", () => {
    const { modal, backdrop, dialog } = elements();
    overlay = createOverlay(null, options({
        modal,
        backdrop,
        dialog,
        stateAttribute: "mobileState",
    }));

    expect(modal.dispatchEvent(morphAttributeEvent("data-mobile-state"))).toBe(false);
    expect(modal.dispatchEvent(morphAttributeEvent("data-state"))).toBe(true);
});

test("cleanup releases managed presence attributes to future morphs", () => {
    const { modal, backdrop, dialog } = elements();
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));

    expect(modal.dispatchEvent(morphAttributeEvent("hidden"))).toBe(false);

    overlay.cleanup();
    overlay = null;

    expect(modal.dispatchEvent(morphAttributeEvent("hidden"))).toBe(true);
});

test("Turbo morphs preserve top-layer attributes only while the overlay is shown", async () => {
    const { modal, backdrop, dialog } = elements();
    modal.showPopover = () => {};
    modal.hidePopover = () => {};
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));

    expect(modal.dispatchEvent(morphAttributeEvent("popover"))).toBe(true);

    await overlay.open();

    for (const attributeName of ["popover", "data-hotwire-top-layer", "data-hotwire-top-layer-popover"]) {
        expect(modal.dispatchEvent(morphAttributeEvent(attributeName))).toBe(false);
    }

    await overlay.close();

    expect(modal.dispatchEvent(morphAttributeEvent("popover"))).toBe(true);
});

test("scroll locking preserves body classes that predate the overlay", async () => {
    const { modal, backdrop, dialog } = elements();
    document.body.classList.add("overflow-hidden");
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));

    await overlay.open();
    await overlay.close();

    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

test("focus return can reopen without leaving desired state and DOM out of sync", async () => {
    const { modal, backdrop, dialog, trigger } = elements();
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));
    let reopening = null;

    await overlay.open();
    dialog.querySelector("button").focus();
    trigger.addEventListener("focus", () => {
        reopening = overlay.open();
    }, { once: true });

    const closing = overlay.close();

    expect(await closing).toBe(false);
    expect(await reopening).toBe(true);
    expect(overlay.isOpen).toBe(true);
    expect(overlay.phase).toBe("open");
    expect(modal.dataset.state).toBe("open");
    expect(modal.hidden).toBe(false);
    expect(modal.hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

function elements() {
    const trigger = document.createElement("button");
    const modal = document.createElement("div");
    const backdrop = document.createElement("div");
    const dialog = document.createElement("div");
    const close = document.createElement("button");

    modal.dataset.state = "closed";
    modal.dataset.motion = "default";
    modal.hidden = true;
    modal.setAttribute("inert", "");
    dialog.appendChild(close);
    modal.append(backdrop, dialog);
    document.body.append(trigger, modal);
    trigger.focus();

    return { backdrop, dialog, modal, trigger };
}

function options({ modal, backdrop, dialog, ...overrides }) {
    return {
        modalTarget: modal,
        backdropTarget: backdrop,
        dialogTarget: dialog,
        lockScrollClasses: ["overflow-hidden"],
        getTriggerElement: () => document.activeElement,
        ...overrides,
    };
}

function fakeAnimation() {
    const finished = deferred();

    return {
        animation: {
            effect: {
                getComputedTiming: () => ({ endTime: 100 }),
                target: null,
            },
            finished: finished.promise,
            playState: "running",
        },
        finish: () => finished.resolve(),
    };
}

function morphAttributeEvent(attributeName) {
    return new CustomEvent("turbo:before-morph-attribute", {
        bubbles: true,
        cancelable: true,
        detail: { attributeName, mutationType: "update" },
    });
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}

function tick() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
