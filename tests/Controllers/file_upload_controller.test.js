import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Application } from "@hotwired/stimulus";
import { Window } from "happy-dom";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

// --- Dropzone mock ---
// Capture options at construction, register/emit handlers, count destroy calls.

const dzState = {
    instance: null,
    options: null,
    handlers: {},
    destroyed: 0,
};

class FakeDropzone {
    constructor(element, options) {
        this.element = element;
        this.options = options;
        this.hiddenFileInput = { click: mock(() => {}) };
        dzState.instance = this;
        dzState.options = options;
    }
    on(name, fn) {
        (dzState.handlers[name] ||= []).push(fn);
        return this;
    }
    emit(name, ...args) {
        (dzState.handlers[name] ?? []).forEach((fn) => fn(...args));
    }
    destroy() {
        dzState.destroyed++;
    }
}

FakeDropzone.autoDiscover = true;

mock.module("@deltablot/dropzone", () => ({ default: FakeDropzone }));
mock.module("@deltablot/dropzone/dist/dropzone.css", () => ({}));

// --- fetch mock ---
const fetchCalls = [];
globalThis.fetch = mock((url, init) => {
    fetchCalls.push({ url, init });
    return Promise.resolve({ ok: true });
});

const FileUploadController = (await import("../../resources/js/controllers/file_upload_controller.js")).default;

let mounted;

beforeEach(() => {
    dzState.instance = null;
    dzState.options = null;
    dzState.handlers = {};
    dzState.destroyed = 0;
    fetchCalls.length = 0;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = undefined;
});

function defaultHtml(extraAttrs = "") {
    return `
        <div data-controller="file-upload"
             data-file-upload-url-value="/upload"
             data-file-upload-hidden-name-value="avatar"
             ${extraAttrs}>
            <div role="status" data-file-upload-target="announcer"></div>
        </div>
    `;
}

async function mount(html = defaultHtml()) {
    mounted = await mountController("file-upload", FileUploadController, html);
}

// --- Construction ---

test("constructs Dropzone on the controller element with the configured url", async () => {
    await mount();
    expect(dzState.instance.element).toBe(mounted.root);
    expect(dzState.options.url).toBe("/upload");
});

test("forwards defaults when only url is set", async () => {
    await mount();
    expect(dzState.options.paramName).toBe("file");
    expect(dzState.options.parallelUploads).toBe(3);
    expect(dzState.options.uploadMultiple).toBe(false);
    expect(dzState.options.acceptedFiles).toBeNull();
    expect(dzState.options.maxFilesize).toBeNull();
    expect(dzState.options.maxFiles).toBeNull();
});

test("converts max-size-bytes to Dropzone's maxFilesize (MB)", async () => {
    await mount(
        defaultHtml('data-file-upload-max-size-bytes-value="10485760"')
    );
    expect(dzState.options.maxFilesize).toBe(10);
});

test("passes accept, max-files, parallel-uploads, param-name through to Dropzone", async () => {
    await mount(
        defaultHtml(`
            data-file-upload-accept-value="image/*"
            data-file-upload-max-files-value="5"
            data-file-upload-parallel-uploads-value="2"
            data-file-upload-param-name-value="upload"
        `)
    );
    expect(dzState.options.acceptedFiles).toBe("image/*");
    expect(dzState.options.maxFiles).toBe(5);
    expect(dzState.options.parallelUploads).toBe(2);
    expect(dzState.options.paramName).toBe("upload");
});

test("uses previewsContainer:false when preview value is disabled", async () => {
    await mount(defaultHtml('data-file-upload-preview-value="false"'));
    expect(dzState.options.previewsContainer).toBe(false);
});

test("leaves previewsContainer unset when preview defaults to true", async () => {
    await mount();
    expect(dzState.options.previewsContainer).toBeUndefined();
});

// --- CSRF header ---

test("omits CSRF header when no meta tag is present", async () => {
    await mount();
    expect(dzState.options.headers).toEqual({});
});

