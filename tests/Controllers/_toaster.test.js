import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

let createToaster;
let emitToast;
let resetToaster;
let testWindow;
let viewport;
let toaster;

beforeEach(async () => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.CustomEvent = testWindow.CustomEvent;
    globalThis.Event = testWindow.Event;
    globalThis.Element = testWindow.Element;
    globalThis.HTMLElement = testWindow.HTMLElement;
    globalThis.KeyboardEvent = testWindow.KeyboardEvent;
    globalThis.Node = testWindow.Node;
    globalThis.getComputedStyle = testWindow.getComputedStyle.bind(testWindow);
    globalThis.requestAnimationFrame = testWindow.requestAnimationFrame.bind(testWindow);
    globalThis.cancelAnimationFrame = testWindow.cancelAnimationFrame.bind(testWindow);

    ({ createToaster, emitToast, resetToaster } = await import(
        "../../resources/js/controllers/_toaster.js"
    ));
    resetToaster();

    // happy-dom has no layout, so every element measures 0 and the stack maths collapses. Giving
    // toasts a fixed height keeps the offset accumulation meaningful here; the real geometry is
    // covered in the Playwright suite.
    Object.defineProperty(testWindow.HTMLElement.prototype, "offsetHeight", {
        configurable: true,
        get() {
            return this.dataset?.slot === "toast" ? 80 : 0;
        },
    });

    viewport = document.createElement("div");
    viewport.dataset.slot = "toaster";
    document.body.appendChild(viewport);
});

afterEach(() => {
    toaster?.destroy();
    toaster = null;
    resetToaster?.();
    testWindow.close();
});

function mount(options = {}) {
    toaster = createToaster(viewport, options);

    return toaster;
}

function toasts() {
    return [...viewport.querySelectorAll('[data-slot="toast"]')];
}

// --- Pre-connect buffering ---

test("drains toasts emitted before the viewport connects", () => {
    emitToast({ message: "Queued before mount", type: "success" });

    expect(toasts()).toHaveLength(0);

    mount();

    expect(toasts()).toHaveLength(1);
    expect(toasts()[0].querySelector('[data-slot="toast-title"]').textContent).toBe("Queued before mount");
});

test("drains buffered toasts with the newest toast first in the DOM", () => {
    emitToast({ message: "first" });
    emitToast({ message: "second" });

    mount();

    expect(toasts().map((t) => t.querySelector('[data-slot="toast-title"]').textContent))
        .toEqual(["second", "first"]);
});

test("empties the buffer once drained so a second viewport does not replay it", () => {
    emitToast({ message: "only once" });
    mount();
    toaster.destroy();
    toaster = null;

    const second = createToaster(viewport, {});

    expect(viewport.querySelectorAll('[data-slot="toast"]')).toHaveLength(0);

    second.destroy();
});

// --- Anatomy ---

test("renders the documented anatomy", () => {
    mount();
    toaster.toast("Saved", { description: "Everything went through" });

    const toast = toasts()[0];

    expect(toast.dataset.slot).toBe("toast");
    expect(toast.querySelector('[data-slot="toast-icon"]')).not.toBeNull();
    expect(toast.querySelector('[data-slot="toast-content"]')).not.toBeNull();
    expect(toast.querySelector('[data-slot="toast-title"]').textContent).toBe("Saved");
    expect(toast.querySelector('[data-slot="toast-description"]').textContent).toBe("Everything went through");
});

test("omits the description node when no description is given", () => {
    mount();
    toaster.toast("Saved");

    expect(toasts()[0].querySelector('[data-slot="toast-description"]')).toBeNull();
});

test("renders message and description as text, never as markup", () => {
    mount();
    toaster.toast("<img src=x onerror=alert(1)>", { description: "<b>bold</b>" });

    const toast = toasts()[0];

    expect(toast.querySelector("img")).toBeNull();
    expect(toast.querySelector("b")).toBeNull();
    expect(toast.querySelector('[data-slot="toast-title"]').textContent).toBe("<img src=x onerror=alert(1)>");
});

// --- Content combinations ---
//
// A Turbo Stream macro forwards whatever the request held, so empty strings arrive routinely:
// `->toast($type, $request->input('message', ''), description: ...)` with no message field posted.
// An empty node still occupies a row in the body and throws the icon out of alignment.

test("omits the title node when the message is empty", () => {
    mount();
    toaster.error("", { description: "The server restored the authoritative task state." });

    const toast = toasts()[0];

    expect(toast.querySelector('[data-slot="toast-title"]')).toBeNull();
    expect(toast.querySelector('[data-slot="toast-description"]').textContent)
        .toBe("The server restored the authoritative task state.");
});

