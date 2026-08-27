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

test("composing Escape does not close the overlay", async () => {
    const { modal, backdrop, dialog } = elements();
    overlay = createOverlay(null, options({ modal, backdrop, dialog }));
    await overlay.open();

    const event = new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
    Object.defineProperty(event, "isComposing", { value: true });
    dialog.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(overlay.isOpen).toBe(true);
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

test("links package titles and descriptions and protects their references during morphs", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    const description = document.createElement("p");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    description.dataset.slot = "modal-description";
    dialog.prepend(title, description);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(title.id).toBe("account-modal-title");
    expect(description.id).toBe("account-modal-description");
    expect(modal.getAttribute("aria-labelledby")).toBe(title.id);
    expect(modal.getAttribute("aria-describedby")).toBe(description.id);
    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(false);
    expect(modal.dispatchEvent(morphAttributeEvent("aria-describedby"))).toBe(false);
});

test("does not name an overlay from a nested overlay title", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const nestedModal = document.createElement("div");
    const nestedTitle = document.createElement("h2");
    root.id = "parent-modal";
    modal.setAttribute("role", "dialog");
    nestedModal.setAttribute("role", "dialog");
    nestedTitle.dataset.slot = "modal-title";
    nestedModal.append(nestedTitle);
    dialog.prepend(nestedModal);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(modal.hasAttribute("aria-labelledby")).toBe(false);
    expect(nestedTitle.id).toBe("");
});

test("reconciles ownership when a nested role boundary changes", () => {
    let observerCallback;
    let scheduledRefresh;
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const nestedModal = document.createElement("div");
    const title = document.createElement("h2");
    root.id = "parent-modal";
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    nestedModal.append(title);
    dialog.prepend(nestedModal);
    root.append(modal);
    Object.defineProperty(testWindow, "MutationObserver", {
        configurable: true,
        value: class {
            constructor(callback) {
                observerCallback ??= callback;
            }

            observe() {}
            disconnect() {}
        },
    });
    globalThis.requestAnimationFrame = (callback) => {
        scheduledRefresh = callback;

        return 3;
    };
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));
    expect(modal.getAttribute("aria-labelledby")).toBe("parent-modal-title");

    nestedModal.setAttribute("role", "dialog");
    observerCallback([{ target: nestedModal, attributeName: "role" }]);
    scheduledRefresh();
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);

    nestedModal.removeAttribute("role");
    observerCallback([{ target: nestedModal, attributeName: "role" }]);
    scheduledRefresh();
    expect(modal.getAttribute("aria-labelledby")).toBe("parent-modal-title");
});

test("preserves an application-authored accessible name", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-labelledby", "application-title");
    title.dataset.slot = "modal-title";
    dialog.prepend(title);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(modal.getAttribute("aria-labelledby")).toBe("application-title");
    expect(title.id).toBe("");
    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(true);
});

test("copies an application-authored accessible name from the controller root", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    root.setAttribute("aria-label", "Account settings");
    modal.setAttribute("role", "dialog");
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(modal.getAttribute("aria-label")).toBe("Account settings");
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);
});

test("restores a target-authored labelledby after a root aria-label is removed", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    root.setAttribute("aria-label", "Fallback");
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-labelledby", "author-title");
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(modal.getAttribute("aria-label")).toBe("Fallback");
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);

    modal.setAttribute("aria-labelledby", "newer-author-title");
    root.setAttribute("aria-label", "Updated fallback");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);

    root.setAttribute("aria-labelledby", "root-title");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.getAttribute("aria-labelledby")).toBe("root-title");

    root.removeAttribute("aria-label");
    modal.dispatchEvent(morphedElementEvent());

    expect(modal.hasAttribute("aria-label")).toBe(false);
    expect(modal.getAttribute("aria-labelledby")).toBe("root-title");

    root.removeAttribute("aria-labelledby");
    modal.dispatchEvent(morphedElementEvent());

    expect(modal.hasAttribute("aria-label")).toBe(false);
    expect(modal.getAttribute("aria-labelledby")).toBe("newer-author-title");
});

