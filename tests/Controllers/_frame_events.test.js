import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

import { frameEventAffects, submissionFrameId } from "../../resources/js/controllers/_frame_events.js";

let window;

beforeEach(() => {
    window = new Window({ url: "http://localhost" });
    globalThis.window = window;
    globalThis.document = window.document;
});

afterEach(() => window.close());

test("matches owning, descendant and ancestor frame relationships", () => {
    document.body.innerHTML = `
        <main id="page">
            <turbo-frame id="owner"><form id="form"></form></turbo-frame>
            <turbo-frame id="other"></turbo-frame>
        </main>
    `;

    const page = document.getElementById("page");
    const owner = document.getElementById("owner");
    const form = document.getElementById("form");
    const other = document.getElementById("other");

    expect(frameEventAffects(form, { target: owner })).toBe(true);
    expect(frameEventAffects(owner, { target: owner })).toBe(true);
    expect(frameEventAffects(page, { target: owner })).toBe(true);
    expect(frameEventAffects(form, { target: other })).toBe(false);
    expect(frameEventAffects(form, { target: other }, "other")).toBe(true);
    expect(frameEventAffects(form, { target: document })).toBe(false);
});

test("uses the containing frame target for form submissions", () => {
    document.body.innerHTML = `
        <turbo-frame id="owner" target="preview">
            <form id="form"></form>
        </turbo-frame>
        <turbo-frame id="preview"></turbo-frame>
    `;

    const form = document.getElementById("form");

    expect(submissionFrameId(form, {})).toBe("preview");
});

test("falls through empty frame attributes to the containing frame target", () => {
    document.body.innerHTML = `
        <turbo-frame id="owner" target="preview">
            <form id="form" data-turbo-frame=""></form>
        </turbo-frame>
    `;

    expect(submissionFrameId(document.getElementById("form"), {})).toBe("preview");
});
