import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import CleanQueryParamsController from "../../resources/js/controllers/clean_query_params_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- formdata listener ---

test("removes empty string fields from formdata", async () => {
    await setup(`<form data-controller="clean-query-params"></form>`);

    const form = document.querySelector("form");
    const formData = dispatchFormData(form, { q: "", category: "news" });

    expect(has(formData, "q")).toBe(false);
    expect(formData.get("category")).toBe("news");
});

test("keeps all fields when none are empty", async () => {
    await setup(`<form data-controller="clean-query-params"></form>`);

    const form = document.querySelector("form");
    const formData = dispatchFormData(form, { q: "hello", category: "news" });

    expect(formData.get("q")).toBe("hello");
    expect(formData.get("category")).toBe("news");
});

test("removes all fields when all are empty", async () => {
    await setup(`<form data-controller="clean-query-params"></form>`);

    const form = document.querySelector("form");
    const formData = dispatchFormData(form, { q: "", category: "", page: "" });

    expect(has(formData, "q")).toBe(false);
    expect(has(formData, "category")).toBe(false);
    expect(has(formData, "page")).toBe(false);
});

test("does not clean formdata after disconnect", async () => {
    await setup(`<form data-controller="clean-query-params"></form>`);

    const form = document.querySelector("form");

    mounted.controller.disconnect();

    const formData = dispatchFormData(form, { q: "", category: "news" });

    expect(has(formData, "q")).toBe(true);
    expect(formData.get("category")).toBe("news");
});

// --- helpers ---

function dispatchFormData(form, fields) {
    const formData = new FormData();
    for (const [name, value] of Object.entries(fields)) {
        formData.append(name, value);
    }
    const event = new Event("formdata", { bubbles: true });
    event.formData = formData;
    form.dispatchEvent(event);
    return formData;
}

function has(formData, name) {
    return formData.has(name);
}

async function setup(html) {
    mounted = await mountController("clean-query-params", CleanQueryParamsController, html);
}
