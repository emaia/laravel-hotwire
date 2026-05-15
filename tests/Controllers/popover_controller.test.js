import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import PopoverController from "../../resources/js/controllers/popover_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

const baseHtml = (extra = "") => `
    <div data-controller="popover" ${extra}>
        <button type="button" data-popover-target="trigger" aria-expanded="false">Open</button>
        <div data-popover-target="content" data-popover aria-hidden="true">
            <input type="text" />
        </div>
    </div>
`;

test.serial("exposes trigger and content targets", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());

    expect(mounted.controller.hasTriggerTarget).toBe(true);
    expect(mounted.controller.hasContentTarget).toBe(true);
});

test.serial("starts closed and reports isOpen=false", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("open() sets aria-expanded=true and aria-hidden=false", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();

    expect(ctrl.triggerTarget.getAttribute("aria-expanded")).toBe("true");
    expect(ctrl.contentTarget.getAttribute("aria-hidden")).toBe("false");
    expect(ctrl.isOpen).toBe(true);
});

test.serial("close() restores aria-expanded=false and aria-hidden=true", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();
    ctrl.close(false);

    expect(ctrl.triggerTarget.getAttribute("aria-expanded")).toBe("false");
    expect(ctrl.contentTarget.getAttribute("aria-hidden")).toBe("true");
});

test.serial("close() is a no-op when already closed", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.close();

    expect(ctrl.triggerTarget.getAttribute("aria-expanded")).toBe("false");
    expect(ctrl.contentTarget.getAttribute("aria-hidden")).toBe("true");
});

test.serial("toggle() flips open/closed state", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.toggle();
    expect(ctrl.isOpen).toBe(true);

    ctrl.toggle();
    expect(ctrl.isOpen).toBe(false);
});

test.serial("clicking the trigger toggles the popover", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.triggerTarget.click();
    expect(ctrl.isOpen).toBe(true);

    ctrl.triggerTarget.click();
    expect(ctrl.isOpen).toBe(false);
});

test.serial("Escape keydown on the root closes the popover", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();
    ctrl.element.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));

    expect(ctrl.isOpen).toBe(false);
});

test.serial("clicking outside the wrapper closes the popover", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();

    const outside = document.createElement("button");
    document.body.appendChild(outside);
    outside.click();

    expect(ctrl.isOpen).toBe(false);

    outside.remove();
});

test.serial("clicking inside the content does not close the popover", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();
    const input = ctrl.contentTarget.querySelector("input");
    input.click();

    expect(ctrl.isOpen).toBe(true);
});

test.serial("opening one popover dispatches basecoat:popover", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    let received = null;
    document.addEventListener("basecoat:popover", (event) => {
        received = event.detail.source;
    });

    ctrl.open();

    expect(received).toBe(ctrl.element);
});

test.serial("basecoat:popover from another source closes this popover", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();

    const otherSource = document.createElement("div");
    document.dispatchEvent(
        new CustomEvent("basecoat:popover", { detail: { source: otherSource } }),
    );

    expect(ctrl.isOpen).toBe(false);
});

test.serial("basecoat:popover from this same source keeps this popover open", async () => {
    mounted = await mountController("popover", PopoverController, baseHtml());
    const ctrl = mounted.controller;

    ctrl.open();
    // open() already dispatched basecoat:popover with source=this.element; assert we didn't self-close
    expect(ctrl.isOpen).toBe(true);
});
