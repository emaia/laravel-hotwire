import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

import {
    captureAction,
    replayAction,
    resolveActionElement,
} from "../../resources/js/controllers/_action_replay.js";

let window;

beforeEach(() => {
    window = new Window({ url: "http://localhost" });
    globalThis.window = window;
    globalThis.document = window.document;
    globalThis.Element = window.Element;
});

afterEach(() => window.close());

test("captures a submit action without retaining DOM nodes", () => {
    document.body.innerHTML = `
        <form id="item-form"></form>
        <div id="root">
            <div id="zone">
                <button type="submit" form="item-form" name="intent" value="destroy">Delete</button>
            </div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const button = zone.querySelector("button");

    const action = captureAction({ target: button, currentTarget: zone }, root);

    expect(action.kind).toBe("submit");
    expect(action.formId).toBe("item-form");
    expect(action.attributes).toContainEqual(["name", "intent"]);
    expect(action.attributes).toContainEqual(["value", "destroy"]);
    expect(JSON.parse(JSON.stringify(action))).toEqual(action);
});

test("resolves a replacement trigger by id", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button id="trigger" type="button">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = document.getElementById("trigger");
    const action = captureAction({ target: original, currentTarget: zone }, root);
    const replacement = original.cloneNode(true);

    original.replaceWith(replacement);

    expect(resolveActionElement(action, root)).toBe(replacement);
});

test("replays an href-less anchor as a generic click", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone">
                <a id="trigger" data-action="items#destroy">Delete</a>
                <a id="empty-href" href="">Reload</a>
            </div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    const emptyHrefAction = captureAction({ target: document.getElementById("empty-href"), currentTarget: zone }, root);
    let clicks = 0;
    trigger.addEventListener("click", () => clicks++);

    expect(action.kind).toBe("click");
    expect(emptyHrefAction.kind).toBe("link");
    expect(replayAction(action, root)).toBe(true);
    expect(clicks).toBe(1);
});

test("does not replay an href-less anchor that becomes aria-disabled", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><a id="trigger" data-action="items#destroy">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    let clicks = 0;
    trigger.addEventListener("click", () => clicks++);

    trigger.setAttribute("aria-disabled", "true");

    expect(replayAction(action, root)).toBe(false);
    expect(clicks).toBe(0);
});

test("does not replay a generic click on an unrelated replacement", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button" data-action="items#destroy">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = zone.querySelector("button");
    const action = captureAction({ target: original, currentTarget: zone }, root);
    const unrelated = document.createElement("button");
    unrelated.type = "button";
    unrelated.dataset.action = "items#archive";
    let unrelatedClicks = 0;
    unrelated.addEventListener("click", () => unrelatedClicks++);

    original.replaceWith(unrelated);

    expect(replayAction(action, root)).toBe(false);
    expect(unrelatedClicks).toBe(0);
});

test("does not replay a generic click after a stable-id button changes intent", () => {
    document.body.innerHTML = `
        <form><div id="root"><div id="zone"><button id="trigger" type="button" data-action="items#archive">Archive</button></div></div></form>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = document.getElementById("trigger");
    const action = captureAction({ target: original, currentTarget: zone }, root);
    const replacement = original.cloneNode(true);
    replacement.type = "submit";
    replacement.dataset.action = "items#destroy";
    let clicks = 0;
    replacement.addEventListener("click", (event) => {
        event.preventDefault();
        clicks++;
    });

    original.replaceWith(replacement);

    expect(replayAction(action, root)).toBe(false);
    expect(clicks).toBe(0);
});

test("does not resolve duplicate internal action keys", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = zone.querySelector("button");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    const duplicate = trigger.cloneNode(true);
    let clicks = 0;
    trigger.addEventListener("click", () => clicks++);
    duplicate.addEventListener("click", () => clicks++);
    zone.append(duplicate);

    expect(resolveActionElement(action, root)).toBeNull();
    expect(replayAction(action, root)).toBe(false);
    expect(clicks).toBe(0);
});

test("does not replay through duplicate trigger boundaries", () => {
    document.body.innerHTML = `
        <div id="root"><div data-slot="alert-dialog-trigger" data-action="click->alert-dialog#intercept"><a href="/items/1">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = root.querySelector("[data-slot='alert-dialog-trigger']");
    const trigger = zone.querySelector("a");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);

    root.append(zone.cloneNode(true));

    expect(replayAction(action, root)).toBe(false);
});

test("replays a submit from an id-less form inside the trigger zone", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone">
                <form><button type="submit" name="intent" value="save">Save</button></form>
            </div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const form = zone.querySelector("form");
    const button = form.querySelector("button");
    const action = captureAction({ target: button, currentTarget: zone }, root);
    let submitted = false;
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitted = true;
    });

    expect(replayAction(action, root)).toBe(true);
    expect(submitted).toBe(true);
});

test("replays a submit from an id-less form wrapping the controller root", () => {
    document.body.innerHTML = `
        <form>
            <div id="root"><div id="zone"><button type="submit">Save</button></div></div>
        </form>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const form = root.closest("form");
    const button = zone.querySelector("button");
    const action = captureAction({ target: button, currentTarget: zone }, root);
    let submitted = false;
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitted = true;
    });

    expect(replayAction(action, root)).toBe(true);
    expect(submitted).toBe(true);
    expect(form.hasAttribute("id")).toBe(false);
});