test("reads CSRF token from <meta> and forwards to Dropzone headers", async () => {
    // Manual bootstrap so the meta tag is present in `document.head` before the
    // controller's connect() runs. mountController() makes a fresh Window per call,
    // so setting the meta in the previous test's document does not survive.
    const testWindow = new Window({ url: "http://localhost" });
    testWindow.SyntaxError = SyntaxError;
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.HTMLElement = testWindow.HTMLElement;
    globalThis.Event = testWindow.Event;
    globalThis.CustomEvent = testWindow.CustomEvent;

    document.head.innerHTML = '<meta name="csrf-token" content="tok-abc">';
    document.body.innerHTML = `
        <div data-controller="file-upload"
             data-file-upload-url-value="/upload"
             data-file-upload-hidden-name-value="avatar"></div>
    `;

    const application = Application.start(document.body);
    application.register("file-upload", FileUploadController);
    await wait(0);

    try {
        expect(dzState.options.headers).toEqual({ "X-CSRF-TOKEN": "tok-abc" });
    } finally {
        application.stop();
        testWindow.close();
    }
});

// --- Dispatched Stimulus events ---

test("dispatches file-upload:added with the file", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:added", (e) => captured.push(e.detail));

    const file = { name: "x.png", size: 10 };
    dzState.instance.emit("addedfile", file);

    expect(captured).toEqual([{ file }]);
});

test("dispatches file-upload:progress with percent and bytes", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:progress", (e) => captured.push(e.detail));

    const file = { name: "x.png" };
    dzState.instance.emit("uploadprogress", file, 42, 4200);

    expect(captured).toEqual([{ file, percent: 42, bytes: 4200 }]);
});

test("dispatches file-upload:success with file, response and extracted value", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:success", (e) => captured.push(e.detail));

    const file = { name: "x.png" };
    const response = { token: "abc" };
    dzState.instance.emit("success", file, response);

    expect(captured).toEqual([{ file, response, value: "abc" }]);
});

test("dispatches file-upload:error with message, xhr, and normalized text", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:error", (e) => captured.push(e.detail));

    const file = { name: "x.png" };
    const xhr = { status: 500 };
    dzState.instance.emit("error", file, "boom", xhr);

    expect(captured).toEqual([{ file, message: "boom", xhr, text: "boom" }]);
});

test("normalizes a Laravel JSON error response (`{ message }`) for the announcer, thumb, and event text", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:error", (e) => captured.push(e.detail));

    const previewElement = document.createElement("div");
    const errMsg = document.createElement("span");
    errMsg.setAttribute("data-dz-errormessage", "");
    previewElement.appendChild(errMsg);

    const file = { name: "x.png", previewElement };
    dzState.instance.emit("error", file, { message: "File too large" }, { status: 422 });

    expect(errMsg.textContent).toBe("File too large");

    const announcer = mounted.root.querySelector('[data-file-upload-target="announcer"]');
    expect(announcer.textContent).toContain("File too large");
    expect(announcer.textContent.toLowerCase()).toContain("failed");

    expect(captured[0].text).toBe("File too large");
    expect(captured[0].message).toEqual({ message: "File too large" });
});

test("normalizes a Laravel 422 validation response (`{ errors: { field: [...] } }`) using the first field error", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:error", (e) => captured.push(e.detail));

    dzState.instance.emit("error", { name: "x.png" }, {
        message: "The given data was invalid.",
        errors: { file: ["The file must be an image.", "The file may not be greater than 5120 kilobytes."] },
    }, { status: 422 });

    // Prefer the actual field error over the generic "The given data was invalid"
    expect(captured[0].text).toBe("The file must be an image.");
});

test("falls back to a generic message when the response object has neither a message nor errors", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:error", (e) => captured.push(e.detail));

    dzState.instance.emit("error", { name: "x.png" }, { weird: true }, { status: 500 });

    expect(captured[0].text).toBe("Upload failed");
});

test("falls back to a generic message when the response is null or undefined", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:error", (e) => captured.push(e.detail));

    dzState.instance.emit("error", { name: "x.png" }, null, { status: 500 });

    expect(captured[0].text).toBe("Upload failed");
});

test("dispatches file-upload:removed with the file", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:removed", (e) => captured.push(e.detail));

    const file = { name: "x.png" };
    dzState.instance.emit("removedfile", file);

    expect(captured).toEqual([{ file }]);
});

// --- Value extraction (response-key) ---

test("extracts the value using the default response-key 'token'", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:success", (e) => captured.push(e.detail.value));

    dzState.instance.emit("success", { name: "x.png" }, { token: "abc", url: "/x" });

    expect(captured).toEqual(["abc"]);
});

