import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ConfirmDialogController from "../../resources/js/controllers/confirm_dialog_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

const HTML = `
    <div data-controller="confirm-dialog"
         data-confirm-dialog-hidden-class="hidden"
         data-confirm-dialog-visible-class="visible"
         data-confirm-dialog-backdrop-hidden-class="bd-hidden"
         data-confirm-dialog-backdrop-visible-class="bd-visible"
         data-confirm-dialog-dialog-hidden-class="dlg-hidden"
         data-confirm-dialog-dialog-visible-class="dlg-visible"
         data-confirm-dialog-lock-scroll-class="overflow-hidden"
         data-confirm-dialog-close-on-click-outside-value="true"
         data-confirm-dialog-open-duration-value="1"
         data-confirm-dialog-close-duration-value="1">
        <a href="/items/1" data-action="click->confirm-dialog#intercept" id="trigger">Delete</a>

        <div data-confirm-dialog-target="modal"
             data-action="click->confirm-dialog#clickOutside"
             hidden>
            <div data-confirm-dialog-target="backdrop"></div>
            <div data-confirm-dialog-target="dialog">
                <button id="cancel" data-action="click->confirm-dialog#cancel">Cancel</button>
                <button id="confirm" data-action="click->confirm-dialog#confirm">OK</button>
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
    const modal = document.querySelector('[data-confirm-dialog-target="modal"]');

    expect(modal.hidden).toBe(true);

    const defaultPrevented = !clickWith(trigger);

    expect(defaultPrevented).toBe(true);
    expect(modal.hidden).toBe(false);
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

// --- visible/hidden classes applied after rAF ---

test.serial("after open, modal gets the visible class and lock-scroll is applied to body", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const modal = document.querySelector('[data-confirm-dialog-target="modal"]');

    clickWith(trigger);
    await wait(10); // rAF tick

    expect(modal.classList.contains("visible")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

// --- confirm() re-clicks the original trigger ---

test.serial("confirm re-issues the click on the original trigger and lets it through", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    let secondClickReached = false;
    trigger.addEventListener("click", () => {
        if (mounted.controller.isOpen === false && mounted.controller.confirmed === false) {
            // The "second" click is the re-click after confirm; intercept consumed
            // confirmed=true and reset it to false right before this handler runs.
            secondClickReached = true;
        }
    });

    clickWith(trigger);                          // first click → intercepted
    mounted.controller.confirm();
    await wait(20);                              // wait for closeDuration + re-click

    expect(secondClickReached).toBe(true);
    expect(mounted.controller.isOpen).toBe(false);
});

// --- cancel() closes without re-clicking ---

test.serial("cancel closes the dialog and clears the pending element", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    clickWith(trigger);
    mounted.controller.cancel();

    expect(mounted.controller.isOpen).toBe(false);
    expect(mounted.controller.pendingElement).toBeNull();
});

// --- click outside ---

test.serial("clicking the backdrop cancels the dialog when closeOnClickOutside is true", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const backdrop = document.querySelector('[data-confirm-dialog-target="backdrop"]');

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    clickWith(backdrop);

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("clicking inside the dialog does NOT close it", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const dialog = document.querySelector('[data-confirm-dialog-target="dialog"]');

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
    const spy = (event) => {
        if (event.target.id === "trigger") ancestorClicks++;
    };
    document.body.addEventListener("click", spy);

    const trigger = document.getElementById("trigger");
    const confirmBtn = document.getElementById("confirm");

    clickWith(trigger); // first click → intercepted, does NOT bubble
    expect(mounted.controller.isOpen).toBe(true);
    expect(ancestorClicks).toBe(0);

    clickWith(confirmBtn);
    await wait(20); // closeDuration (1ms) + re-click

    expect(mounted.controller.isOpen).toBe(false);
    // The synthetic re-click on the original trigger reaches ancestor listeners,
    // letting an enclosing dropdown (or other UI) close gracefully after the
    // modal has finished its own close transition.
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

test.serial("disconnect detaches the keydown listener and closes an open dialog", async () => {
    await mount();
    const trigger = document.getElementById("trigger");

    clickWith(trigger);
    expect(mounted.controller.isOpen).toBe(true);

    mounted.controller.disconnect();

    expect(mounted.controller.isOpen).toBe(false);

    // Subsequent Escape no longer reaches the (disconnected) controller.
    // Just verify no throw.
    expect(() => {
        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
    }).not.toThrow();
});

async function mount() {
    mounted = await mountController("confirm-dialog", ConfirmDialogController, HTML);
}