test("uses captured submitter attributes when a replacement changes intent", () => {
    document.body.innerHTML = `
        <form id="item-form"></form>
        <div id="root"><div id="zone"><button id="trigger" type="submit" form="item-form" formaction="/items/1">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = document.getElementById("trigger");
    const action = captureAction({ target: original, currentTarget: zone }, root);
    const replacement = original.cloneNode(true);
    replacement.setAttribute("formaction", "/items/2");
    original.replaceWith(replacement);
    let submittedAction = null;
    document.getElementById("item-form").addEventListener("submit", (event) => {
        event.preventDefault();
        submittedAction = event.submitter.getAttribute("formaction");
    });

    replayAction(action, root);

    expect(submittedAction).toBe("/items/1");
});

test("replays a link through a transient unwired proxy", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone" data-action="click->alert-dialog#intercept">
                <a id="trigger" href="/items/1" data-action="other#run" data-turbo-method="delete">Delete</a>
            </div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    let replayed = null;
    root.addEventListener("click", (event) => {
        if (event.target.hasAttribute("data-hotwire-action-replay")) {
            event.preventDefault();
            replayed = event.target;
        }
    });

    trigger.remove();

    expect(replayAction(action, root)).toBe(true);
    expect(replayed?.getAttribute("href")).toBe("/items/1");
    expect(replayed?.getAttribute("data-turbo-method")).toBe("delete");
    expect(replayed?.hasAttribute("data-action")).toBe(false);
    expect(root.querySelector("[data-hotwire-action-replay]")).toBeNull();
});

test("does not copy inline or delegated behavior to the replay proxy", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone">
                <a href="#done" onclick="window.inlineClicks++" wire:click="destroy">Delete</a>
            </div>
        </div>
    `;
    window.inlineClicks = 0;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = zone.querySelector("a");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    let proxy = null;
    root.addEventListener("click", (event) => {
        if (!event.target.hasAttribute("data-hotwire-action-replay")) return;

        event.preventDefault();
        proxy = event.target;
    });

    replayAction(action, root);

    expect(window.inlineClicks).toBe(0);
    expect(proxy?.hasAttribute("onclick")).toBe(false);
    expect(proxy?.hasAttribute("wire:click")).toBe(false);
});

test("inherits only Turbo enablement and preserves a lost containing frame", () => {
    document.body.innerHTML = `
        <div id="root" data-turbo="false" data-turbo-frame="wrong" data-turbo-action="replace">
            <turbo-frame id="items">
                <div id="zone"><a href="/items/1">Delete</a></div>
            </turbo-frame>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = zone.querySelector("a");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    let proxy = null;
    root.addEventListener("click", (event) => {
        if (!event.target.hasAttribute("data-hotwire-action-replay")) return;

        event.preventDefault();
        proxy = event.target;
    });

    zone.closest("turbo-frame").replaceWith(zone);
    replayAction(action, root);

    expect(proxy?.getAttribute("data-turbo")).toBe("false");
    expect(proxy?.getAttribute("data-turbo-frame")).toBe("items");
    expect(proxy?.hasAttribute("data-turbo-action")).toBe(false);
});

test("resolves a relative parent frame target before moving the proxy", () => {
    document.body.innerHTML = `
        <div id="root">
            <turbo-frame id="parent">
                <turbo-frame id="child">
                    <div id="zone"><a href="/items/1" data-turbo-frame="_parent">Delete</a></div>
                </turbo-frame>
            </turbo-frame>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = zone.querySelector("a");
    const action = captureAction({ target: trigger, currentTarget: zone }, root);
    let proxy = null;
    root.addEventListener("click", (event) => {
        if (!event.target.hasAttribute("data-hotwire-action-replay")) return;

        event.preventDefault();
        proxy = event.target;
    });

    root.append(zone);
    replayAction(action, root);

    expect(proxy?.getAttribute("data-turbo-frame")).toBe("parent");
});

test("derives ambient submit frame context from the owner form", () => {
    document.body.innerHTML = `
        <form id="outside-form"></form>
        <div id="root">
            <turbo-frame id="trigger-frame">
                <div id="outside-zone"><button type="submit" form="outside-form">Outside form</button></div>
            </turbo-frame>
            <turbo-frame id="form-frame">
                <form id="inside-form"></form>
            </turbo-frame>
            <div id="inside-zone"><button type="submit" form="inside-form">Inside form</button></div>
        </div>
    `;
    const root = document.getElementById("root");
    const outsideZone = document.getElementById("outside-zone");
    const insideZone = document.getElementById("inside-zone");

    const outsideAction = captureAction({ target: outsideZone.querySelector("button"), currentTarget: outsideZone }, root);
    const insideAction = captureAction({ target: insideZone.querySelector("button"), currentTarget: insideZone }, root);

    expect(outsideAction.frameTarget).toBe("");
    expect(insideAction.frameTarget).toBe("form-frame");
});
