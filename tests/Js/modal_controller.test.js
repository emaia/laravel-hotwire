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

test.serial("opens when dynamic content is inserted and closes cleanly through the public API", async () => {
    mounted = await mountController(
        "modal",
        ModalController,
        `
            <div data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
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
                    </div>
                </div>
            </div>
        `,
    );

    const controller = mounted.controller;
    const frame = document.querySelector('#modal-frame');
    const modal = mounted.root.querySelector('[data-modal-target="modal"]');

    frame.innerHTML = "<div>Loaded content</div>";
    await wait(10);

    expect(controller.isOpen).toBe(true);
    expect(modal.hidden).toBe(false);
    expect(frame.innerHTML).toContain("Loaded content");

    controller.close();
    await wait(10);

    expect(controller.isOpen).toBe(false);
    expect(modal.hidden).toBe(true);
    expect(frame.innerHTML).toBe("");
});
