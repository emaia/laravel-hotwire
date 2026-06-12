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

test.serial("recover() is called when isStale returns true on morph", () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    dispatchMorph(element);

    expect(recovered).toBe(1);
});

test.serial("recover() is NOT called when isStale returns false on morph", () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => false,
        recover: () => { recovered++; },
    });

    dispatchMorph(element);

    expect(recovered).toBe(0);
});

test.serial("recover() is NOT called when the element is no longer in the document", () => {
    const element = mount();
    let recovered = 0;

    attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    element.remove();
    dispatchMorph(element);

    expect(recovered).toBe(0);
});

test.serial("the returned detach function removes the listener", () => {
    const element = mount();
    let recovered = 0;

    const detach = attachMorphRecovery({ element }, {
        isStale: () => true,
        recover: () => { recovered++; },
    });

    detach();
    dispatchMorph(element);

    expect(recovered).toBe(0);
});

test.serial("listener is bound to this.element, not the document — sibling morphs are ignored", () => {
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

    expect(recovered).toBe(0);
});
