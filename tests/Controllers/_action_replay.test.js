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

test("captures an action without writing identity markers to the document", () => {
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
    expect(action.targetElement).toBe(button);
    expect(action.signature).toContainEqual(["form", "item-form"]);
    expect(action.signature).toContainEqual(["name", "intent"]);
    expect(action.signature).toContainEqual(["value", "destroy"]);
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

test("resolves the same id-less trigger after it moves", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button" name="archive">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = zone.querySelector("button");
    const action = capture(original, zone, root);

    root.append(original);

    expect(resolveActionElement(action, root)).toBe(original);
});

test("fails closed when an id-less trigger is replaced at the same position", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button" name="archive">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = zone.querySelector("button");
    const action = capture(original, zone, root);

    original.replaceWith(original.cloneNode(true));

    expect(resolveActionElement(action, root)).toBeNull();
});

test("does not confuse indistinguishable id-less triggers after a reorder", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button type="button">Delete</button><button type="button">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const original = zone.firstElementChild;
    const action = capture(original, zone, root);

    original.remove();

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the same id-less trigger changes intent", () => {
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

test("fails closed when a relative link resolves to a different destination", () => {
    document.head.innerHTML = `<base href="http://localhost/archive/">`;
    document.body.innerHTML = `
        <div id="root"><div id="zone"><a id="trigger" href="items/1">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.querySelector("base").href = "http://localhost/published/";

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a stable submitter changes owner form", () => {
    document.body.innerHTML = `
        <form id="item-one"></form>
        <form id="item-two"></form>
        <div id="root"><div id="zone"><button id="trigger" type="submit" form="item-one">Delete</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    trigger.setAttribute("form", "item-two");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the owner form changes its submission context", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" action="/items/1" method="post">
                <div id="zone"><button id="trigger" type="submit">Delete</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("item-form").setAttribute("action", "/items/2");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a relative form action resolves to a different destination", () => {
    document.head.innerHTML = `<base href="http://localhost/archive/">`;
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" action="items/1" method="post">
                <div id="zone"><button id="trigger" type="submit">Delete</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.querySelector("base").href = "http://localhost/published/";

    expect(resolveActionElement(action, root)).toBeNull();
});

test("an empty form action ignores base URL changes", () => {
    document.head.innerHTML = `<base href="http://localhost/archive/">`;
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" action=""><div id="zone"><button id="trigger" type="submit">Save</button></div></form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.querySelector("base").href = "http://localhost/published/";

    expect(resolveActionElement(action, root)).toBe(trigger);
});

test("fails closed when the document URL changes for an empty form action", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form"><div id="zone"><button id="trigger" type="submit">Save</button></div></form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    window.history.pushState({}, "", "/other");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the owner form changes its framework behavior", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" data-action="submit->items#archive">
                <div id="zone"><button id="trigger" type="submit">Archive</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("item-form").setAttribute("data-action", "submit->items#destroy");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the owner form payload changes", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" action="/items/1" method="post">
                <input type="hidden" name="_method" value="delete">
                <input type="hidden" name="item" value="1">
                <div id="zone"><button id="trigger" type="submit">Delete</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.querySelector(`[name="item"]`).value = "2";

    expect(resolveActionElement(action, root)).toBeNull();
});

test("accepts an equivalent form payload after controls are reordered", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" action="/items/1" method="post">
                <input type="hidden" name="_method" value="delete">
                <input type="hidden" name="item" value="1">
                <div id="zone"><button id="trigger" type="submit">Delete</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);
    const form = document.getElementById("item-form");

    form.prepend(form.querySelector(`[name="item"]`));

    expect(resolveActionElement(action, root)).toBe(trigger);
});

test("fails closed when an empty file control is removed from the payload", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form">
                <input type="file" name="attachment">
                <div id="zone"><button id="trigger" type="submit">Upload</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.querySelector(`[name="attachment"]`).remove();

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when inherited Turbo enablement changes", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone" data-turbo="false"><a id="trigger" href="/items/1">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    zone.setAttribute("data-turbo", "true");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("treats enabled Turbo attribute values as equivalent", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone" data-turbo=""><a id="trigger" href="/items/1">Delete</a></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    zone.setAttribute("data-turbo", "true");

    expect(resolveActionElement(action, root)).toBe(trigger);
});

