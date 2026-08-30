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

const SHARED_HTML = `
    <div data-controller="alert-dialog"
         data-alert-dialog-shared-value="true"
         data-alert-dialog-lock-scroll-class="overflow-hidden">
        <div data-slot="alert-dialog-trigger"
             data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
            <button id="first-trigger"
                    type="button"
                    data-alert-dialog-trigger
                    data-alert-dialog-title="Delete item?"
                    data-alert-dialog-description="This item cannot be recovered."
                    data-alert-dialog-confirm-label="Delete"
                    data-alert-dialog-cancel-label="Keep"
                    data-alert-dialog-confirm-variant="danger"
                    data-alert-dialog-cancel-variant="ghost">Delete first</button>
            <button id="second-trigger" type="button" data-alert-dialog-trigger>Delete second</button>
            <button id="unmarked-trigger" type="button">Leave alone</button>
        </div>

        <div data-alert-dialog-target="modal"
             data-state="closed"
             data-motion="none"
             data-action="click->alert-dialog#clickOutside"
             aria-labelledby="shared-title"
             aria-describedby="shared-description"
             hidden inert>
            <div data-alert-dialog-target="backdrop"></div>
            <div data-alert-dialog-target="dialog">
                <h2 id="shared-title" data-alert-dialog-target="title">Confirm action</h2>
                <p id="shared-description" data-alert-dialog-target="description">This action cannot be undone.</p>
                <button id="cancel" type="button" data-variant="secondary"
                        data-alert-dialog-target="cancel" data-action="click->alert-dialog#cancel">Cancel</button>
                <button id="confirm" type="button" data-variant="destructive"
                        data-alert-dialog-target="confirm" data-action="click->alert-dialog#confirm">Confirm</button>
            </div>
        </div>
    </div>
`;

function clickWith(element, init = {}) {
    return element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, ...init }));
}

// --- shared host ---

