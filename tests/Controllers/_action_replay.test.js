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

function capture(target, zone, root) {
    return captureAction({ target, currentTarget: zone }, root);
}

// --- capture ---

test("captures an action without retaining DOM nodes or writing to the document", () => {
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

    const action = capture(button, zone, root);

    expect(action.kind).toBe("submit");
    expect(action.signature).toContainEqual(["name", "intent"]);
    expect(action.signature).toContainEqual(["value", "destroy"]);
    expect(JSON.parse(JSON.stringify(action))).toEqual(action);
    expect(root.innerHTML).not.toContain("data-hotwire");
});

test("classifies an href-less anchor as a generic click", () => {
    document.body.innerHTML = `<div id="root"><div id="zone"><a>Archive</a></div></div>`;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");

    expect(capture(zone.querySelector("a"), zone, root).kind).toBe("click");
    zone.querySelector("a").setAttribute("href", "/archive");
    expect(capture(zone.querySelector("a"), zone, root).kind).toBe("link");
});

// --- resolution ---

test("resolves a replacement trigger by id", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button id="trigger" type="button">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = document.getElementById("trigger");
    const action = capture(original, zone, root);
    const replacement = original.cloneNode(true);

    original.replaceWith(replacement);

    expect(resolveActionElement(action, root)).toBe(replacement);
});

test("resolves an id-less replacement trigger by its position", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button" name="archive">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = zone.querySelector("button");
    const action = capture(original, zone, root);
    const replacement = original.cloneNode(true);

    original.replaceWith(replacement);

    expect(resolveActionElement(action, root)).toBe(replacement);
});

test("fails closed when the replacement at the same position changed intent", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button" name="archive">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = zone.querySelector("button");
    const action = capture(original, zone, root);

    original.setAttribute("name", "publish");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a stable-id trigger changes intent", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><a id="trigger" href="/posts/1" data-turbo-method="delete">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    trigger.setAttribute("href", "/posts/2");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the captured position no longer exists", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const action = capture(zone.querySelector("button"), zone, root);

    zone.remove();

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the captured id became ambiguous", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button id="trigger" type="button">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    zone.append(trigger.cloneNode(true));

    expect(resolveActionElement(action, root)).toBeNull();
});

// --- replay ---

test("replays a generic click on the resolved element", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const button = zone.querySelector("button");
    let clicks = 0;
    button.addEventListener("click", () => clicks++);

    const action = capture(button, zone, root);

    expect(clicks).toBe(0);
    expect(replayAction(action, root)).toBe(true);
    expect(clicks).toBe(1);
});

test("replays a submit through the real submitter so the form sees it", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form">
                <div id="zone"><button type="submit" name="intent" value="destroy">Delete</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const button = zone.querySelector("button");
    const submitters = [];
    document.getElementById("item-form").addEventListener("submit", (event) => {
        event.preventDefault();
        submitters.push(event.submitter ?? null);
    });

    const action = capture(button, zone, root);

    expect(action.kind).toBe("submit");
    expect(replayAction(action, root)).toBe(true);
    expect(submitters.length).toBe(1);
});

test("replays a link by clicking the anchor that still carries its own attributes", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><a href="/posts/1" data-turbo-method="delete">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const anchor = zone.querySelector("a");
    const observed = [];
    document.addEventListener("click", (event) => {
        event.preventDefault();
        observed.push({
            target: event.target,
            method: event.target.getAttribute("data-turbo-method"),
        });
    });

    const action = capture(anchor, zone, root);

    expect(replayAction(action, root)).toBe(true);
    expect(observed).toEqual([{ target: anchor, method: "delete" }]);
});

test("does not replay when the resolved target became disabled", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const button = zone.querySelector("button");
    let clicks = 0;
    button.addEventListener("click", () => clicks++);

    const action = capture(button, zone, root);
    button.disabled = true;

    expect(replayAction(action, root)).toBe(false);
    expect(clicks).toBe(0);
});

test("does not replay when the resolved target became aria-disabled", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><a>Archive</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const anchor = zone.querySelector("a");
    let clicks = 0;
    anchor.addEventListener("click", () => clicks++);

    const action = capture(anchor, zone, root);
    anchor.setAttribute("aria-disabled", "true");

    expect(replayAction(action, root)).toBe(false);
    expect(clicks).toBe(0);
});

test("does not replay when the element can no longer be identified", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const action = capture(zone.querySelector("button"), zone, root);

    zone.innerHTML = `<button type="button" name="something-else">Other</button>`;

    expect(replayAction(action, root)).toBe(false);
});