test("extracts the value using a custom response-key", async () => {
    await mount(defaultHtml('data-file-upload-response-key-value="uuid"'));
    const captured = [];
    mounted.root.addEventListener("file-upload:success", (e) => captured.push(e.detail.value));

    dzState.instance.emit("success", { name: "x.png" }, { uuid: "01HQVZ", token: "ignored" });

    expect(captured).toEqual(["01HQVZ"]);
});

test("treats a plain-string response as the raw value", async () => {
    await mount();
    const captured = [];
    mounted.root.addEventListener("file-upload:success", (e) => captured.push(e.detail.value));

    dzState.instance.emit("success", { name: "x.png" }, "raw-value");

    expect(captured).toEqual(["raw-value"]);
});

// --- Hidden input ---

test("appends a hidden input with the extracted value on success", async () => {
    await mount();
    dzState.instance.emit("success", { name: "x.png" }, { token: "abc" });

    const hidden = mounted.root.querySelector('input[type="hidden"]');
    expect(hidden).not.toBeNull();
    expect(hidden.name).toBe("avatar");
    expect(hidden.value).toBe("abc");
});

test("appends one hidden input per successful file when name is bracketed", async () => {
    const html = defaultHtml().replace(
        'data-file-upload-hidden-name-value="avatar"',
        'data-file-upload-hidden-name-value="attachments[]"'
    );
    await mount(html);

    dzState.instance.emit("success", { name: "a" }, { token: "t1" });
    dzState.instance.emit("success", { name: "b" }, { token: "t2" });

    const hiddens = mounted.root.querySelectorAll('input[type="hidden"]');
    expect(hiddens.length).toBe(2);
    expect([...hiddens].map((h) => h.value)).toEqual(["t1", "t2"]);
    expect([...hiddens].every((h) => h.name === "attachments[]")).toBe(true);
});

