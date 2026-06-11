import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../../resources/js/helpers/test_stimulus.js";
import LinkController from "../../../resources/js/controllers/optimistic/link_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

function streams() {
    return document.body.querySelectorAll("turbo-stream");
}

function click(element, init = {}) {
    element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, ...init }));
}

// --- happy path ---

test.serial("normal click dispatches optimistic stream", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `);

    click(mounted.root);

    expect(streams()).toHaveLength(1);
});

// --- click filter: skip cases ---

test.serial("middle-button click is ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `);

    click(mounted.root, { button: 1 });

    expect(streams()).toHaveLength(0);
});

test.serial("modifier keys (meta/ctrl/shift/alt) are ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `);

    click(mounted.root, { metaKey: true });
    click(mounted.root, { ctrlKey: true });
    click(mounted.root, { shiftKey: true });
    click(mounted.root, { altKey: true });

    expect(streams()).toHaveLength(0);
});

test.serial("defaultPrevented click is ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `);

    mounted.root.addEventListener("click", (e) => e.preventDefault(), { capture: true });
    click(mounted.root);

    expect(streams()).toHaveLength(0);
});

test.serial("data-turbo=\"false\" is ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `, { turbo: "false" });

    click(mounted.root);

    expect(streams()).toHaveLength(0);
});

test.serial("target=\"_blank\" is ignored", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `, { target: "_blank" });

    click(mounted.root);

    expect(streams()).toHaveLength(0);
});

test.serial('target="_self" is allowed', async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `, { target: "_self" });

    click(mounted.root);

    expect(streams()).toHaveLength(1);
});

// --- disconnect cleanup ---

test.serial("disconnect detaches the click listener", async () => {
    await mount(`
        <template data-optimistic-stream data-optimistic-target-id="t">
            <div>card</div>
        </template>
    `);

    mounted.controller.disconnect();
    click(mounted.root);

    expect(streams()).toHaveLength(0);
});

async function mount(innerHTML, { turbo = null, target = null } = {}) {
    const attrs = [
        turbo !== null ? `data-turbo="${turbo}"` : "",
        target !== null ? `target="${target}"` : "",
    ].filter(Boolean).join(" ");
    mounted = await mountController(
        "optimistic--link",
        LinkController,
        `<a href="/x" data-controller="optimistic--link"${attrs ? " " + attrs : ""}>${innerHTML}</a>`,
    );
}