test("omits the description node when it is an empty string", () => {
    mount();
    toaster.success("Saved", { description: "" });

    expect(toasts()[0].querySelector('[data-slot="toast-description"]')).toBeNull();
});

test("still renders a toast when both message and description are empty", () => {
    mount();
    toaster.info("", { description: "" });

    const toast = toasts()[0];

    expect(toast).toBeDefined();
    expect(toast.querySelector('[data-slot="toast-body"]')).not.toBeNull();
    expect(toast.querySelector('[data-slot="toast-title"]')).toBeNull();
});

test("omits the close button when it is disabled", () => {
    mount({ closeButton: false });
    toaster.success("Saved");

    expect(toasts()[0].querySelector('[data-slot="toast-close"]')).toBeNull();
});

test("emits the icon slot for every type, default included", () => {
    mount();
    toaster.toast("plain");
    toaster.success("done");

    // The default type hides its icon in CSS rather than dropping the node, so the row layout is
    // identical whichever type arrives.
    expect(toasts().every((t) => t.querySelector('[data-slot="toast-icon"]') !== null)).toBe(true);
});

// --- Types ---

test("marks the toast with its type", () => {
    mount();
    toaster.success("Done");
    toaster.error("Broke");
    toaster.warning("Careful");
    toaster.info("Heads up");
    toaster.toast("Plain");

    expect(toasts().map((t) => t.dataset.type)).toEqual([
        "default",
        "info",
        "warning",
        "error",
        "success",
    ]);
});

// --- Public surface ---

test("exposes the documented public surface", () => {
    mount();

    for (const method of ["toast", "success", "error", "warning", "info", "dismiss", "destroy"]) {
        expect(typeof toaster[method]).toBe("function");
    }
});

test("dismiss removes the toast it names and leaves the others", async () => {
    mount();
    const first = toaster.success("first");
    toaster.success("second");

    toaster.dismiss(first);
    await wait(80);

    expect(toasts().map((t) => t.querySelector('[data-slot="toast-title"]').textContent))
        .toEqual(["second"]);
});

test("destroy empties the viewport", () => {
    mount();
    toaster.toast("gone");

    toaster.destroy();
    toaster = null;

    expect(viewport.querySelectorAll('[data-slot="toast"]')).toHaveLength(0);
});

test("destroy clears the global reference when it points at this toaster", () => {
    mount();
    window.toaster = toaster;

    toaster.destroy();
    toaster = null;

    expect(window.toaster).toBeNull();
});

// --- Motion ---

test("enters from the closed state so the CSS transition has somewhere to travel from", async () => {
    mount();
    toaster.toast("arriving");

    // Mounted closed and visible, so the browser has a first frame to transition away from.
    // Syncing straight to open would leave nothing to animate.
    expect(toasts()[0].dataset.state).toBe("closed");
    expect(toasts()[0].hidden).toBe(false);

    await wait(60);

    expect(toasts()[0].dataset.state).toBe("open");
});

test("is already closed on the element it inserts, before any layout happens", () => {
    // The first style the browser resolves has to be the off-screen one. Appended stateless, the
    // card paints at its resting position and the entry transition then runs backwards from there,
    // which reads as a bare fade.
    const appended = [];
    const original = viewport.insertBefore.bind(viewport);
    viewport.insertBefore = (node, child) => {
        appended.push(node.dataset.state);

        return original(node, child);
    };

    mount();
    toaster.toast("arriving");

    expect(appended).toEqual(["closed"]);
});

test("keeps a dismissed toast in the DOM until its exit motion finishes", async () => {
    mount();
    const id = toaster.toast("leaving");

    toaster.dismiss(id);

    expect(toasts()).toHaveLength(1);
    expect(toasts()[0].dataset.state).toBe("closed");

    await wait(80);

    expect(toasts()).toHaveLength(0);
});

test("dismiss is idempotent while the exit is still running", async () => {
    mount();
    const id = toaster.toast("leaving");

    toaster.dismiss(id);
    toaster.dismiss(id);
    await wait(80);

    expect(toasts()).toHaveLength(0);
});

test("a toast leaving does not hold a slot in the stack", async () => {
    mount();
    await wait(60);
    const first = toaster.toast("first");
    toaster.toast("second");
    await wait(60);

    toaster.dismiss(first);
    await frame();

    const remaining = toasts().find(
        (t) => t.querySelector('[data-slot="toast-title"]').textContent === "second",
    );

    expect(remaining.style.getPropertyValue("--toast-index")).toBe("0");
    expect(remaining.style.getPropertyValue("--toast-offset-y")).toBe("0px");
});

// --- Measured geometry ---

