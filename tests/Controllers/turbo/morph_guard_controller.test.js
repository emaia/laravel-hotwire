import { afterEach, expect, test } from "bun:test";

import {
    mountController,
    mountControllers,
    wait,
} from "../../../resources/js/helpers/test_stimulus.js";
import MorphGuardController from "../../../resources/js/controllers/turbo/morph_guard_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test("marks the nearest Turbo Frame permanent while connected", async () => {
    await mount(`
        <turbo-frame id="editor-frame">
            <form data-controller="turbo--morph-guard"></form>
        </turbo-frame>
    `);

    expect(document.querySelector("#editor-frame").hasAttribute("data-turbo-permanent")).toBe(true);
});

test("works when mounted directly on the frame", async () => {
    await mount('<turbo-frame id="editor-frame" data-controller="turbo--morph-guard"></turbo-frame>');

    expect(mounted.root.hasAttribute("data-turbo-permanent")).toBe(true);
});

test("guards only the nearest frame", async () => {
    await mount(`
        <turbo-frame id="outer-frame">
            <turbo-frame id="inner-frame">
                <form data-controller="turbo--morph-guard"></form>
            </turbo-frame>
        </turbo-frame>
    `);

    expect(document.querySelector("#inner-frame").hasAttribute("data-turbo-permanent")).toBe(true);
    expect(document.querySelector("#outer-frame").hasAttribute("data-turbo-permanent")).toBe(false);
});

test("safely ignores missing invalid and duplicated frame ids", async () => {
    mounted = await mountControllers(
        "turbo--morph-guard",
        MorphGuardController,
        `
            <section data-controller="turbo--morph-guard"></section>
            <turbo-frame><form data-controller="turbo--morph-guard"></form></turbo-frame>
            <turbo-frame id="duplicate"><form data-controller="turbo--morph-guard"></form></turbo-frame>
            <turbo-frame id="duplicate"></turbo-frame>
            <turbo-frame id="shared"><form data-controller="turbo--morph-guard"></form></turbo-frame>
            <div id="shared"></div>
        `,
    );

    expect(document.querySelectorAll("[data-turbo-permanent]")).toHaveLength(0);
});

test("removes a guard-owned marker on disconnect", async () => {
    await mount(`
        <turbo-frame id="editor-frame">
            <form data-controller="turbo--morph-guard"></form>
        </turbo-frame>
    `);
    const frame = document.querySelector("#editor-frame");

    mounted.controller.disconnect();

    expect(frame.hasAttribute("data-turbo-permanent")).toBe(false);
});

test("preserves a marker that existed before the guard connected", async () => {
    await mount(`
        <turbo-frame id="editor-frame" data-turbo-permanent="server-owned">
            <form data-controller="turbo--morph-guard"></form>
        </turbo-frame>
    `);
    const frame = document.querySelector("#editor-frame");

    mounted.controller.disconnect();

    expect(frame.getAttribute("data-turbo-permanent")).toBe("server-owned");
});

test("keeps a shared frame guarded until the final owner disconnects", async () => {
    mounted = await mountControllers(
        "turbo--morph-guard",
        MorphGuardController,
        `
            <turbo-frame id="editor-frame">
                <form id="first" data-controller="turbo--morph-guard"></form>
                <form id="second" data-controller="turbo--morph-guard"></form>
            </turbo-frame>
        `,
    );
    const frame = document.querySelector("#editor-frame");

    mounted.controllers[0].disconnect();
    expect(frame.hasAttribute("data-turbo-permanent")).toBe(true);

    mounted.controllers[1].disconnect();
    expect(frame.hasAttribute("data-turbo-permanent")).toBe(false);
});

test("keeps ownership isolated across separate frames", async () => {
    mounted = await mountControllers(
        "turbo--morph-guard",
        MorphGuardController,
        `
            <turbo-frame id="first-frame">
                <form data-controller="turbo--morph-guard"></form>
            </turbo-frame>
            <turbo-frame id="second-frame">
                <form data-controller="turbo--morph-guard"></form>
            </turbo-frame>
        `,
    );

    mounted.controllers[0].disconnect();

    expect(document.querySelector("#first-frame").hasAttribute("data-turbo-permanent")).toBe(false);
    expect(document.querySelector("#second-frame").hasAttribute("data-turbo-permanent")).toBe(true);
});

test("releases the old frame and reacquires a replacement on reconnect", async () => {
    await mount(`
        <turbo-frame id="editor-frame">
            <form data-controller="turbo--morph-guard"></form>
        </turbo-frame>
    `);
    const oldFrame = document.querySelector("#editor-frame");
    const replacement = document.createElement("turbo-frame");
    replacement.id = "editor-frame";

    oldFrame.replaceWith(replacement);
    mounted.controller.disconnect();
    replacement.append(mounted.root);
    mounted.controller.connect();

    expect(oldFrame.hasAttribute("data-turbo-permanent")).toBe(false);
    expect(replacement.hasAttribute("data-turbo-permanent")).toBe(true);
});

test("removes package-owned markers before Turbo caches the page", async () => {
    await mount(`
        <turbo-frame id="editor-frame">
            <form data-controller="turbo--morph-guard"></form>
        </turbo-frame>
    `);
    const frame = document.querySelector("#editor-frame");

    document.dispatchEvent(new Event("turbo:before-cache"));
    const snapshot = await new Promise((resolve) => {
        setTimeout(() => resolve(frame.cloneNode(true)), 0);
    });

    expect(snapshot.hasAttribute("data-turbo-permanent")).toBe(false);

    await wait(0);
    expect(frame.hasAttribute("data-turbo-permanent")).toBe(true);
});

async function mount(html) {
    mounted = await mountController(
        "turbo--morph-guard",
        MorphGuardController,
        html,
    );
}
