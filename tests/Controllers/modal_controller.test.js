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
            data-modal-hidden-class="hidden"
            data-modal-visible-class="visible"
            data-modal-backdrop-hidden-class="backdrop-hidden"
            data-modal-backdrop-visible-class="backdrop-visible"
            data-modal-dialog-hidden-class="dialog-hidden"
            data-modal-dialog-visible-class="dialog-visible"
            data-modal-lock-scroll-class="overflow-hidden"
            hidden
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
                data-modal-hidden-class="hidden"
                data-modal-visible-class="visible"
                data-modal-backdrop-hidden-class="backdrop-hidden"
                data-modal-backdrop-visible-class="backdrop-visible"
                data-modal-dialog-hidden-class="dialog-hidden"
                data-modal-dialog-visible-class="dialog-visible"
                data-modal-lock-scroll-class="overflow-hidden"
            >
                <div data-modal-target="modal" data-open="true" class="hidden" hidden>
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
    expect(modal.dataset.open).toBe("true");
    expect(modal.classList.contains("visible")).toBe(true);
    expect(backdrop.classList.contains("backdrop-visible")).toBe(true);
    expect(dialog.classList.contains("dialog-visible")).toBe(true);
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
                data-modal-open-duration-value="1"
                data-modal-close-duration-value="1"
                data-modal-hidden-class="hidden"
                data-modal-visible-class="visible"
                data-modal-backdrop-hidden-class="backdrop-hidden"
                data-modal-backdrop-visible-class="backdrop-visible"
                data-modal-dialog-hidden-class="dialog-hidden"
                data-modal-dialog-visible-class="dialog-visible"
                data-modal-lock-scroll-class="overflow-hidden"
            >
                <div data-modal-target="modal" hidden>
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
                data-modal-open-duration-value="1"
                data-modal-close-duration-value="1"
                data-modal-hidden-class="hidden"
                data-modal-visible-class="visible"
                data-modal-backdrop-hidden-class="backdrop-hidden"
                data-modal-backdrop-visible-class="backdrop-visible"
                data-modal-dialog-hidden-class="dialog-hidden"
                data-modal-dialog-visible-class="dialog-visible"
                data-modal-lock-scroll-class="overflow-hidden"
            >
                <div data-modal-target="modal" hidden>
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
             data-modal-open-duration-value="1"
             data-modal-close-duration-value="1"
             data-modal-hidden-class="hidden"
             data-modal-visible-class="visible"
             data-modal-backdrop-hidden-class="backdrop-hidden"
             data-modal-backdrop-visible-class="backdrop-visible"
             data-modal-dialog-hidden-class="dialog-hidden"
             data-modal-dialog-visible-class="dialog-visible"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="outer-trigger" data-action="modal#open">Open outer</button>
            <div data-modal-target="modal" hidden>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="outer-close" data-action="modal#close">Close outer</button>

                    <div id="inner" data-controller="modal"
                         data-modal-open-duration-value="1"
                         data-modal-close-duration-value="1"
                         data-modal-hidden-class="hidden"
                         data-modal-visible-class="visible"
                         data-modal-backdrop-hidden-class="backdrop-hidden"
                         data-modal-backdrop-visible-class="backdrop-visible"
                         data-modal-dialog-hidden-class="dialog-hidden"
                         data-modal-dialog-visible-class="dialog-visible"
                         data-modal-lock-scroll-class="overflow-hidden">
                        <button id="inner-trigger" data-action="modal#open">Open inner</button>
                        <div data-modal-target="modal" hidden>
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
             data-modal-open-duration-value="1"
             data-modal-close-duration-value="1"
             data-modal-hidden-class="hidden"
             data-modal-visible-class="visible"
             data-modal-backdrop-hidden-class="backdrop-hidden"
             data-modal-backdrop-visible-class="backdrop-visible"
             data-modal-dialog-hidden-class="dialog-hidden"
             data-modal-dialog-visible-class="dialog-visible"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" hidden>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="modal-close" data-action="modal#close">Close modal</button>

                    <div id="confirm" data-controller="alert-dialog"
                         data-alert-dialog-hidden-class="hidden"
                         data-alert-dialog-visible-class="visible"
                         data-alert-dialog-backdrop-hidden-class="backdrop-hidden"
                         data-alert-dialog-backdrop-visible-class="backdrop-visible"
                         data-alert-dialog-dialog-hidden-class="dialog-hidden"
                         data-alert-dialog-dialog-visible-class="dialog-visible"
                         data-alert-dialog-lock-scroll-class="overflow-hidden"
                         data-alert-dialog-open-duration-value="1"
                         data-alert-dialog-close-duration-value="1">
                        <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                        <div data-alert-dialog-target="modal" data-open="false" data-action="click->alert-dialog#clickOutside" hidden>
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
