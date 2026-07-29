import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import AlertDialogController from "../../resources/js/controllers/alert_dialog_controller.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

const LOADING_TEMPLATE_HTML = `
    <div data-controller="modal">
        <a href="/items/1/edit" data-turbo-frame="modal-frame">Edit</a>
        <a href="/items/1/comments" data-turbo-frame="modal-frame" data-loading-template="#per-link-skeleton">Comments</a>

        <template id="per-link-skeleton">
            <div class="comments-skeleton">Loading comments...</div>
        </template>

        <div
            data-modal-target="modal"
            data-modal-lock-scroll-class="overflow-hidden"
            data-state="closed"
            data-motion="none"
            hidden inert
        >
            <div data-modal-target="backdrop"></div>
            <div data-modal-target="dialog">
                <turbo-frame id="modal-frame" data-modal-target="dynamicContent"></turbo-frame>
                <template data-modal-target="loadingTemplate">
                    <div class="loading-state">Loading...</div>
                </template>
            </div>
        </div>
    </div>
`;

test.serial("connect applies visible state when the overlay is pre-rendered open", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div
                data-controller="modal"
                data-modal-lock-scroll-class="overflow-hidden"
            >
                <div data-modal-target="modal" data-state="open" data-motion="none">
                    <div data-modal-target="backdrop" class="backdrop-hidden"></div>
                    <div data-modal-target="dialog" class="dialog-hidden">
                        <p>Modal content</p>
                    </div>
                </div>
            </div>
        `,
    );

    const modal = document.querySelector('[data-modal-target="modal"]');
    const backdrop = document.querySelector('[data-modal-target="backdrop"]');
    const dialog = document.querySelector('[data-modal-target="dialog"]');

    expect(mounted.controller.isOpen).toBe(true);
    expect(modal.hidden).toBe(false);
    expect(modal.dataset.state).toBe("open");
    expect(modal.hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

test.serial("injects the default loading template when turbo:before-fetch-request fires on the dynamic content", async () => {
    mounted = await mountController("modal", ModalController, LOADING_TEMPLATE_HTML);

    const editLink = document.querySelector('a[href="/items/1/edit"]');
    const frame = document.querySelector('#modal-frame');

    dispatchEvent(editLink, "click");
    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));

    expect(frame.innerHTML).toContain("Loading...");
});

test.serial("injects the per-link template when the trigger declares data-loading-template", async () => {
    mounted = await mountController("modal", ModalController, LOADING_TEMPLATE_HTML);

    const commentsLink = document.querySelector('a[href="/items/1/comments"]');
    const frame = document.querySelector('#modal-frame');

    dispatchEvent(commentsLink, "click");
    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));

    expect(frame.innerHTML).toContain("Loading comments...");
    expect(frame.innerHTML).not.toContain("Loading...");
});

test.serial("skips template injection when the fetch request targets a different frame", async () => {
    mounted = await mountController("modal", ModalController, LOADING_TEMPLATE_HTML);

    const frame = document.querySelector('#modal-frame');
    const otherFrame = document.createElement("turbo-frame");
    otherFrame.id = "other-frame";
    document.body.appendChild(otherFrame);

    otherFrame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));

    expect(frame.innerHTML).toBe("");
});

test.serial("skips template injection when the modal is already open", async () => {
    mounted = await mountController("modal", ModalController, LOADING_TEMPLATE_HTML);
    const frame = document.querySelector('#modal-frame');

    frame.innerHTML = "<p>existing content</p>";
    mounted.controller.open();
    await wait(20);

    expect(mounted.controller.isOpen).toBe(true);
    const before = frame.innerHTML;

    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));

    expect(frame.innerHTML).toBe(before);
    expect(frame.innerHTML).not.toContain("Loading...");
});

test.serial("defers an empty turbo stream update for the modal root until after close animation", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div
                id="modal"
                data-controller="modal"
                data-modal-lock-scroll-class="overflow-hidden"
            >
                <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-modal-target="backdrop"></div>
                    <div data-modal-target="dialog">
                        <p>Modal content</p>
                    </div>
                </div>
            </div>
        `,
    );

    mounted.controller.open();
    await wait(20);

    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "modal");
    stream.innerHTML = "<template></template>";

    let rendered = false;
    stream.performAction = () => {
        rendered = true;
        mounted.root.innerHTML = stream.querySelector("template").innerHTML;
    };

    document.body.appendChild(stream);
    stream.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(rendered).toBe(false);
    expect(mounted.root.innerHTML).toContain("Modal content");

    await wait(20);

    expect(rendered).toBe(true);
    expect(mounted.root.innerHTML).toBe("");

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab" }));
    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
});

