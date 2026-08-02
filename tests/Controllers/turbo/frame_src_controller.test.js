import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../../resources/js/helpers/test_stimulus.js";
import FrameSrcController from "../../../resources/js/controllers/turbo/frame_src_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test("uses the URL that rendered the form's frame", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create?status=in_progress">
            <form data-controller="frame-src" method="post" action="/tasks">
                <input name="title" />
            </form>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"])
        .toBe("http://localhost/tasks/create?status=in_progress");
});

test("lets a controller on the frame serve descendant forms", async () => {
    await mount(`
        <turbo-frame
            id="modal"
            src="tasks/create?status=pending"
            data-controller="frame-src"
        >
            <form id="task-form" method="post" action="/tasks"></form>
        </turbo-frame>
    `);

    const form = document.querySelector("#task-form");
    const event = dispatchRequest(form, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"])
        .toBe("http://localhost/tasks/create?status=pending");
});

test("lets a controller on a generic ancestor serve descendant forms", async () => {
    await mount(`
        <section data-controller="frame-src">
            <turbo-frame id="modal" src="/tasks/create">
                <form id="task-form" method="post" action="/tasks"></form>
            </turbo-frame>
        </section>
    `);

    const event = dispatchRequest(document.querySelector("#task-form"), { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"])
        .toBe("http://localhost/tasks/create");
});

test("resolves path-relative sources against the document base URI", async () => {
    await mount(`
        <base href="/admin/">
        <turbo-frame id="modal" src="tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"])
        .toBe("http://localhost/admin/tasks/create");
});

test("falls back to the document URL for an inline top-level frame", async () => {
    await mount(`
        <base href="/admin/">
        <turbo-frame id="modal">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"])
        .toBe("http://localhost/");
});

test("inherits the source of an ancestor frame for an inline nested frame", async () => {
    await mount(`
        <turbo-frame id="wizard" src="/wizard/step-2?plan=pro">
            <turbo-frame id="modal">
                <form data-controller="frame-src" method="post" action="/tasks"></form>
            </turbo-frame>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"])
        .toBe("http://localhost/wizard/step-2?plan=pro");
});

test("does not replace an invalid nearest source with an ancestor or document URL", async () => {
    await mount(`
        <turbo-frame id="wizard" src="/wizard/step-2">
            <turbo-frame id="modal" src="http://[invalid">
                <form data-controller="frame-src" method="post" action="/tasks"></form>
            </turbo-frame>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("does not set a source when the form targets another frame", async () => {
    await mount(`
        <turbo-frame id="editor" src="/tasks/1/edit">
            <form data-controller="frame-src" method="post" action="/tasks/1"></form>
        </turbo-frame>
        <turbo-frame id="modal"></turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("does not set a source when the form targets the parent browsing context", async () => {
    await mount(`
        <turbo-frame id="editor" src="/tasks/1/edit">
            <form data-controller="frame-src" method="post" action="/tasks/1"></form>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "_parent" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("does not set a source for requests outside a frame", async () => {
    await mount(`
        <form data-controller="frame-src" method="post" action="/tasks"></form>
        <turbo-frame id="modal"></turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("does not set a source without a Turbo-Frame header", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, {});

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("reads plain object headers case-insensitively", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);
    const headers = { "turbo-frame": "modal" };

    dispatchRequest(mounted.root, headers);

    expect(headers["X-Turbo-Frame-Src"]).toBe("http://localhost/tasks/create");
});

test("does not affect requests dispatched by sibling frames", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
        <turbo-frame id="settings" src="/settings/edit">
            <form id="settings-form" method="post" action="/settings"></form>
        </turbo-frame>
    `);

    const event = dispatchRequest(document.querySelector("#settings-form"), {
        "Turbo-Frame": "settings",
    });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("preserves an explicitly supplied source header case-insensitively", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);
    const headers = {
        "Turbo-Frame": "modal",
        "x-turbo-frame-src": "/explicit/source",
    };

    dispatchRequest(mounted.root, headers);

    expect(headers["x-turbo-frame-src"]).toBe("/explicit/source");
    expect(headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("supports Headers instances without replacing the container", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);
    const headers = new mounted.window.Headers({ "Turbo-Frame": "modal" });

    const event = dispatchRequest(mounted.root, headers);

    expect(event.detail.fetchOptions.headers).toBe(headers);
    expect(headers.get("X-Turbo-Frame-Src")).toBe("http://localhost/tasks/create");
});

test("preserves an explicit source in a Headers instance", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);
    const headers = new mounted.window.Headers({
        "Turbo-Frame": "modal",
        "x-turbo-frame-src": "/explicit/source",
    });

    dispatchRequest(mounted.root, headers);

    expect(headers.get("X-Turbo-Frame-Src")).toBe("/explicit/source");
});

test("sets the source once when multiple instances observe the same form", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create" data-controller="frame-src">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);
    let writes = 0;
    const headers = new Proxy({ "Turbo-Frame": "modal" }, {
        set(target, key, value) {
            if (key === "X-Turbo-Frame-Src") writes++;

            return Reflect.set(target, key, value);
        },
    });

    dispatchRequest(document.querySelector("form"), headers);

    expect(headers["X-Turbo-Frame-Src"]).toBe("http://localhost/tasks/create");
    expect(writes).toBe(1);
});

test("keeps multiple sibling frame instances isolated", async () => {
    await mount(`
        <main data-controller="frame-src">
            <turbo-frame id="tasks" src="/tasks/create">
                <form id="task-form" data-controller="frame-src" method="post" action="/tasks"></form>
            </turbo-frame>
            <turbo-frame id="settings" src="/settings/edit">
                <form id="settings-form" data-controller="frame-src" method="post" action="/settings"></form>
            </turbo-frame>
        </main>
    `);
    const taskHeaders = { "Turbo-Frame": "tasks" };
    const settingsHeaders = { "Turbo-Frame": "settings" };

    dispatchRequest(document.querySelector("#task-form"), taskHeaders);
    dispatchRequest(document.querySelector("#settings-form"), settingsHeaders);

    expect(taskHeaders["X-Turbo-Frame-Src"]).toBe("http://localhost/tasks/create");
    expect(settingsHeaders["X-Turbo-Frame-Src"]).toBe("http://localhost/settings/edit");
});

test("ignores frame source loads because they are not form submissions", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create" data-controller="frame-src"></turbo-frame>
    `);

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test("does not inject a source after disconnect", async () => {
    await mount(`
        <turbo-frame id="modal" src="/tasks/create">
            <form data-controller="frame-src" method="post" action="/tasks"></form>
        </turbo-frame>
    `);
    mounted.controller.disconnect();

    const event = dispatchRequest(mounted.root, { "Turbo-Frame": "modal" });

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

async function mount(html) {
    mounted = await mountController("frame-src", FrameSrcController, html);
    await wait(0);
}

function dispatchRequest(target, headers) {
    const event = new CustomEvent("turbo:before-fetch-request", {
        bubbles: true,
        detail: {
            fetchOptions: { headers },
        },
    });

    target.dispatchEvent(event);

    return event;
}
