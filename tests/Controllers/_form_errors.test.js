import { afterEach, beforeEach, expect, test } from "bun:test";
import { Window } from "happy-dom";

import { formHasErrors } from "../../resources/js/controllers/_form_errors.js";

let window;

beforeEach(() => {
    window = new Window({ url: "http://localhost" });
    globalThis.window = window;
    globalThis.document = window.document;
    globalThis.Element = window.Element;
});

afterEach(() => {
    window.close();
});

test("returns false when the element is not inside a form", () => {
    document.body.innerHTML = `<div><input id="loose" /></div>`;

    expect(formHasErrors(document.getElementById("loose"))).toBe(false);
});

test("returns false when the surrounding form has no aria-invalid fields", () => {
    document.body.innerHTML = `
        <form>
            <input id="x" name="x" />
            <input name="y" />
        </form>
    `;

    expect(formHasErrors(document.getElementById("x"))).toBe(false);
});

test("returns true when the surrounding form contains any [aria-invalid=\"true\"]", () => {
    document.body.innerHTML = `
        <form>
            <input id="x" name="x" />
            <input name="y" aria-invalid="true" />
        </form>
    `;

    expect(formHasErrors(document.getElementById("x"))).toBe(true);
});

test("returns false for aria-invalid=\"false\" (only literal \"true\" counts)", () => {
    document.body.innerHTML = `
        <form>
            <input id="x" name="x" />
            <input name="y" aria-invalid="false" />
        </form>
    `;

    expect(formHasErrors(document.getElementById("x"))).toBe(false);
});

test("finds errors when the element itself is the invalid field", () => {
    document.body.innerHTML = `
        <form>
            <input id="x" name="x" aria-invalid="true" />
        </form>
    `;

    expect(formHasErrors(document.getElementById("x"))).toBe(true);
});