test("restores a target-authored labelledby after a root labelledby is removed", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    root.setAttribute("aria-labelledby", "root-title");
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-labelledby", "author-title");
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(modal.getAttribute("aria-labelledby")).toBe("root-title");

    modal.setAttribute("aria-labelledby", "newer-author-title");
    root.setAttribute("aria-labelledby", "updated-root-title");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.getAttribute("aria-labelledby")).toBe("updated-root-title");

    root.removeAttribute("aria-labelledby");
    modal.dispatchEvent(morphedElementEvent());

    expect(modal.getAttribute("aria-labelledby")).toBe("newer-author-title");
});

test("cleanup restores target-authored accessibility before reconnect", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    root.setAttribute("aria-label", "Root label");
    root.setAttribute("aria-describedby", "root-description");
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-labelledby", "author-title");
    modal.setAttribute("aria-describedby", "author-description");
    root.append(modal);
    const overlayOptions = options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    });
    overlay = createOverlay({ element: root }, overlayOptions);

    expect(modal.getAttribute("aria-label")).toBe("Root label");
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);
    expect(modal.getAttribute("aria-describedby")).toBe("root-description");

    overlay.cleanup();
    overlay = null;

    expect(modal.hasAttribute("aria-label")).toBe(false);
    expect(modal.getAttribute("aria-labelledby")).toBe("author-title");
    expect(modal.getAttribute("aria-describedby")).toBe("author-description");

    overlay = createOverlay({ element: root }, overlayOptions);
    root.removeAttribute("aria-label");
    root.removeAttribute("aria-describedby");
    modal.dispatchEvent(morphedElementEvent());

    expect(modal.getAttribute("aria-labelledby")).toBe("author-title");
    expect(modal.getAttribute("aria-describedby")).toBe("author-description");
});

test("does not protect stale ownership after a synchronous root override", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    dialog.prepend(title);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    root.setAttribute("aria-label", "Root override");
    const incomingModal = modal.cloneNode(true);
    incomingModal.querySelector('[data-slot="modal-title"]').id = "server-title";
    incomingModal.setAttribute("aria-labelledby", "server-title");
    modal.dispatchEvent(morphElementEvent(incomingModal));

    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(true);
    title.id = "server-title";
    modal.setAttribute("aria-labelledby", "server-title");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.getAttribute("aria-label")).toBe("Root override");
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);

    root.removeAttribute("aria-label");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.getAttribute("aria-labelledby")).toBe("server-title");
});

test("allows a server morph after managed labelledby ownership becomes stale", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    dialog.prepend(title);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));
    modal.setAttribute("aria-labelledby", "foreign");

    const incomingModal = modal.cloneNode(true);
    incomingModal.querySelector('[data-slot="modal-title"]').id = "m3-title";
    incomingModal.setAttribute("aria-labelledby", "m3-title");
    modal.dispatchEvent(morphElementEvent(incomingModal));

    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(true);
    title.id = "m3-title";
    modal.setAttribute("aria-labelledby", "m3-title");
    modal.dispatchEvent(morphedElementEvent());

    expect(modal.getAttribute("aria-labelledby")).toBe("m3-title");
    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(false);
});

test("generates accessible references for manual overlays without ids", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    dialog.prepend(title);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    expect(title.id).toMatch(/^hw-modal-\d+-title$/);
    expect(modal.getAttribute("aria-labelledby")).toBe(title.id);
});