test("fails closed when Turbo enablement changes above the Alert Dialog", () => {
    document.body.innerHTML = `
        <main id="context" data-turbo="false">
            <div id="root"><div id="zone"><a id="trigger" href="/items/1">Delete</a></div></div>
        </main>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("context").setAttribute("data-turbo", "true");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the effective Turbo Frame target changes", () => {
    document.body.innerHTML = `
        <div id="root">
            <turbo-frame id="items"><div id="zone"><a id="trigger" href="/items/1">Edit</a></div></turbo-frame>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    trigger.closest("turbo-frame").id = "archive";

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a requested Turbo Frame appears before replay", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><a id="trigger" href="/items/1" data-turbo-frame="preview">Preview</a></div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.body.insertAdjacentHTML("beforeend", `<turbo-frame id="preview"></turbo-frame>`);

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the requested Turbo Frame becomes disabled", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><a id="trigger" href="/items/1" data-turbo-frame="preview">Preview</a></div>
        </div>
        <turbo-frame id="preview"></turbo-frame>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("preview").setAttribute("disabled", "");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("ignores Turbo Frame attributes that do not change routing", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><a id="trigger" href="/items/1" data-turbo-frame="preview">Preview</a></div>
        </div>
        <turbo-frame id="preview" src="/old" data-state="idle"></turbo-frame>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);
    const frame = document.getElementById("preview");

    frame.setAttribute("src", "/new");
    frame.setAttribute("data-state", "loaded");

    expect(resolveActionElement(action, root)).toBe(trigger);
});

test("fails closed when an underscore-prefixed Turbo Frame appears before replay", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><a id="trigger" href="/items/1" data-turbo-frame="_preview">Preview</a></div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.body.insertAdjacentHTML("beforeend", `<turbo-frame id="_preview"></turbo-frame>`);

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a requested Turbo Frame id is ambiguous", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><a id="trigger" href="/items/1" data-turbo-frame="preview">Preview</a></div>
        </div>
        <turbo-frame id="preview" data-version="one"></turbo-frame>
        <turbo-frame id="preview" data-version="two"></turbo-frame>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    expect(resolveActionElement(action, root)).toBeNull();
});

test("an empty submitter frame target falls through to the form target", () => {
    document.body.innerHTML = `
        <turbo-frame id="current">
            <div id="root">
                <form id="item-form" data-turbo-frame="preview">
                    <div id="zone"><button id="trigger" type="submit" data-turbo-frame="">Save</button></div>
                </form>
            </div>
        </turbo-frame>
        <turbo-frame id="preview"></turbo-frame>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("preview").setAttribute("disabled", "");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("ignores visual class changes when resolving an action", () => {
    document.body.innerHTML = `
        <div id="root">
            <form id="item-form" class="idle">
                <div id="zone"><button id="trigger" class="primary" type="submit">Save</button></div>
            </form>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    trigger.className = "loading";
    document.getElementById("item-form").className = "validated";

    expect(resolveActionElement(action, root)).toBe(trigger);
});

test("fails closed when a native popover target changes identity", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><button id="trigger" type="button" popovertarget="details">Toggle</button></div>
            <div id="details" popover="manual"></div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("details").outerHTML = `<aside id="details" popover="manual"></aside>`;

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a native popover target changes mode", () => {
    document.body.innerHTML = `
        <div id="root">
            <div id="zone"><button id="trigger" type="button" popovertarget="details">Toggle</button></div>
            <div id="details" popover="manual"></div>
        </div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    document.getElementById("details").setAttribute("popover", "auto");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when a native click handler changes", () => {
    document.body.innerHTML = `
        <div id="root"><div id="zone"><button id="trigger" type="button" onclick="archive()">Archive</button></div></div>
    `;
    const root = document.getElementById("root");
    const zone = document.getElementById("zone");
    const trigger = document.getElementById("trigger");
    const action = capture(trigger, zone, root);

    trigger.setAttribute("onclick", "destroy()");

    expect(resolveActionElement(action, root)).toBeNull();
});

test("fails closed when the captured id-less trigger leaves the root", () => {
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
