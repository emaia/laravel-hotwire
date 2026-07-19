import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

const FileUploadController = (await import("../../resources/js/controllers/file_upload_controller.js")).default;

let mounted;
let requests;
let fetchCalls;
let objectUrls;
let revokedUrls;
let originalCreateObjectURL;
let originalRevokeObjectURL;

beforeEach(() => {
    requests = [];
    fetchCalls = [];
    objectUrls = [];
    revokedUrls = [];
    originalCreateObjectURL = globalThis.URL?.createObjectURL;
    originalRevokeObjectURL = globalThis.URL?.revokeObjectURL;
    globalThis.XMLHttpRequest = FakeXMLHttpRequest;
    globalThis.URL.createObjectURL = mock((blob) => {
        const url = `blob:${blob.name}-${objectUrls.length}`;
        objectUrls.push({ blob, url });

        return url;
    });
    globalThis.URL.revokeObjectURL = mock((url) => revokedUrls.push(url));
    globalThis.fetch = mock((url, init) => {
        fetchCalls.push({ url, init });
        return Promise.resolve({ ok: true });
    });
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = undefined;
    delete globalThis.Turbo;
    if (originalCreateObjectURL) {
        globalThis.URL.createObjectURL = originalCreateObjectURL;
    } else {
        delete globalThis.URL.createObjectURL;
    }

    if (originalRevokeObjectURL) {
        globalThis.URL.revokeObjectURL = originalRevokeObjectURL;
    } else {
        delete globalThis.URL.revokeObjectURL;
    }
});

function defaultHtml(extraAttrs = "", extraChildren = "", controllers = "file-upload") {
    return `
        <form id="parent-form">
            <div data-controller="${controllers}"
                  data-file-upload-url-value="/upload"
                  data-file-upload-hidden-name-value="avatar"
                  ${extraAttrs}>
                <input type="file" hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-file-upload-target="dropzone"></div>
                <div data-slot="file-upload-actions">
                    <button type="button" hidden data-file-upload-clear data-action="file-upload#clear">Clear all</button>
                </div>
                <div data-file-upload-target="list"></div>
                <template data-file-upload-target="template">
                    <div data-slot="attachment" data-state="idle" data-file-upload-attachment>
                        <div data-slot="attachment-media" data-variant="icon"><svg></svg></div>
                        <span data-file-upload-name></span>
                        <span data-file-upload-description></span>
                        <div data-file-upload-progress hidden>
                            <div data-slot="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-value="0" data-max="100" style="--progress-value: 0%">
                                <div data-slot="progress-track"><div data-slot="progress-indicator"></div></div>
                            </div>
                        </div>
                        <button type="button" hidden data-file-upload-retry data-action="file-upload#retry">Retry</button>
                        <button type="button" data-file-upload-remove data-action="file-upload#remove">Remove</button>
                    </div>
                </template>
                <div role="status" data-file-upload-target="announcer"></div>
                ${extraChildren}
            </div>
        </form>
    `;
}

async function mount(html = defaultHtml()) {
    mounted = await mountController("file-upload", FileUploadController, html);
}

function file(name, { type = "text/plain", size = 4 } = {}) {
    return new File([new Uint8Array(size)], name, { type });
}

// --- Selection and queueing ---

test("select adds files, starts a native XHR upload and dispatches added", async () => {
    await mount();
    const added = [];
    mounted.root.addEventListener("file-upload:added", (event) => added.push(event.detail));

    const upload = file("photo.png", { type: "image/png" });
    mounted.controller.select({ target: { files: [upload], value: "x" } });

    expect(added).toEqual([{ file: upload }]);
    expect(requests).toHaveLength(1);
    expect(requests[0].method).toBe("POST");
    expect(requests[0].url).toBe("/upload");
    expect(requests[0].body).toBeInstanceOf(FormData);
    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("uploading");
    expect(mounted.root.querySelector("[data-file-upload-clear]").hidden).toBe(false);
});

