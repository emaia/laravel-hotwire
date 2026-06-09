import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
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