test.serial("defers an empty turbo stream update for dynamic content until after close animation", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div
                id="modal-shell"
                data-controller="modal"
                data-modal-lock-scroll-class="overflow-hidden"
            >
                <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-modal-target="backdrop"></div>
                    <div data-modal-target="dialog">
                        <turbo-frame id="modal" data-modal-target="dynamicContent">
                            <p>Modal content</p>
                        </turbo-frame>
                    </div>
                </div>
            </div>
        `,
    );

    mounted.controller.open();
    await wait(20);

    const frame = document.querySelector("#modal");
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "modal");
    stream.innerHTML = "<template></template>";

    let rendered = false;
    stream.performAction = () => {
        rendered = true;
        frame.innerHTML = stream.querySelector("template").innerHTML;
    };

    document.body.appendChild(stream);
    stream.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));

    expect(rendered).toBe(false);
    expect(frame.innerHTML).toContain("Modal content");

    await wait(20);

    expect(rendered).toBe(true);
    expect(frame.innerHTML).toBe("");
});

test.serial("Escape closes only the top modal when modals are nested", async () => {
    mounted = await mountMultipleControllers({ modal: ModalController }, `
        <div id="outer" data-controller="modal"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="outer-trigger" data-action="modal#open">Open outer</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="outer-close" data-action="modal#close">Close outer</button>

                    <div id="inner" data-controller="modal"
                         data-modal-lock-scroll-class="overflow-hidden">
                        <button id="inner-trigger" data-action="modal#open">Open inner</button>
                        <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                            <div data-modal-target="backdrop"></div>
                            <div data-modal-target="dialog">
                                <button id="inner-close" data-action="modal#close">Close inner</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    const outer = mounted.getController("modal", document.getElementById("outer"));
    const inner = mounted.getController("modal", document.getElementById("inner"));

    outer.open({ target: document.getElementById("outer-trigger") });
    await wait(10);
    inner.open({ target: document.getElementById("inner-trigger") });
    await wait(10);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(inner.isOpen).toBe(false);
    expect(outer.isOpen).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(outer.isOpen).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);
});

test.serial("AlertDialog opened inside a modal handles Escape without closing the modal", async () => {
    mounted = await mountMultipleControllers({ modal: ModalController, "alert-dialog": AlertDialogController }, `
        <div id="modal" data-controller="modal"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="modal-close" data-action="modal#close">Close modal</button>

                    <div id="confirm" data-controller="alert-dialog"
                         data-alert-dialog-lock-scroll-class="overflow-hidden">
                        <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                        <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" data-action="click->alert-dialog#clickOutside" hidden inert>
                            <div data-alert-dialog-target="backdrop"></div>
                            <div data-alert-dialog-target="dialog">
                                <button id="cancel" data-action="alert-dialog#cancel">Cancel</button>
                                <button id="confirm-action" data-action="alert-dialog#confirm">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    const modal = mounted.getController("modal", document.getElementById("modal"));
    const alertDialog = mounted.getController("alert-dialog", document.getElementById("confirm"));

    modal.open({ target: document.getElementById("modal-trigger") });
    await wait(10);
    document.getElementById("delete").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(alertDialog.isOpen).toBe(false);
    expect(alertDialog.pendingElement).toBeNull();
    expect(modal.isOpen).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
});

test.serial("clicking the backdrop does not block the next trigger click", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div id="modal" data-controller="modal"
                 data-modal-lock-scroll-class="overflow-hidden">
                <button id="modal-trigger" data-action="modal#open">Open modal</button>
                <div data-modal-target="modal" data-state="closed" data-motion="none" data-action="click->modal#clickOutside" hidden inert>
                    <div id="modal-backdrop" data-modal-target="backdrop"></div>
                    <div data-modal-target="dialog">
                        <button id="inside">Inside</button>
                    </div>
                </div>
            </div>
        `,
    );

    const modal = mounted.controller;
    const trigger = document.getElementById("modal-trigger");
    const backdrop = document.getElementById("modal-backdrop");

    modal.dialogTarget.getBoundingClientRect = () => ({ top: 100, right: 200, bottom: 200, left: 100 });

    trigger.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 }));
    await wait(10);

    expect(modal.isOpen).toBe(true);

    backdrop.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0, clientX: 10, clientY: 10 }));
    await wait(10);

    expect(modal.isOpen).toBe(false);

    trigger.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 }));
    await wait(10);

    expect(modal.isOpen).toBe(true);
});

