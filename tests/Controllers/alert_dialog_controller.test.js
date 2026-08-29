import { afterEach, expect, test } from "bun:test";

import { mountController, mountControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import AlertDialogController from "../../resources/js/controllers/alert_dialog_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    document.body.removeAttribute("style");
    document.body.removeAttribute("class");
});

const HTML = `
    <div data-controller="alert-dialog"
         data-alert-dialog-lock-scroll-class="overflow-hidden"
         data-alert-dialog-close-on-click-outside-value="true">
        <div id="trigger-zone" data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
            <a href="/items/1" id="trigger">Delete</a>
        </div>

        <div data-alert-dialog-target="modal"
             data-state="closed"
             data-motion="none"
             data-action="click->alert-dialog#clickOutside"
             hidden inert>
            <div data-alert-dialog-target="backdrop"></div>
            <div data-alert-dialog-target="dialog">
                <button id="cancel" data-action="click->alert-dialog#cancel">Cancel</button>
                <button id="confirm" data-action="click->alert-dialog#confirm">OK</button>
            </div>
        </div>
    </div>
`;

function clickWith(element, init = {}) {
    return element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, ...init }));
}

// --- intercept opens the dialog ---

test.serial("intercept prevents the click and opens the dialog", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const modal = document.querySelector('[data-alert-dialog-target="modal"]');

    expect(modal.hidden).toBe(true);

    const defaultPrevented = !clickWith(trigger);

    expect(defaultPrevented).toBe(true);
    expect(modal.hidden).toBe(false);
    expect(modal.dataset.state).toBe("closed");
    expect(modal.hasAttribute("inert")).toBe(true);

    await wait(0);

    expect(modal.dataset.state).toBe("open");
    expect(modal.hasAttribute("inert")).toBe(false);
    expect(mounted.controller.isOpen).toBe(true);
});

test.serial("intercept ignores middle-button click", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    const defaultPrevented = !clickWith(trigger, { button: 1 });

    expect(defaultPrevented).toBe(false);
    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("intercept ignores click with modifier keys", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    clickWith(trigger, { ctrlKey: true });
    clickWith(trigger, { metaKey: true });
    clickWith(trigger, { shiftKey: true });

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("capture interception keeps the original click from reaching trigger listeners", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    let triggerClicks = 0;
    trigger.addEventListener("click", () => triggerClicks++);

    clickWith(trigger);

    expect(triggerClicks).toBe(0);
    expect(mounted.controller.isOpen).toBe(true);
});

test.serial("capture interception survives trigger stopPropagation", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    trigger.addEventListener("click", (event) => event.stopPropagation());

    const defaultPrevented = !clickWith(trigger);

    expect(defaultPrevented).toBe(true);
    expect(mounted.controller.isOpen).toBe(true);
    expect(mounted.controller.pendingAction?.kind).toBe("link");
});

// --- semantic presence state ---

