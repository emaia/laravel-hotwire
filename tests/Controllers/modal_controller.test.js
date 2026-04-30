import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("injects the default loading template for a matching turbo-frame link", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div data-controller="modal">
                <a href="/items/1/edit" data-turbo-frame="modal-frame">Edit</a>

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
        `,
    );

    const link = document.querySelector('a[data-turbo-frame="modal-frame"]');
    const frame = document.querySelector("turbo-frame");

    dispatchEvent(link, "click");
    await wait(0);
    await wait(0);

    expect(frame.innerHTML).toContain("Loading...");
});

test.serial("does not let an unrelated turbo response suppress the loading template", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div data-controller="modal">
                <a href="/items/1/edit" data-turbo-frame="modal-frame">Edit</a>
                <turbo-frame id="other-frame"></turbo-frame>

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
        `,
    );

    const link = document.querySelector('a[data-turbo-frame="modal-frame"]');
    const otherFrame = document.querySelector('#other-frame');
    const frame = document.querySelector('#modal-frame');

    dispatchEvent(link, "click");
    otherFrame.dispatchEvent(new CustomEvent("turbo:before-fetch-response", { bubbles: true }));

    await wait(0);
    await wait(0);

    expect(frame.innerHTML).toContain("Loading...");
});

test.serial("skips the loading template when the matching frame response arrives first", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div data-controller="modal">
                <a href="/items/1/edit" data-turbo-frame="modal-frame">Edit</a>

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
        `,
    );

    const link = document.querySelector('a[data-turbo-frame="modal-frame"]');
    const frame = document.querySelector('#modal-frame');

    dispatchEvent(link, "click");
    frame.dispatchEvent(new CustomEvent("turbo:before-fetch-response", { bubbles: true }));

    await wait(0);
    await wait(0);

    expect(frame.innerHTML).toBe("");
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
