import { afterEach, expect, test } from "bun:test";

import { mountController, mountMultipleControllers } from "../../resources/js/helpers/test_stimulus.js";
import { Controller } from "@hotwired/stimulus";
import ModalAutoCloseController from "../../resources/js/controllers/modal_auto_close_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("calls close() on the parent modal controller", async () => {
    const state = { closeCalls: 0 };

    class ModalStub extends Controller {
        close() { state.closeCalls++; }
    }

    mounted = await mountMultipleControllers(
        { "modal": ModalStub, "modal-auto-close": ModalAutoCloseController },
        `<div data-controller="modal">
            <div data-controller="modal-auto-close"></div>
        </div>`,
    );

    expect(state.closeCalls).toBe(1);
    expect(document.querySelector('[data-controller~="modal-auto-close"]')).toBeNull();
});

test.serial("element removed without crash when no modal parent", async () => {
    mounted = await mountController("modal-auto-close", ModalAutoCloseController, `
        <div data-controller="modal-auto-close"></div>
    `);

    expect(document.querySelector('[data-controller~="modal-auto-close"]')).toBeNull();
});

test.serial("element removed without crash when ancestor lacks modal controller", async () => {
    mounted = await mountController("modal-auto-close", ModalAutoCloseController, `
        <div>
            <div data-controller="modal-auto-close"></div>
        </div>
    `);

    expect(document.querySelector('[data-controller~="modal-auto-close"]')).toBeNull();
});