test("skips the hidden input when emit-hidden is disabled", async () => {
    await mount(defaultHtml('data-file-upload-emit-hidden-value="false"'));
    dzState.instance.emit("success", { name: "x.png" }, { token: "abc" });

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("skips the hidden input when extracted value is null", async () => {
    await mount();
    dzState.instance.emit("success", { name: "x.png" }, { not_the_token: "abc" });

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("removes the matching hidden input on removedfile", async () => {
    await mount();
    const file = { name: "x.png" };
    dzState.instance.emit("success", file, { token: "abc" });
    expect(mounted.root.querySelector('input[type="hidden"]')).not.toBeNull();

    dzState.instance.emit("removedfile", file);
    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

// --- Preserved hiddens (Laravel old() / value prop) ---

test("single mode — a new upload replaces a pre-existing preserved hidden", async () => {
    const html = `
        <div data-controller="file-upload"
             data-file-upload-url-value="/upload"
             data-file-upload-hidden-name-value="avatar">
            <input type="hidden" name="avatar" value="old-token" data-hw-upload-preserved>
            <div role="status" data-file-upload-target="announcer"></div>
        </div>
    `;
    await mount(html);

    expect(mounted.root.querySelectorAll('input[type="hidden"][name="avatar"]').length).toBe(1);

    dzState.instance.emit("success", { name: "x.png" }, { token: "new-token" });

    const hiddens = mounted.root.querySelectorAll('input[type="hidden"][name="avatar"]');
    expect(hiddens.length).toBe(1);
    expect(hiddens[0].value).toBe("new-token");
});

test("multi mode — new uploads keep pre-existing preserved hiddens (the list accumulates)", async () => {
    const html = `
        <div data-controller="file-upload"
             data-file-upload-url-value="/upload"
             data-file-upload-hidden-name-value="attachments[]"
             data-file-upload-multiple-value="true">
            <input type="hidden" name="attachments[]" value="old-a" data-hw-upload-preserved>
            <input type="hidden" name="attachments[]" value="old-b" data-hw-upload-preserved>
            <div role="status" data-file-upload-target="announcer"></div>
        </div>
    `;
    await mount(html);

    dzState.instance.emit("success", { name: "x.png" }, { token: "new-c" });

    const hiddens = mounted.root.querySelectorAll('input[type="hidden"][name="attachments[]"]');
    expect(hiddens.length).toBe(3);
    expect([...hiddens].map((h) => h.value)).toEqual(["old-a", "old-b", "new-c"]);
});

test("emit-hidden=false leaves preserved hiddens untouched (the form controls the hiddens server-side)", async () => {
    const html = `
        <div data-controller="file-upload"
             data-file-upload-url-value="/upload"
             data-file-upload-hidden-name-value="avatar"
             data-file-upload-emit-hidden-value="false">
            <input type="hidden" name="avatar" value="old-token" data-hw-upload-preserved>
            <div role="status" data-file-upload-target="announcer"></div>
        </div>
    `;
    await mount(html);

    dzState.instance.emit("success", { name: "x.png" }, { token: "new-token" });

    const hiddens = mounted.root.querySelectorAll('input[type="hidden"]');
    expect(hiddens.length).toBe(1);
    expect(hiddens[0].value).toBe("old-token");
});

// --- DELETE on remove ---

test("fires DELETE with token-substituted URL on removedfile when delete-url is set", async () => {
    await mount(
        defaultHtml('data-file-upload-delete-url-value="/uploads/:token"')
    );
    const file = { name: "x.png" };
    dzState.instance.emit("success", file, { token: "abc" });
    dzState.instance.emit("removedfile", file);

    await wait(0);
    expect(fetchCalls).toEqual([
        { url: "/uploads/abc", init: { method: "DELETE", headers: {} } },
    ]);
});

test("does not call fetch on remove when delete-url is unset", async () => {
    await mount();
    const file = { name: "x.png" };
    dzState.instance.emit("success", file, { token: "abc" });
    dzState.instance.emit("removedfile", file);

    await wait(0);
    expect(fetchCalls.length).toBe(0);
});

test("does not call fetch when removing a file that never succeeded", async () => {
    await mount(
        defaultHtml('data-file-upload-delete-url-value="/uploads/:token"')
    );
    dzState.instance.emit("removedfile", { name: "x.png" });

    await wait(0);
    expect(fetchCalls.length).toBe(0);
});

// --- Announcer ---

test("writes an upload-started message to the announcer on addedfile", async () => {
    await mount();
    dzState.instance.emit("addedfile", { name: "photo.png" });

    const announcer = mounted.root.querySelector('[data-file-upload-target="announcer"]');
    expect(announcer.textContent).toContain("photo.png");
});

test("writes an upload-success message on success", async () => {
    await mount();
    dzState.instance.emit("success", { name: "photo.png" }, { token: "abc" });

    const announcer = mounted.root.querySelector('[data-file-upload-target="announcer"]');
    expect(announcer.textContent.toLowerCase()).toContain("uploaded");
    expect(announcer.textContent).toContain("photo.png");
});

test("writes an error message on error including the failure reason", async () => {
    await mount();
    dzState.instance.emit("error", { name: "photo.png" }, "Too large", null);

    const announcer = mounted.root.querySelector('[data-file-upload-target="announcer"]');
    expect(announcer.textContent.toLowerCase()).toContain("failed");
    expect(announcer.textContent).toContain("Too large");
});

test("does not write to the announcer on uploadprogress (avoid screen reader noise)", async () => {
    await mount();
    const announcer = mounted.root.querySelector('[data-file-upload-target="announcer"]');
    announcer.textContent = "Uploading photo.png";

    dzState.instance.emit("uploadprogress", { name: "photo.png" }, 50, 5000);

    expect(announcer.textContent).toBe("Uploading photo.png");
});

// --- Keyboard activation (openPicker) ---

test("openPicker clicks Dropzone's hidden file input", async () => {
    await mount();
    mounted.controller.openPicker(new Event("keydown"));

    expect(dzState.instance.hiddenFileInput.click).toHaveBeenCalledTimes(1);
});

test("openPicker calls preventDefault on the triggering event so Space does not scroll", async () => {
    await mount();
    let prevented = false;
    const event = { preventDefault: () => (prevented = true) };

    mounted.controller.openPicker(event);

    expect(prevented).toBe(true);
});

test("openPicker is a no-op when Dropzone has no hidden file input yet", async () => {
    await mount();
    dzState.instance.hiddenFileInput = null;

    expect(() => mounted.controller.openPicker(new Event("keydown"))).not.toThrow();
});

// --- Cleanup ---

test("destroys Dropzone on disconnect", async () => {
    await mount();
    mounted.controller.disconnect();

    expect(dzState.destroyed).toBe(1);
});