test("releases and reacquires a managed accessible name across morphs", () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    const incomingModal = document.createElement("div");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    dialog.prepend(title);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));
    incomingModal.setAttribute("role", "dialog");
    incomingModal.setAttribute("aria-labelledby", "application-title");

    modal.dispatchEvent(morphElementEvent(incomingModal));

    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(true);
    modal.setAttribute("aria-labelledby", "application-title");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.getAttribute("aria-labelledby")).toBe("application-title");

    const restoredModal = modal.cloneNode(true);
    restoredModal.removeAttribute("aria-labelledby");
    modal.dispatchEvent(morphElementEvent(restoredModal));
    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(true);
    modal.removeAttribute("aria-labelledby");
    modal.dispatchEvent(morphedElementEvent());
    expect(modal.getAttribute("aria-labelledby")).toBe("account-modal-title");
});

test("keeps managed ownership when a morph is canceled", async () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const title = document.createElement("h2");
    const incomingModal = document.createElement("div");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    dialog.prepend(title);
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));
    incomingModal.setAttribute("role", "dialog");
    incomingModal.setAttribute("aria-labelledby", "application-title");
    modal.addEventListener("turbo:before-morph-element", (event) => event.preventDefault(), { once: true });

    expect(modal.dispatchEvent(morphElementEvent(incomingModal))).toBe(false);
    await tick();

    expect(modal.dispatchEvent(morphAttributeEvent("aria-labelledby"))).toBe(false);
});

test("cleanup invalidates pending accessibility morph work", async () => {
    const root = document.createElement("div");
    const { modal, backdrop, dialog } = elements();
    const incomingModal = document.createElement("div");
    const title = document.createElement("h2");
    root.id = "account-modal";
    modal.setAttribute("role", "dialog");
    incomingModal.setAttribute("role", "dialog");
    title.dataset.slot = "modal-title";
    root.append(modal);
    overlay = createOverlay({ element: root }, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));

    modal.dispatchEvent(morphElementEvent(incomingModal));
    dialog.prepend(title);
    overlay.cleanup();
    overlay = null;
    await tick();

    expect(title.id).toBe("");
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);
});

test("coalesces accessibility observer refreshes", () => {
    let observerCallback;
    let scheduledRefresh;
    let canceledFrame;
    let queryCount = 0;
    const { modal, backdrop, dialog } = elements();
    modal.setAttribute("role", "dialog");
    const originalQuerySelectorAll = modal.querySelectorAll.bind(modal);
    modal.querySelectorAll = (...args) => {
        queryCount++;

        return originalQuerySelectorAll(...args);
    };
    Object.defineProperty(testWindow, "MutationObserver", {
        configurable: true,
        value: class {
            constructor(callback) {
                observerCallback = callback;
            }

            observe() {}
            disconnect() {}
        },
    });
    globalThis.requestAnimationFrame = (callback) => {
        scheduledRefresh = callback;

        return 7;
    };
    globalThis.cancelAnimationFrame = (frame) => {
        canceledFrame = frame;
    };

    overlay = createOverlay(null, options({
        modal,
        backdrop,
        dialog,
        accessibilityPrefix: "modal",
    }));
    expect(queryCount).toBe(2);

    observerCallback([{ target: modal }]);
    observerCallback([{ target: modal }]);
    observerCallback([{ target: modal }]);

    expect(queryCount).toBe(2);
    scheduledRefresh();
    expect(queryCount).toBe(4);

    const nestedModal = document.createElement("div");
    nestedModal.setAttribute("role", "dialog");
    modal.append(nestedModal);
    scheduledRefresh = null;
    observerCallback([{ target: nestedModal }]);

    expect(scheduledRefresh).toBeNull();
    expect(queryCount).toBe(4);

    observerCallback([{ target: modal }]);
    const staleRefresh = scheduledRefresh;
    overlay.cleanup();
    overlay = null;

    expect(canceledFrame).toBe(7);
    staleRefresh();
    expect(queryCount).toBe(4);
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

function morphElementEvent(newElement) {
    return new CustomEvent("turbo:before-morph-element", {
        bubbles: true,
        cancelable: true,
        detail: { newElement },
    });
}

function morphedElementEvent() {
    return new CustomEvent("turbo:morph-element", {
        bubbles: true,
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
