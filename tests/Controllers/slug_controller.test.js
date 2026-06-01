import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import SlugController from "../../resources/js/controllers/slug_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- slugify ---

test.serial("generates a slug from the source as the user types", async () => {
    await mount();

    type(source(), "Hello World");

    expect(slug().value).toBe("hello-world");
});

test.serial("strips diacritics and punctuation", async () => {
    await mount();

    type(source(), "Olá, Mundo! Ação & Café");

    expect(slug().value).toBe("ola-mundo-acao-cafe");
});

test.serial("collapses and trims separators", async () => {
    await mount();

    type(source(), "  --Foo   bar-- ");

    expect(slug().value).toBe("foo-bar");
});

test.serial("honors a custom separator", async () => {
    await mount({ separator: "_" });

    type(source(), "Hello World");

    expect(slug().value).toBe("hello_world");
});

// --- maxLength ---

test.serial("truncates at the word boundary when over maxLength", async () => {
    await mount({ maxLength: 12 });

    type(source(), "The Quick Brown Fox");

    expect(slug().value).toBe("the-quick");
});

test.serial("hard-cuts a single long word with no separator to fit maxLength", async () => {
    await mount({ maxLength: 5 });

    type(source(), "Supercalifragilistic");

    expect(slug().value).toBe("super");
});

// --- locking ---

test.serial("stops syncing once the slug is edited manually", async () => {
    await mount();

    type(source(), "First Title");
    expect(slug().value).toBe("first-title");

    type(slug(), "custom-slug");
    type(source(), "Second Title");

    expect(slug().value).toBe("custom-slug");
    expect(mounted.root.getAttribute("data-slug-locked")).toBe("true");
});

test.serial("starts locked on an edit page where the slug is prefilled", async () => {
    await mount({ slugValue: "existing-post" });

    expect(mounted.root.getAttribute("data-slug-locked")).toBe("true");

    type(source(), "A New Title");

    expect(slug().value).toBe("existing-post");
});

test.serial("starts locked when auto is false", async () => {
    await mount({ auto: false });

    type(source(), "Anything");

    expect(slug().value).toBe("");
    expect(mounted.root.getAttribute("data-slug-locked")).toBe("true");
});

// --- sync on connect ---

test.serial("generates from a prefilled source on connect", async () => {
    await mount({ sourceValue: "Restored Title" });

    expect(slug().value).toBe("restored-title");
    expect(mounted.root.getAttribute("data-slug-locked")).toBe("false");
});

// --- relink ---

test.serial("relink unlocks and regenerates from the source", async () => {
    await mount();

    type(source(), "My Post");
    type(slug(), "manual");
    expect(mounted.root.getAttribute("data-slug-locked")).toBe("true");

    mounted.controller.relink();
    await wait(0);

    expect(slug().value).toBe("my-post");
    expect(mounted.root.getAttribute("data-slug-locked")).toBe("false");

    type(source(), "My Post Updated");
    expect(slug().value).toBe("my-post-updated");
});

// --- preview ---

test.serial("mirrors the slug into the preview element", async () => {
    await mount({ preview: true });

    type(source(), "Hello World");

    expect(preview().textContent).toBe("hello-world");
});

test.serial("mirrors the existing slug into the preview on connect", async () => {
    await mount({ preview: true, slugValue: "kept-slug" });

    expect(preview().textContent).toBe("kept-slug");
});

// --- helpers ---

const source = () => document.querySelector('[data-slug-target="source"]');
const slug = () => document.querySelector('[data-slug-target="slug"]');
const preview = () => document.querySelector('[data-slug-target="preview"]');

function type(input, value) {
    input.value = value;
    input.dispatchEvent(new Event("input", { bubbles: true }));
}

async function mount({
    separator = null,
    auto = null,
    maxLength = null,
    sourceValue = "",
    slugValue = "",
    preview = false,
} = {}) {
    const values = [
        separator === null ? "" : `data-slug-separator-value="${separator}"`,
        auto === null ? "" : `data-slug-auto-value="${auto}"`,
        maxLength === null ? "" : `data-slug-max-length-value="${maxLength}"`,
    ].join(" ");

    mounted = await mountController(
        "slug",
        SlugController,
        `
        <div data-controller="slug" ${values}>
            <input name="title" data-slug-target="source" value="${sourceValue}" />
            <input name="slug" data-slug-target="slug" value="${slugValue}" />
            ${preview ? '<span data-slug-target="preview"></span>' : ""}
        </div>`,
    );
}