test("drop adds files and toggles drag state off", async () => {
    await mount();
    const dropped = file("document.pdf", { type: "application/pdf" });
    let prevented = false;

    mounted.controller.dragEnter({ preventDefault() {}, currentTarget: mounted.root });
    expect(mounted.root.dataset.dragging).toBe("true");

    mounted.controller.drop({
        preventDefault: () => (prevented = true),
        dataTransfer: { files: [dropped] },
    });

    expect(prevented).toBe(true);
    expect(mounted.root.dataset.dragging).toBe("false");
    expect(requests).toHaveLength(1);
});

test("openPicker clicks the native file input and prevents default", async () => {
    await mount();
    const input = mounted.root.querySelector('[data-file-upload-target="input"]');
    input.click = mock(() => {});
    let prevented = false;

    mounted.controller.openPicker({ preventDefault: () => (prevented = true) });

    expect(prevented).toBe(true);
    expect(input.click).toHaveBeenCalledTimes(1);
});

test("select clears the file input by default so the same file can be selected again", async () => {
    await mount();
    const target = { files: [file("photo.png")], value: "C:\\fakepath\\photo.png" };

    mounted.controller.select({ target });

    expect(target.value).toBe("");
});

test("select keeps the file input value when file preservation controllers are stacked", async () => {
    await mount(defaultHtml("", "", "file-upload file-preserve reset-files"));
    const target = { files: [file("photo.png")], value: "C:\\fakepath\\photo.png" };

    mounted.controller.select({ target });

    expect(target.value).toBe("C:\\fakepath\\photo.png");
});

// --- Validation ---

test("rejects files that do not match accept", async () => {
    await mount(defaultHtml('data-file-upload-accept-value="image/*,.pdf"'));
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail));

    const upload = file("notes.txt", { type: "text/plain" });
    mounted.controller.select({ target: { files: [upload], value: "x" } });

    expect(requests).toHaveLength(0);
    expect(errors[0].file).toBe(upload);
    expect(errors[0].text).toBe("File type is not allowed");
    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("error");
});

test("rejects files over max-size-bytes", async () => {
    await mount(defaultHtml('data-file-upload-max-size-bytes-value="3"'));
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    mounted.controller.select({ target: { files: [file("huge.zip", { size: 4 })], value: "x" } });

    expect(requests).toHaveLength(0);
    expect(errors).toEqual(["File is too large"]);
});

test("rejects files beyond max-files", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-max-files-value="1"'));
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    mounted.controller.select({ target: { files: [file("a.txt"), file("b.txt")], value: "x" } });

    expect(requests).toHaveLength(1);
    expect(errors).toEqual(["Maximum number of files reached"]);
});

test("rejected files do not count against max-files", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-max-files-value="1" data-file-upload-max-size-bytes-value="3"'));
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    mounted.controller.select({ target: { files: [file("huge.zip", { size: 4 })], value: "x" } });
    mounted.controller.select({ target: { files: [file("small.zip", { size: 3 })], value: "x" } });

    expect(requests).toHaveLength(1);
    expect(errors).toEqual(["File is too large"]);
    expect(mounted.root.querySelectorAll('[data-slot="attachment"]')).toHaveLength(2);
});

test("multiple mode ignores duplicate files", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true"'));
    const upload = file("photo.png", { type: "image/png", size: 4 });

    mounted.controller.select({ target: { files: [upload, upload], value: "x" } });
    mounted.controller.select({ target: { files: [upload], value: "x" } });

    expect(requests).toHaveLength(1);
    expect(mounted.root.querySelectorAll('[data-slot="attachment"]')).toHaveLength(1);
});

// --- Progress and completion ---

test("progress updates the attachment progressbar and dispatches progress", async () => {
    await mount();
    const progress = [];
    mounted.root.addEventListener("file-upload:progress", (event) => progress.push(event.detail));

    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].progress(32, 100);

    const bar = mounted.root.querySelector('[data-slot="progress"]');
    expect(progress).toEqual([{ file: upload, percent: 32, bytes: 32 }]);
    expect(bar.dataset.value).toBe("32");
    expect(bar.getAttribute("aria-valuenow")).toBe("32");
    expect(bar.getAttribute("style")).toContain("--progress-value: 32%");
});