test.serial("Turbo cache synchronously cancels a pending exit", async () => {
    mounted = await mountController("modal", ModalController, `
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="trigger">Open</button>
            <div data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);
    const modal = mounted.controller.modalTarget;
    const motion = fakeAnimation();

    await mounted.controller.open({ currentTarget: document.getElementById("trigger") });
    mounted.controller.dialogTarget.getAnimations = () => modal.dataset.state === "closed" ? [motion.animation] : [];
    const closing = mounted.controller.close();
    await wait(0);

    expect(modal.hidden).toBe(false);

    mounted.controller.closeForCache();

    expect(modal.hidden).toBe(true);
    expect(modal.hasAttribute("inert")).toBe(true);
    expect(document.body.classList.contains("overflow-hidden")).toBe(false);

    motion.finish();
    expect(await closing).toBe(false);
});

test.serial("Turbo morph rebuilds an open overlay around replacement targets", async () => {
    mounted = await mountController("modal", ModalController, `
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="trigger">Open</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);

    const trigger = document.getElementById("trigger");
    let openedEvents = 0;
    mounted.root.addEventListener("modal:opened", () => openedEvents++);
    await mounted.controller.open({ currentTarget: trigger });
    const previous = mounted.controller.modalTarget;
    const replacement = document.createElement("div");
    replacement.setAttribute("data-modal-target", "modal");
    replacement.dataset.state = "closed";
    replacement.dataset.motion = "none";
    replacement.hidden = true;
    replacement.setAttribute("inert", "");
    replacement.innerHTML = `
        <div data-modal-target="backdrop"></div>
        <div data-modal-target="dialog"><button>Replacement</button></div>
    `;

    previous.replaceWith(replacement);
    mounted.controller.modalTargetDisconnected(previous);
    mounted.controller.modalTargetConnected(replacement);
    await waitUntil(() => mounted.controller.modalTarget === replacement && replacement.dataset.state === "open");

    expect(mounted.controller.modalTarget).toBe(replacement);
    expect(mounted.controller.isOpen).toBe(true);
    expect(replacement.dataset.state).toBe("open");
    expect(replacement.hidden).toBe(false);
    expect(replacement.hasAttribute("inert")).toBe(false);
    expect(document.body.classList.contains("overflow-hidden")).toBe(true);
    expect(openedEvents).toBe(1);

    await mounted.controller.close();
    expect(document.activeElement).toBe(trigger);
});

test.serial("target morph during exit preserves a deferred Turbo Stream", async () => {
    mounted = await mountController("modal", ModalController, `
        <div id="modal-shell" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <div data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <turbo-frame id="modal-frame" data-modal-target="dynamicContent"><p>Content</p></turbo-frame>
                </div>
            </div>
        </div>
    `);
    await mounted.controller.open();
    const motion = fakeAnimation();
    mounted.controller.dialogTarget.getAnimations = () => mounted.controller.modalTarget.dataset.state === "closed" ? [motion.animation] : [];
    const frame = document.getElementById("modal-frame");
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "modal-frame");
    stream.innerHTML = "<template></template>";
    let rendered = false;
    stream.performAction = () => {
        rendered = true;
        frame.innerHTML = "";
    };

    document.body.appendChild(stream);
    stream.dispatchEvent(new CustomEvent("turbo:before-stream-render", { bubbles: true }));
    mounted.controller.backdropTarget.replaceWith(mounted.controller.backdropTarget.cloneNode(true));
    await wait(0);

    expect(rendered).toBe(false);

    motion.finish();
    await wait(10);

    expect(rendered).toBe(true);
    expect(frame.innerHTML).toBe("");
});

test.serial("close during an opening target morph is not undone by stale reopen intent", async () => {
    mounted = await mountController("modal", ModalController, `
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="trigger">Open</button>
            <div data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);
    const motion = fakeAnimation();
    mounted.controller.dialogTarget.getAnimations = () => mounted.controller.modalTarget.dataset.state === "open" ? [motion.animation] : [];

    const opening = mounted.controller.open({ currentTarget: document.getElementById("trigger") });
    await wait(0);
    mounted.controller.backdropTarget.replaceWith(mounted.controller.backdropTarget.cloneNode(true));
    await wait(0);
    const closing = mounted.controller.close();

    motion.finish();
    await Promise.all([opening, closing]);
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
    expect(mounted.controller.overlay.phase).toBe("closed");
    expect(mounted.controller.modalTarget.dataset.state).toBe("closed");
    expect(mounted.controller.modalTarget.hidden).toBe(true);
});

test.serial("morphing an outer modal preserves the nested modal as the top overlay", async () => {
    mounted = await mountMultipleControllers({ modal: ModalController }, `
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="outer-trigger">Open outer</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <div id="inner" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
                        <button id="inner-trigger">Open inner</button>
                        <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                            <div data-modal-target="backdrop"></div>
                            <div data-modal-target="dialog"><button>Close inner</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    const outerRoot = document.getElementById("outer");
    const innerRoot = document.getElementById("inner");
    const outer = mounted.getController("modal", outerRoot);
    const inner = mounted.getController("modal", innerRoot);

    await outer.open({ currentTarget: document.getElementById("outer-trigger") });
    await inner.open({ currentTarget: document.getElementById("inner-trigger") });
    outer.backdropTarget.replaceWith(outer.backdropTarget.cloneNode(true));
    await wait(10);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(0);

    expect(inner.isOpen).toBe(false);
    expect(outer.isOpen).toBe(true);
});

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

async function waitUntil(predicate, timeout = 2_000) {
    const deadline = Date.now() + timeout;

    while (!predicate()) {
        if (Date.now() >= deadline) throw new Error("Timed out waiting for Modal state");
        await wait(5);
    }
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}
