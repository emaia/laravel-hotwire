import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

import { attachMorphRecovery } from "../../resources/js/controllers/_turbo_morph_recovery.js";

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.CustomEvent = testWindow.CustomEvent;
    globalThis.Event = testWindow.Event;
});

afterEach(() => {
    testWindow.close();
});

function dispatchMorph(element) {
    element.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));
}

function mount() {
    document.body.innerHTML = `<div id="host"><canvas></canvas></div>`;
    return document.getElementById("host");
}

test.serial("recover() is called when isStale returns true on morph", async () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    dispatchMorph(element);

    expect(recovered).toBe(0);
    await Promise.resolve();
    expect(recovered).toBe(1);
});

test.serial("recover() is NOT called when isStale returns false on morph", async () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => false,
        recover: () => { recovered++; },
    });

    dispatchMorph(element);

    await Promise.resolve();
    expect(recovered).toBe(0);
});

test.serial("recover() is NOT called when the element is no longer in the document", async () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    element.remove();
    dispatchMorph(element);

    await Promise.resolve();
    expect(recovered).toBe(0);
});

test.serial("the returned detach function removes the listener", async () => {
    const element = mount();
    let recovered = 0;

    const detach = attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    detach();
    dispatchMorph(element);

    await Promise.resolve();
    expect(recovered).toBe(0);
});

test.serial("listener is bound to this.element, not the document — sibling morphs are ignored", async () => {
    document.body.innerHTML = `
        <div id="a"></div>
        <div id="b"></div>
    `;
    const a = document.getElementById("a");
    const b = document.getElementById("b");
    let recovered = 0;

    attachMorphRecovery({ element: a }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    dispatchMorph(b);

    await Promise.resolve();
    expect(recovered).toBe(0);
});

test.serial("root and descendant events from one morph coalesce into one recovery", async () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    dispatchMorph(element.querySelector("canvas"));
    dispatchMorph(element);

    expect(recovered).toBe(0);
    await Promise.resolve();
    expect(recovered).toBe(1);
});

test.serial("staleness is checked after the synchronous morph reaches its final DOM", async () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => !element.querySelector("canvas"),
        recover: () => { recovered++; },
    });

    dispatchMorph(element.querySelector("canvas"));
    element.innerHTML = "<p>final server DOM</p>";

    await Promise.resolve();
    expect(recovered).toBe(1);
});

test.serial("detach makes an already queued recovery inert", async () => {
    const element = mount();
    let recovered = 0;

    const detach = attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    dispatchMorph(element);
    detach();

    await Promise.resolve();
    expect(recovered).toBe(0);
});