test("success appends a hidden input, marks the attachment done and dispatches success", async () => {
    await mount();
    const successes = [];
    mounted.root.addEventListener("file-upload:success", (event) => successes.push(event.detail));

    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].respond(201, { token: "abc" });
    await wait(0);

    const hidden = mounted.root.querySelector('input[type="hidden"][name="avatar"]');
    expect(hidden.value).toBe("abc");
    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("done");
    expect(mounted.root.querySelector('[data-file-upload-description]')?.textContent).toContain("Uploaded");
    expect(successes).toEqual([{ file: upload, response: { token: "abc" }, value: "abc" }]);
});

test("single mode replaces preserved hiddens when a new upload succeeds", async () => {
    await mount(defaultHtml("", '<input type="hidden" name="avatar" value="old-token" data-hw-upload-preserved>'));

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "new-token" });
    await wait(0);

    const hiddens = mounted.root.querySelectorAll('input[type="hidden"][name="avatar"]');
    expect(hiddens).toHaveLength(1);
    expect(hiddens[0].value).toBe("new-token");
});

test("emit-hidden=false skips hidden input append", async () => {
    await mount(defaultHtml('data-file-upload-emit-hidden-value="false"'));

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "abc" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("plain string responses are treated as the uploaded value", async () => {
    await mount();

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, "raw-token", { "content-type": "text/plain" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]').value).toBe("raw-token");
});

test("custom response-key extracts a different response property", async () => {
    await mount(defaultHtml('data-file-upload-response-key-value="uuid"'));

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { uuid: "01HX" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]').value).toBe("01HX");
});

// --- Error and Turbo Streams ---

test("server errors normalize Laravel validation JSON and mark the attachment error", async () => {
    await mount();
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail));

    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].respond(422, { message: "Invalid", errors: { file: ["The file must be an image."] } });
    await wait(0);

    expect(errors[0].text).toBe("The file must be an image.");
    const attachment = mounted.root.querySelector('[data-slot="attachment"]');
    const description = mounted.root.querySelector('[data-file-upload-description]');
    expect(attachment?.dataset.state).toBe("error");
    expect(attachment?.getAttribute("aria-invalid")).toBe("true");
    expect(description?.getAttribute("role")).toBe("alert");
    expect(description?.textContent).toBe("The file must be an image.");
});

test("retry requeues retryable server errors in the same attachment", async () => {
    await mount();
    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });

    requests[0].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    const attachment = mounted.root.querySelector('[data-slot="attachment"]');
    const retry = mounted.root.querySelector("[data-file-upload-retry]");
    const id = attachment.dataset.fileUploadId;
    expect(attachment.dataset.state).toBe("error");
    expect(retry.hidden).toBe(false);

    mounted.controller.retry({ preventDefault() {}, params: { id } });

    expect(requests).toHaveLength(2);
    expect(attachment.dataset.state).toBe("uploading");
    expect(retry.hidden).toBe(true);

    requests[1].respond(201, { token: "retry-token" });
    await wait(0);

    expect(attachment.dataset.state).toBe("done");
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("retry-token");
});

test("network errors with status zero are retryable", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });

    requests[0].fail(0);
    await wait(0);

    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("error");
    expect(mounted.root.querySelector("[data-file-upload-retry]").hidden).toBe(false);
});

test("validation errors do not expose retry action", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(422, { message: "Invalid", errors: { file: ["The file must be an image."] } });
    await wait(0);

    expect(mounted.root.querySelector("[data-file-upload-retry]").hidden).toBe(true);
});

test("413 HTML responses use the file-too-big message instead of rendering the response body", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true"'));
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    mounted.controller.select({
        target: { files: [file("small.txt"), file("huge.zip", { size: 1024 })], value: "x" },
    });
    requests[0].respond(201, { token: "small" });
    requests[1].respond(
        413,
        "<!doctype html><html><body>Request Entity Too Large</body></html>",
        { "content-type": "text/html" }
    );
    await wait(0);

    const descriptions = [...mounted.root.querySelectorAll("[data-file-upload-description]")]
        .map((element) => element.textContent);

    expect(errors).toEqual(["File is too large"]);
    expect(descriptions).toContain("File is too large");
    expect(descriptions.some((text) => text.includes("<!doctype") || text.includes("<html"))).toBe(false);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("small");
});

