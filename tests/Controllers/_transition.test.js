import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

import { enter, leave } from "../../resources/js/controllers/_transition.js";

let window;

beforeEach(() => {
    window = new Window({ url: "http://localhost" });
    window.SyntaxError = SyntaxError;
    globalThis.window = window;
    globalThis.document = window.document;
    globalThis.Element = window.Element;
    globalThis.getComputedStyle = window.getComputedStyle.bind(window);
    globalThis.requestAnimationFrame = window.requestAnimationFrame.bind(window);
    globalThis.cancelAnimationFrame = window.cancelAnimationFrame.bind(window);
});

afterEach(() => {
    window.close();
});

// --- instant path (no transition attributes) ---

test("enter removes the hidden class instantly when no transition attrs", () => {
    const el = make("hidden");

    enter(el);

    expect(el.classList.contains("hidden")).toBe(false);
});

test("leave adds the hidden class instantly when no transition attrs", () => {
    const el = make("");

    leave(el);

    expect(el.classList.contains("hidden")).toBe(true);
});

test("honors a custom hidden class", () => {
    const el = make("is-closed");

    enter(el, { hidden: "is-closed" });

    expect(el.classList.contains("is-closed")).toBe(false);
});

// --- synchronous setup of the animated path ---

test("enter unhides and applies active + from classes synchronously", () => {
    const el = make("hidden", {
        "data-transition-enter": "t-enter",
        "data-transition-enter-from": "ef",
        "data-transition-enter-to": "et",
    });

    enter(el);

    expect(el.classList.contains("hidden")).toBe(false);
    expect(el.classList.contains("t-enter")).toBe(true);
    expect(el.classList.contains("ef")).toBe(true);
    expect(el.classList.contains("et")).toBe(false); // "to" applied later
});

test("leave applies active + from classes synchronously and stays visible", () => {
    const el = make("", {
        "data-transition-leave": "t-leave",
        "data-transition-leave-from": "lf",
        "data-transition-leave-to": "lt",
    });

    leave(el);

    expect(el.classList.contains("hidden")).toBe(false);
    expect(el.classList.contains("t-leave")).toBe(true);
    expect(el.classList.contains("lf")).toBe(true);
});

// --- interruption ---

test("interrupting enter with leave strips stale enter classes", () => {
    const el = make("hidden", {
        "data-transition-enter": "t-enter",
        "data-transition-enter-from": "ef",
        "data-transition-enter-to": "et",
        "data-transition-leave": "t-leave",
        "data-transition-leave-from": "lf",
        "data-transition-leave-to": "lt",
    });

    enter(el);
    leave(el);

    expect(el.classList.contains("t-enter")).toBe(false);
    expect(el.classList.contains("ef")).toBe(false);
    expect(el.classList.contains("t-leave")).toBe(true);
    expect(el.classList.contains("lf")).toBe(true);
});

// --- full sequence (rAF stubbed to run immediately) ---

test("enter completes: from removed, to applied, then cleaned, staying visible", async () => {
    immediateFrames();
    const el = make("hidden", {
        "data-transition-enter": "t-enter",
        "data-transition-enter-from": "ef",
        "data-transition-enter-to": "et",
    });

    enter(el);
    expect(el.classList.contains("ef")).toBe(false);
    expect(el.classList.contains("et")).toBe(true);

    await tick();
    expect(el.classList.contains("t-enter")).toBe(false);
    expect(el.classList.contains("et")).toBe(false);
    expect(el.classList.contains("hidden")).toBe(false);
});

test("leave completes: ends hidden with all transition classes cleaned", async () => {
    immediateFrames();
    const el = make("", {
        "data-transition-leave": "t-leave",
        "data-transition-leave-from": "lf",
        "data-transition-leave-to": "lt",
    });

    leave(el);
    expect(el.classList.contains("lf")).toBe(false);
    expect(el.classList.contains("lt")).toBe(true);

    await tick();
    expect(el.classList.contains("t-leave")).toBe(false);
    expect(el.classList.contains("lt")).toBe(false);
    expect(el.classList.contains("hidden")).toBe(true);
});

// --- helpers ---

function make(className, attrs = {}) {
    const el = document.createElement("div");
    if (className) el.className = className;
    for (const [key, value] of Object.entries(attrs)) el.setAttribute(key, value);
    document.body.appendChild(el);
    return el;
}

function immediateFrames() {
    globalThis.requestAnimationFrame = (cb) => {
        cb();
        return 0;
    };
    globalThis.cancelAnimationFrame = () => {};
}

function tick() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