test.serial("shared host intercepts only marked triggers", async () => {
    await mountShared();
    const unmarked = document.getElementById("unmarked-trigger");
    let clicks = 0;
    unmarked.addEventListener("click", () => clicks++);

    const defaultPrevented = !clickWith(unmarked);

    expect(defaultPrevented).toBe(false);
    expect(clicks).toBe(1);
    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("shared host ignores disabled marked triggers", async () => {
    await mountShared();
    const trigger = document.getElementById("first-trigger");
    trigger.setAttribute("aria-disabled", "true");

    const defaultPrevented = !clickWith(trigger);

    expect(defaultPrevented).toBe(true);
    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("shared host applies trigger content and restores its defaults after cancel", async () => {
    await mountShared();

    clickWith(document.getElementById("first-trigger"));

    expect(document.getElementById("shared-title").textContent).toBe("Delete item?");
    expect(document.getElementById("shared-description").textContent).toBe("This item cannot be recovered.");
    expect(document.getElementById("cancel").textContent).toBe("Keep");
    expect(document.getElementById("cancel").dataset.variant).toBe("ghost");
    expect(document.getElementById("confirm").textContent).toBe("Delete");
    expect(document.getElementById("confirm").dataset.variant).toBe("danger");

    await mounted.controller.cancel();

    expect(document.getElementById("shared-title").textContent).toBe("Confirm action");
    expect(document.getElementById("shared-description").textContent).toBe("This action cannot be undone.");
    expect(document.getElementById("cancel").textContent).toBe("Cancel");
    expect(document.getElementById("cancel").dataset.variant).toBe("secondary");
    expect(document.getElementById("confirm").textContent).toBe("Confirm");
    expect(document.getElementById("confirm").dataset.variant).toBe("destructive");
});

test.serial("shared host removes an empty dynamic description from the accessible description", async () => {
    await mountShared();
    const trigger = document.getElementById("first-trigger");
    const modal = document.querySelector('[data-alert-dialog-target="modal"]');
    const description = document.getElementById("shared-description");
    trigger.dataset.alertDialogTitle = "";
    trigger.dataset.alertDialogDescription = "";
    trigger.dataset.alertDialogConfirmLabel = "";

    clickWith(trigger);

    expect(document.getElementById("shared-title").textContent).toBe("Confirm action");
    expect(description.hidden).toBe(true);
    expect(modal.hasAttribute("aria-describedby")).toBe(false);
    expect(document.getElementById("confirm").textContent).toBe("Confirm");

    await mounted.controller.cancel();

    expect(description.hidden).toBe(false);
    expect(modal.getAttribute("aria-describedby")).toBe("shared-description");
});

test.serial("shared host reveals a trigger title when its default title is empty", async () => {
    mounted = await mountController(
        "alert-dialog",
        AlertDialogController,
        SHARED_HTML.replace(
            'id="shared-title" data-alert-dialog-target="title">Confirm action',
            'id="shared-title" data-alert-dialog-target="title" hidden>',
        ),
    );
    const title = document.getElementById("shared-title");

    clickWith(document.getElementById("first-trigger"));

    expect(title.hidden).toBe(false);
    expect(title.textContent).toBe("Delete item?");

    await mounted.controller.cancel();

    expect(title.hidden).toBe(true);
    expect(title.textContent).toBe("");
});

test.serial("shared host supplies an accessible title when its default and trigger titles are empty", async () => {
    mounted = await mountController(
        "alert-dialog",
        AlertDialogController,
        SHARED_HTML.replace(
            'id="shared-title" data-alert-dialog-target="title">Confirm action',
            'id="shared-title" data-alert-dialog-target="title" hidden>',
        ),
    );
    const modal = document.querySelector('[data-alert-dialog-target="modal"]');
    const title = document.getElementById("shared-title");

    clickWith(document.getElementById("second-trigger"));

    expect(title.hidden).toBe(false);
    expect(title.textContent).toBe("Confirm action");
    expect(modal.getAttribute("aria-labelledby")).toBe("shared-title");
});

test.serial("shared host uses an authored aria-label when its resolved title is empty", async () => {
    mounted = await mountController(
        "alert-dialog",
        AlertDialogController,
        SHARED_HTML
            .replace(
                'id="shared-title" data-alert-dialog-target="title">Confirm action',
                'id="shared-title" data-alert-dialog-target="title" hidden>',
            )
            .replace('aria-labelledby="shared-title"', 'aria-label="Confirm deletion" aria-labelledby="shared-title"'),
    );
    const modal = document.querySelector('[data-alert-dialog-target="modal"]');
    const title = document.getElementById("shared-title");

    clickWith(document.getElementById("second-trigger"));

    expect(title.hidden).toBe(true);
    expect(title.textContent).toBe("");
    expect(modal.getAttribute("aria-label")).toBe("Confirm deletion");
    expect(modal.hasAttribute("aria-labelledby")).toBe(false);
});

test.serial("shared host keeps the first pending action while open", async () => {
    await mountShared();
    const first = document.getElementById("first-trigger");
    const second = document.getElementById("second-trigger");
    const clicked = [];
    first.addEventListener("click", () => clicked.push("first"));
    second.addEventListener("click", () => clicked.push("second"));

    clickWith(first);
    const secondPrevented = !clickWith(second);
    expect(document.getElementById("shared-title").textContent).toBe("Delete item?");
    await mounted.controller.confirm();

    expect(secondPrevented).toBe(true);
    expect(clicked).toEqual(["first"]);
});

test.serial("shared host replaces a stale pending action after a failed close leaves it closed", async () => {
    await mountShared();
    const first = document.getElementById("first-trigger");
    const second = document.getElementById("second-trigger");
    const clicked = [];
    first.addEventListener("click", () => clicked.push("first"));
    second.addEventListener("click", () => clicked.push("second"));

    clickWith(first);
    const overlay = mounted.controller.overlay;
    const close = overlay.close;
    overlay.close = async () => {
        overlay.closeNow({ restoreFocus: false });

        return false;
    };
    await mounted.controller.confirm();
    overlay.close = close;

    expect(mounted.controller.isOpen).toBe(false);
    expect(mounted.controller.pendingAction).not.toBeNull();

    clickWith(second);
    await mounted.controller.confirm();

    expect(clicked).toEqual(["second"]);
});

test.serial("a nested shared host owns its marked triggers", async () => {
    mounted = await mountControllers("alert-dialog", AlertDialogController, `
        <div id="outer-host" data-controller="alert-dialog" data-alert-dialog-shared-value="true"
             data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
            <div id="inner-host" data-controller="alert-dialog" data-alert-dialog-shared-value="true"
                 data-alert-dialog-lock-scroll-class="overflow-hidden">
                <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                    <button id="nested-trigger" type="button" data-alert-dialog-trigger>Delete</button>
                </div>
                ${sharedOverlay("inner")}
            </div>
            </div>
            ${sharedOverlay("outer")}
        </div>
    `);
    const [outer, inner] = mounted.controllers;

    clickWith(document.getElementById("nested-trigger"));

    expect(outer.isOpen).toBe(false);
    expect(inner.isOpen).toBe(true);
    expect(document.getElementById("outer-modal").hidden).toBe(true);
    expect(document.getElementById("inner-modal").hidden).toBe(false);
});

test.serial("shared host drops an action moved under another host before confirmation", async () => {
    mounted = await mountControllers("alert-dialog", AlertDialogController, `
        <div id="first-host" data-controller="alert-dialog" data-alert-dialog-shared-value="true"
             data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                <button id="moving-trigger" type="button" data-alert-dialog-trigger>Delete</button>
            </div>
            ${sharedOverlay("first")}
        </div>
        <div id="second-host" data-controller="alert-dialog" data-alert-dialog-shared-value="true"
             data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept"></div>
            ${sharedOverlay("second")}
        </div>
    `);
    const [first] = mounted.controllers;
    const trigger = document.getElementById("moving-trigger");
    let clicks = 0;
    let dropped = 0;
    trigger.addEventListener("click", () => clicks++);
    mounted.roots[0].addEventListener("alert-dialog:dropped", () => dropped++);

    clickWith(trigger);
    mounted.roots[1].querySelector('[data-action*="interceptCapture"]').append(trigger);
    await first.confirm();

    expect(clicks).toBe(0);
    expect(dropped).toBe(1);
});

test.serial("shared host recaptures defaults after a permanent disconnect", async () => {
    await mountShared();
    const title = document.getElementById("shared-title");

    clickWith(document.getElementById("first-trigger"));
    mounted.controller.disconnect();
    await wait(0);
    title.textContent = "Server default";
    mounted.controller.connect();

    clickWith(document.getElementById("second-trigger"));

    expect(title.textContent).toBe("Server default");
});

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

test.serial("capture interception fails closed inside an ancestor link", async () => {
    mounted = await mountController("alert-dialog", AlertDialogController, `
        <a href="/items/1">
            <div data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                    <span id="nested-trigger">Delete</span>
                </div>

                <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-alert-dialog-target="backdrop"></div>
                    <div data-alert-dialog-target="dialog">
                        <button data-action="click->alert-dialog#cancel">Cancel</button>
                        <button data-action="click->alert-dialog#confirm">Confirm</button>
                    </div>
                </div>
            </div>
        </a>
    `);

    const defaultPrevented = !clickWith(document.getElementById("nested-trigger"));

    expect(defaultPrevented).toBe(true);
    expect(mounted.controller.isOpen).toBe(true);
    expect(mounted.controller.pendingAction?.kind).toBe("click");
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

test.serial("confirm dispatches dropped when the captured action no longer matches", async () => {
    await mount();
    const trigger = document.getElementById("trigger");
    const dropped = [];
    mounted.root.addEventListener("alert-dialog:dropped", (event) => dropped.push(event));

    clickWith(trigger);
    trigger.href = "/items/2";

    await mounted.controller.confirm();

    expect(dropped).toHaveLength(1);
    expect(dropped[0].target).toBe(mounted.root);
    expect(dropped[0].detail).toEqual({ kind: "link", triggerId: "trigger" });
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

async function mountShared() {
    mounted = await mountController("alert-dialog", AlertDialogController, SHARED_HTML);
}

function sharedOverlay(id) {
    return `
        <div id="${id}-modal" data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
            <div data-alert-dialog-target="backdrop"></div>
            <div data-alert-dialog-target="dialog">
                <h2 data-alert-dialog-target="title">Confirm action</h2>
                <p data-alert-dialog-target="description">This action cannot be undone.</p>
                <button type="button" data-alert-dialog-target="cancel" data-action="alert-dialog#cancel">Cancel</button>
                <button type="button" data-alert-dialog-target="confirm" data-action="alert-dialog#confirm">Confirm</button>
            </div>
        </div>
    `;
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