test("HTML error pages fall back to the generic upload failure message", async () => {
    await mount();
    const errors = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(
        500,
        "<html><body>Server Error</body></html>",
        { "content-type": "text/html; charset=UTF-8" }
    );
    await wait(0);

    const description = mounted.root.querySelector("[data-file-upload-description]");
    expect(errors).toEqual(["Upload failed"]);
    expect(description?.textContent).toBe("Upload failed");
});

test("malformed JSON responses do not become hidden input values", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, "{not valid json", { "content-type": "application/json" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("done");
});

test("turbo-stream=true negotiates stream responses and renders stream success without hidden input", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml('data-file-upload-turbo-stream-value="true"'));

    const stream = '<turbo-stream action="append" target="files"><template>ok</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    expect(requests[0].headers.Accept).toBe("text/vnd.turbo-stream.html, application/json");
    requests[0].respond(200, stream, { "content-type": "text/vnd.turbo-stream.html" });
    await wait(0);

    expect(rendered).toEqual([stream]);
    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("turbo-stream error responses are rendered too", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml('data-file-upload-turbo-stream-value="true"'));

    const stream = '<turbo-stream action="replace" target="upload-error"><template>no</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(422, stream, { "content-type": "text/vnd.turbo-stream.html" });
    await wait(0);

    expect(rendered).toEqual([stream]);
});

test("turbo-stream=true only renders actual turbo-stream elements", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml('data-file-upload-turbo-stream-value="true"'));

    const upload = file("photo.png");
    const text = "stored <turbo-streamish>not a stream</turbo-streamish>";
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].respond(200, text, { "content-type": "text/html" });
    await wait(0);

    expect(rendered).toEqual([]);
    expect(mounted.root.querySelector('input[type="hidden"]').value).toBe(text);
});

// --- Removal and concurrency ---

test("remove aborts an in-flight upload and removes the attachment", async () => {
    await mount();
    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });

    const id = mounted.root.querySelector('[data-file-upload-id]').dataset.fileUploadId;
    mounted.controller.remove({ preventDefault() {}, params: { id: Number(id) } });

    expect(requests[0].aborted).toBe(true);
    expect(mounted.root.querySelector('[data-slot="attachment"]')).toBeNull();
});

test("late load callbacks after remove do not append orphan hidden inputs", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });

    const id = mounted.root.querySelector('[data-file-upload-id]').dataset.fileUploadId;
    mounted.controller.remove({ preventDefault() {}, params: { id } });
    requests[0].respond(201, { token: "late-token" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
    expect(mounted.root.querySelector('[data-slot="attachment"]')).toBeNull();
});

test("late load callbacks after disconnect do not append orphan hidden inputs", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });

    mounted.controller.disconnect();
    requests[0].respond(201, { token: "late-token" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("remove deletes a completed remote upload and removes its hidden input", async () => {
    await mount(defaultHtml('data-file-upload-delete-url-value="/uploads/:token/revisions/:token"'));
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "abc 123" });
    await wait(0);

    const id = mounted.root.querySelector('[data-file-upload-id]').dataset.fileUploadId;
    mounted.controller.remove({ preventDefault() {}, params: { id } });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
    expect(fetchCalls).toEqual([{ url: "/uploads/abc%20123/revisions/abc%20123", init: { method: "DELETE", headers: {} } }]);
});