test("never records a zero height", () => {
    // A Turbo Drive visit can connect the trigger while the permanent viewport is not in the new
    // document yet, so the toast measures 0. Recording that pins the card to its borders and the
    // content is clipped for the rest of its life.
    Object.defineProperty(testWindow.HTMLElement.prototype, "offsetHeight", {
        configurable: true,
        get: () => 0,
    });

    mount();
    toaster.toast("measured while detached");

    expect(toasts()[0].style.getPropertyValue("--toast-height")).toBe("");
    expect(toasts()[0].style.getPropertyValue("--toast-frontmost-height")).toBe("");
});

test("records the height once the element can be measured again", async () => {
    let measurable = false;
    Object.defineProperty(testWindow.HTMLElement.prototype, "offsetHeight", {
        configurable: true,
        get() {
            if (!measurable) return 0;

            return this.dataset?.slot === "toast" ? 92 : 0;
        },
    });

    mount();
    toaster.toast("late layout");

    expect(toasts()[0].style.getPropertyValue("--toast-height")).toBe("");

    measurable = true;
    toaster.remeasure();
    await frame();

    expect(toasts()[0].style.getPropertyValue("--toast-height")).toBe("92px");
});

test("writes each toast height so the stack can offset from it", () => {
    mount();
    toaster.toast("measured");

    expect(toasts()[0].style.getPropertyValue("--toast-height")).toBe("80px");
});

test("offsets each toast by the stack in front of it", async () => {
    mount();
    toaster.toast("oldest");
    toaster.toast("newest");
    await frame();

    const [newest, oldest] = toasts();

    expect(newest.style.getPropertyValue("--toast-offset-y")).toBe("0px");
    expect(oldest.style.getPropertyValue("--toast-offset-y")).toBe("80px");
});

test("re-offsets the stack after a dismissal", async () => {
    mount();
    const first = toaster.toast("first");
    toaster.toast("second");
    toaster.toast("third");

    toaster.dismiss(first);
    await wait(80);

    expect(toasts()).toHaveLength(2);
    expect(toasts()[0].style.getPropertyValue("--toast-offset-y")).toBe("0px");
});

// --- Expansion ---

test("expands the stack while the pointer is over the viewport", () => {
    mount();
    toaster.toast("a");

    viewport.dispatchEvent(new Event("pointerenter"));

    // Mirrored onto the toast, so the stylesheet places it from its own attributes.
    expect(viewport.dataset.expanded).toBe("true");
    expect(toasts()[0].dataset.expanded).toBe("true");

    viewport.dispatchEvent(new Event("pointerleave"));

    expect(toasts()[0].dataset.expanded).toBe("false");
});

test("keeps the stack expanded when configured that way", () => {
    mount({ expand: true });
    toaster.toast("a");

    viewport.dispatchEvent(new Event("pointerleave"));

    expect(toasts()[0].dataset.expanded).toBe("true");
});

test("a toast arriving while expanded is born expanded", () => {
    mount();
    viewport.dispatchEvent(new Event("pointerenter"));
    toaster.toast("late arrival");

    expect(toasts()[0].dataset.expanded).toBe("true");
});

// --- Timers ---

test("dismisses a toast once its duration elapses", async () => {
    mount({ duration: 20 });
    toaster.toast("temporary");

    expect(toasts()).toHaveLength(1);
    await wait(60);

    expect(toasts()).toHaveLength(0);
});

test("honours a per-toast duration over the viewport default", async () => {
    mount({ duration: 5000 });
    toaster.toast("quick", { duration: 20 });

    await wait(60);

    expect(toasts()).toHaveLength(0);
});

test("keeps a toast with a non-positive duration on screen", async () => {
    mount({ duration: 20 });
    toaster.toast("sticky", { duration: 0 });

    await wait(60);

    expect(toasts()).toHaveLength(1);
});

test("pauses the timer while the pointer is over the viewport", async () => {
    mount({ duration: 40 });
    toaster.toast("hovered");

    viewport.dispatchEvent(new Event("pointerenter"));
    await wait(80);

    expect(toasts()).toHaveLength(1);

    viewport.dispatchEvent(new Event("pointerleave"));
    await wait(80);

    expect(toasts()).toHaveLength(0);
});

test("pauses the timer while focus is inside the viewport", async () => {
    mount({ duration: 40 });
    toaster.toast("focused");

    viewport.dispatchEvent(new Event("focusin"));
    await wait(80);

    expect(toasts()).toHaveLength(1);
});

test("keeps the timer paused until every pause cause clears", async () => {
    mount({ duration: 40 });
    toaster.toast("focused hover");

    viewport.dispatchEvent(new Event("pointerenter"));
    viewport.dispatchEvent(new Event("focusin"));
    viewport.dispatchEvent(new Event("pointerleave"));
    await wait(80);

    expect(toasts()).toHaveLength(1);

    viewport.dispatchEvent(new Event("focusout"));
    await wait(80);

    expect(toasts()).toHaveLength(0);
});