test.serial("after open, modal becomes interactive and lock-scroll is applied to body", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const modal = document.querySelector('[data-alert-dialog-target="modal"]');

    clickWith(trigger);
    await wait(10); // rAF tick

    expect(modal.dataset.state).toBe("open");
    expect(modal.hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

test.serial("lock-scroll compensates for the removed scrollbar gutter", async () => {
    await mount();
    setViewportWidth(1000, 980);

    clickWith(document.getElementById("trigger"));

    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
    expect(document.body.style.paddingRight).toBe("20px");

    mounted.controller.cancel();

    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
    expect(document.body.style.paddingRight).toBe("");
});

test.serial("lock-scroll keeps compensation until the last overlay unlocks", async () => {
    mounted = await mountControllers(
        "alert-dialog",
        AlertDialogController,
        `${HTML}${HTML.replace('id="trigger"', 'id="trigger-two"')}`,
    );
    setViewportWidth(1000, 980);

    const [first, second] = mounted.controllers;

    clickWith(document.getElementById("trigger"));
    expect(document.body.style.paddingRight).toBe("20px");

    clickWith(document.getElementById("trigger-two"));
    expect(document.body.style.paddingRight).toBe("20px");

    first.cancel();
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
    expect(document.body.style.paddingRight).toBe("20px");

    second.cancel();
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
    expect(document.body.style.paddingRight).toBe("");
});

// --- confirm() executes the captured action ---

test.serial("listeners observe the confirmed click rather than the intercepted one", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const triggerZone = document.getElementById("trigger-zone");
    const observed = [];

    triggerZone.addEventListener("click", (event) => observed.push(event));

    clickWith(trigger);
    expect(observed).toEqual([]);
    expect(mounted.controller.isOpen).toBe(true);

    mounted.controller.confirm();
    await wait(20);

    expect(observed.length).toBe(1);
    expect(observed[0].target).toBe(trigger);
});

test.serial("confirm executes the link without firing trigger listeners twice", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    let triggerClicks = 0;
    let confirmedClicks = 0;

    trigger.addEventListener("click", () => triggerClicks++);
    document.body.addEventListener("click", () => confirmedClicks++);

    clickWith(trigger);
    mounted.controller.confirm();
    await wait(20);

    expect(triggerClicks).toBe(1);
    expect(confirmedClicks).toBe(1);
    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("generic button listeners run only after confirmation", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = "Archive";
    trigger.replaceWith(button);
    let clicks = 0;
    button.addEventListener("click", () => clicks++);

    clickWith(button);

    expect(clicks).toBe(0);
    expect(mounted.controller.isOpen).toBe(true);

    await mounted.controller.confirm();

    expect(clicks).toBe(1);
    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("href-less anchor listeners run only after confirmation", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const anchor = document.createElement("a");
    anchor.dataset.action = "items#destroy";
    anchor.textContent = "Delete";
    trigger.replaceWith(anchor);
    let clicks = 0;
    anchor.addEventListener("click", () => clicks++);

    clickWith(anchor);

    expect(clicks).toBe(0);
    expect(mounted.controller.isOpen).toBe(true);

    await mounted.controller.confirm();

    expect(clicks).toBe(1);
    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("confirm waits for the actual exit motion before re-issuing the click", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const modal = document.querySelector('[data-alert-dialog-target="modal"]');
    const dialog = document.querySelector('[data-alert-dialog-target="dialog"]');
    const motion = fakeAnimation();
    let confirmedClickReached = false;

    modal.dataset.motion = "default";
    dialog.getAnimations = () => modal.dataset.state === "closed" ? [motion.animation] : [];
    document.body.addEventListener("click", () => confirmedClickReached = true);

    clickWith(trigger);
    await wait(0);
    const confirming = mounted.controller.confirm();
    await wait(0);

    expect(confirmedClickReached).toBe(false);
    expect(modal.hidden).toBe(false);
    expect(modal.hasAttribute("inert")).toBe(true);

    motion.finish();
    await confirming;

    expect(confirmedClickReached).toBe(true);
    expect(modal.hidden).toBe(true);
});

// --- cancel() closes without re-clicking ---

test.serial("cancel closes the dialog and clears the pending action", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const zone = document.getElementById("trigger-zone");
    trigger.removeAttribute("id");
    zone.removeAttribute("id");

    clickWith(trigger);
    mounted.controller.cancel();

    expect(mounted.controller.isOpen).toBe(false);
    expect(document.querySelector('[data-alert-dialog-target="modal"]').dataset.state).toBe("closed");
    expect(mounted.controller.pendingAction).toBeNull();
});

test.serial("closeForCache clears the pending action before snapshot", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const zone = document.getElementById("trigger-zone");
    trigger.removeAttribute("id");
    zone.removeAttribute("id");

    clickWith(trigger);
    mounted.controller.closeForCache();

    expect(mounted.controller.pendingAction).toBeNull();
});

// --- click outside ---

test.serial("clicking the backdrop cancels the dialog when closeOnClickOutside is true", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const backdrop = document.querySelector('[data-alert-dialog-target="backdrop"]');

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    clickWith(backdrop);

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("clicking inside the dialog does NOT close it", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const dialog = document.querySelector('[data-alert-dialog-target="dialog"]');

    clickWith(trigger);

    // A bubbling click that originates inside the dialog reaches the modal-level
    // clickOutside handler but should be ignored (target is within the dialog).
    clickWith(dialog);

    expect(mounted.controller.isOpen).toBe(true);
});

// --- Escape key ---

test.serial("Escape key cancels the dialog when open", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("Escape key is a no-op when the dialog is closed", async () => {
    await mount();

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(false);
});

// --- propagation containment (modal lives inside an outer listener, e.g. a dropdown) ---

test.serial("confirm re-click bubbles past the dialog so ancestors can react", async () => {
    await mount();

    let ancestorClicks = 0;
    const spy = () => ancestorClicks++;
    document.body.addEventListener("click", spy);

    const trigger = document.getElementById("trigger");
    const confirmBtn = document.getElementById("confirm");

    clickWith(trigger); // first click → intercepted, does NOT bubble
    expect(mounted.controller.isOpen).toBe(true);
    expect(ancestorClicks).toBe(0);

    clickWith(confirmBtn);
    await wait(20);

    expect(mounted.controller.isOpen).toBe(false);
    // The resumed action reaches ancestor listeners, letting an enclosing
    // dropdown close after the modal has finished its own close transition.
    expect(ancestorClicks).toBe(1);

    document.body.removeEventListener("click", spy);
});

test.serial("cancel click does not bubble to ancestor click listeners while modal is open", async () => {
    await mount();

    let ancestorClicks = 0;
    const spy = () => {
        ancestorClicks++;
    };
    document.body.addEventListener("click", spy);

    const trigger = document.getElementById("trigger");
    const cancelBtn = document.getElementById("cancel");

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    const before = ancestorClicks;
    clickWith(cancelBtn);

    expect(mounted.controller.isOpen).toBe(false);
    expect(ancestorClicks).toBe(before);

    document.body.removeEventListener("click", spy);
});

test.serial("Escape does not reach other document keydown listeners while modal is open", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    let bubbleListenerSawEscape = false;
    const spy = (event) => {
        if (event.key === "Escape") bubbleListenerSawEscape = true;
    };
    document.addEventListener("keydown", spy);

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(mounted.controller.isOpen).toBe(false);
    expect(bubbleListenerSawEscape).toBe(false);

    document.removeEventListener("keydown", spy);
});

// --- disconnect cleanup ---

test.serial("permanent disconnect releases the pending action and closes the dialog", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const zone = document.getElementById("trigger-zone");
    trigger.removeAttribute("id");
    zone.removeAttribute("id");

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    mounted.controller.disconnect();
    await wait(0);

    expect(mounted.controller.isOpen).toBe(false);
    expect(mounted.controller.pendingAction).toBeNull();

    // Subsequent Escape no longer reaches the (disconnected) controller.
    // Just verify no throw.
    expect(() => {
        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
    }).not.toThrow();
});

async function mount() {
    mounted = await mountController("alert-dialog", AlertDialogController, HTML);
}

function setViewportWidth(innerWidth, clientWidth) {
    Object.defineProperty(window, "innerWidth", { configurable: true, value: innerWidth });
    Object.defineProperty(document.documentElement, "clientWidth", { configurable: true, value: clientWidth });
}

function fakeAnimation() {
    const finished = deferred();

    return {
        animation: {
            effect: { getComputedTiming: () => ({ endTime: 100 }) },
            finished: finished.promise,
            playState: "running",
        },
        finish: () => finished.resolve(),
    };
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}