test("clear aborts active uploads, deletes completed uploads and dispatches cleared", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-delete-url-value="/uploads/:token"'));
    const cleared = [];
    const removed = [];
    mounted.root.addEventListener("file-upload:cleared", (event) => cleared.push(event.detail));
    mounted.root.addEventListener("file-upload:removed", (event) => removed.push(event.detail));

    const uploaded = file("uploaded.txt");
    const active = file("active.txt");
    mounted.controller.select({ target: { files: [uploaded, active], value: "x" } });
    requests[0].respond(201, { token: "uploaded-token" });
    await wait(0);

    mounted.controller.clear({ preventDefault() {} });
    await wait(0);

    expect(requests[1].aborted).toBe(true);
    expect(mounted.root.querySelectorAll('[data-slot="attachment"]')).toHaveLength(0);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')).toBeNull();
    expect(mounted.root.querySelector("[data-file-upload-clear]").hidden).toBe(true);
    expect(mounted.root.querySelector("[data-file-upload-target='announcer']")?.textContent).toBe("Cleared files · 2");
    expect(fetchCalls).toEqual([{ url: "/uploads/uploaded-token", init: { method: "DELETE", headers: {} } }]);
    expect(cleared).toEqual([{ files: [uploaded, active], count: 2 }]);
    expect(removed).toEqual([]);
});

test("clear removes preserved hidden upload tokens even when no card is hydrated", async () => {
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true"',
        '<input type="hidden" name="avatar" value="old-a" data-hw-upload-preserved><input type="hidden" name="avatar" value="old-b" data-hw-upload-preserved>'
    ));
    const cleared = [];
    mounted.root.addEventListener("file-upload:cleared", (event) => cleared.push(event.detail));

    expect(mounted.root.querySelector("[data-file-upload-clear]").hidden).toBe(false);

    mounted.controller.clear({ preventDefault() {} });

    expect(mounted.root.querySelectorAll("[data-hw-upload-preserved]")).toHaveLength(0);
    expect(mounted.root.querySelector("[data-file-upload-clear]").hidden).toBe(true);
    expect(mounted.root.querySelector("[data-file-upload-target='announcer']")?.textContent).toBe("Cleared files · 2");
    expect(cleared).toEqual([{ files: [], count: 2 }]);
});

test("clear action stays visible while retrying a failed item", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    mounted.controller.retry({ preventDefault() {}, params: { id: "1" } });

    expect(mounted.root.querySelector("[data-file-upload-clear]").hidden).toBe(false);
});

test("grid view renders local thumbnails for image files and revokes object URLs", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-view-value="grid"'));

    const image = file("photo.png", { type: "image/png" });
    const pdf = file("document.pdf", { type: "application/pdf" });
    mounted.controller.select({ target: { files: [image, pdf], value: "x" } });

    const attachments = [...mounted.root.querySelectorAll('[data-slot="attachment"]')];
    const imageMedia = attachments[0].querySelector('[data-slot="attachment-media"]');
    const documentMedia = attachments[1].querySelector('[data-slot="attachment-media"]');

    expect(attachments.map((attachment) => attachment.dataset.orientation)).toEqual(["vertical", "vertical"]);
    expect(imageMedia.dataset.variant).toBe("image");
    expect(imageMedia.querySelector("img")?.getAttribute("src")).toBe("blob:photo.png-0");
    expect(imageMedia.querySelector("img")?.getAttribute("alt")).toBe("photo.png");
    expect(documentMedia.dataset.variant).toBe("icon");
    expect(documentMedia.querySelector("img")).toBeNull();

    const id = attachments[0].dataset.fileUploadId;
    mounted.controller.remove({ preventDefault() {}, params: { id } });

    expect(revokedUrls).toEqual(["blob:photo.png-0"]);
});