// --- Stacking ---

test("indexes the stack newest first so CSS can place it", async () => {
    mount();
    toaster.toast("oldest");
    toaster.toast("newest");
    await frame();

    const [newest, oldest] = toasts();

    expect(newest.style.getPropertyValue("--toast-index")).toBe("0");
    expect(oldest.style.getPropertyValue("--toast-index")).toBe("1");
});

test("keeps the DOM order newest first so Tab reaches the frontmost toast first", async () => {
    mount();
    toaster.toast("oldest");
    toaster.toast("middle");
    toaster.toast("newest");
    await frame();

    expect(toasts().map((t) => t.querySelector('[data-slot="toast-title"]').textContent))
        .toEqual(["newest", "middle", "oldest"]);
});

test("indexes each position independently", async () => {
    mount();
    toaster.toast("top", { position: "top-center" });
    toaster.toast("bottom", { position: "bottom-center" });
    await frame();

    expect(toasts().map((t) => t.style.getPropertyValue("--toast-index"))).toEqual(["0", "0"]);
});

test("marks toasts past visible-toasts as limited", async () => {
    mount({ visibleToasts: 2 });
    toaster.toast("a");
    toaster.toast("b");
    toaster.toast("c");
    await frame();

    expect(toasts().map((t) => t.hasAttribute("data-limited"))).toEqual([false, false, true]);
});

test("removes limited toast close buttons from the tab order", async () => {
    mount({ visibleToasts: 2 });
    toaster.toast("a");
    toaster.toast("b");
    toaster.toast("c");
    await frame();

    expect(toasts().map((t) => t.querySelector('[data-slot="toast-close"]').tabIndex))
        .toEqual([0, 0, -1]);
});

// --- Accessibility ---

test("makes the viewport a labelled landmark", () => {
    mount({ containerAriaLabel: "Alerts" });

    expect(viewport.getAttribute("role")).toBe("region");
    expect(viewport.getAttribute("aria-label")).toBe("Alerts");
});

test("defaults the landmark label to Notifications", () => {
    mount();

    expect(viewport.getAttribute("aria-label")).toBe("Notifications");
});

test("announces errors assertively and everything else politely", () => {
    mount();
    toaster.error("broke");
    toaster.success("fine");

    const [success, error] = toasts();

    expect(error.getAttribute("role")).toBe("alert");
    expect(error.getAttribute("aria-live")).toBe("assertive");
    expect(success.getAttribute("role")).toBe("status");
    expect(success.getAttribute("aria-live")).toBe("polite");
});

test("moves focus to the viewport on F6", () => {
    mount();
    toaster.toast("focus me");
    let defaultPrevented = false;
    const event = new KeyboardEvent("keydown", { key: "F6", bubbles: true, cancelable: true });

    document.dispatchEvent(event);
    defaultPrevented = event.defaultPrevented;

    expect(defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(viewport);
});

test("ignores F6 when there is nothing to announce", () => {
    mount();
    const event = new KeyboardEvent("keydown", { key: "F6", bubbles: true, cancelable: true });

    document.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
});

// --- Cleanup ---

test("destroy removes the global listeners it registered", () => {
    mount();
    toaster.toast("first");
    toaster.destroy();
    toaster = null;

    const event = new KeyboardEvent("keydown", { key: "F6", bubbles: true, cancelable: true });
    document.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
});

test("destroy clears pending timers so nothing fires afterwards", async () => {
    mount({ duration: 30 });
    toaster.toast("doomed");
    toaster.destroy();
    toaster = null;

    await wait(70);

    expect(viewport.querySelectorAll('[data-slot="toast"]')).toHaveLength(0);
});

function wait(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/** Restacking is deferred a frame so the cards behind have a style to transition from. */
function frame() {
    return new Promise((resolve) => requestAnimationFrame(() => resolve()));
}

// --- A replaced viewport ---

test("buffers a toast emitted while the active viewport is detached", () => {
    mount();
    emitToast({ message: "Before the visit", type: "success" });

    expect(toasts()).toHaveLength(1);

    // A viewport without data-turbo-permanent leaves with the old body, and the manager outlives it
    // when auto-disconnect is off. An emission here would otherwise be built into the detached node.
    viewport.remove();

    const replacement = document.createElement("div");
    replacement.dataset.slot = "toaster";
    document.body.appendChild(replacement);

    emitToast({ message: "Task updated", type: "success" });

    expect(replacement.querySelectorAll('[data-slot="toast"]')).toHaveLength(0);

    // The new controller connects and builds its manager; the buffer drains into it.
    toaster.destroy();
    toaster = createToaster(replacement, {});

    expect([...replacement.querySelectorAll('[data-slot="toast-title"]')].map((n) => n.textContent)).toEqual([
        "Task updated",
    ]);
});