test("clear throttles remote deletes by parallel-uploads", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-delete-url-value="/uploads/:token" data-file-upload-parallel-uploads-value="2"'));
    mounted.controller.select({ target: { files: [file("a.txt"), file("b.txt"), file("c.txt")], value: "x" } });
    requests[0].respond(201, { token: "a" });
    requests[1].respond(201, { token: "b" });
    await wait(0);
    requests[2].respond(201, { token: "c" });
    await wait(0);

    const resolvers = [];
    const started = [];
    let activeDeletes = 0;
    let maxActiveDeletes = 0;
    globalThis.fetch = mock((url, init) => {
        started.push({ url, init });
        activeDeletes++;
        maxActiveDeletes = Math.max(maxActiveDeletes, activeDeletes);

        return new Promise((resolve) => {
            resolvers.push(() => {
                activeDeletes--;
                resolve({ ok: true });
            });
        });
    });

    mounted.controller.clear({ preventDefault() {} });
    await wait(0);

    expect(started.map((call) => call.url)).toEqual(["/uploads/a", "/uploads/b"]);
    expect(maxActiveDeletes).toBe(2);

    resolvers.shift()();
    await wait(0);

    expect(started.map((call) => call.url)).toEqual(["/uploads/a", "/uploads/b", "/uploads/c"]);
    expect(maxActiveDeletes).toBe(2);

    resolvers.forEach((resolve) => resolve());
});

test("reconnect derives the next upload id from existing attachment cards", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true"'));
    mounted.controller.select({ target: { files: [file("a.txt")], value: "x" } });
    requests[0].respond(201, { token: "a" });
    await wait(0);

    mounted.controller.disconnect();
    mounted.controller.connect();
    mounted.controller.select({ target: { files: [file("b.txt")], value: "x" } });

    const ids = [...mounted.root.querySelectorAll('[data-file-upload-attachment][data-file-upload-id]')]
        .map((element) => element.dataset.fileUploadId);
    expect(ids).toEqual(["1", "2"]);
});

test("reconnect hydrates completed uploads so remove still clears their hidden input", async () => {
    await mount(defaultHtml('data-file-upload-delete-url-value="/uploads/:token"'));
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "abc" });
    await wait(0);

    const id = mounted.root.querySelector('[data-file-upload-id]').dataset.fileUploadId;
    mounted.controller.disconnect();
    mounted.controller.connect();
    mounted.controller.remove({ preventDefault() {}, params: { id } });
    await wait(0);

    expect(mounted.root.querySelector('[data-slot="attachment"]')).toBeNull();
    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
    expect(fetchCalls).toEqual([{ url: "/uploads/abc", init: { method: "DELETE", headers: {} } }]);
});

test("parallel-uploads limits concurrent native XHRs", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="2"'));

    mounted.controller.select({ target: { files: [file("a.txt"), file("b.txt"), file("c.txt")], value: "x" } });
    expect(requests).toHaveLength(2);

    requests[0].respond(201, { token: "a" });
    await wait(0);
    expect(requests).toHaveLength(3);
});

// --- Fakes ---

class FakeXMLHttpRequest {
    constructor() {
        this.headers = {};
        this.listeners = {};
        this.upload = new FakeEventTarget();
        this.status = 0;
        this.responseText = "";
        requests.push(this);
    }

    open(method, url) {
        this.method = method;
        this.url = url;
    }

    setRequestHeader(name, value) {
        this.headers[name] = value;
    }

    getResponseHeader(name) {
        return this.responseHeaders?.[name.toLowerCase()] ?? null;
    }

    addEventListener(type, listener) {
        (this.listeners[type] ||= []).push(listener);
    }

    send(body) {
        this.body = body;
    }

    abort() {
        this.aborted = true;
        this.emit("abort", {});
    }

    progress(loaded, total) {
        this.upload.emit("progress", { lengthComputable: true, loaded, total });
    }

    respond(status, body, headers = { "content-type": "application/json" }) {
        this.status = status;
        this.responseHeaders = Object.fromEntries(
            Object.entries(headers).map(([key, value]) => [key.toLowerCase(), value])
        );
        this.responseText = typeof body === "string" ? body : JSON.stringify(body);
        this.emit("load", {});
    }

    fail(status = 0) {
        this.status = status;
        this.responseText = "";
        this.emit("error", {});
    }

    emit(type, event) {
        (this.listeners[type] ?? []).forEach((listener) => listener(event));
    }
}

class FakeEventTarget {
    constructor() {
        this.listeners = {};
    }

    addEventListener(type, listener) {
        (this.listeners[type] ||= []).push(listener);
    }

    emit(type, event) {
        (this.listeners[type] ?? []).forEach((listener) => listener(event));
    }
}
