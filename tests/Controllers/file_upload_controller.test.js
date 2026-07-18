import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

const FileUploadController = (await import("../../resources/js/controllers/file_upload_controller.js")).default;

let mounted;
let requests;
let fetchCalls;

beforeEach(() => {
    requests = [];
    fetchCalls = [];
    globalThis.XMLHttpRequest = FakeXMLHttpRequest;
    globalThis.fetch = mock((url, init) => {
        fetchCalls.push({ url, init });
        return Promise.resolve({ ok: true });
    });
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = undefined;
    delete globalThis.Turbo;
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
                <div data-file-upload-target="list"></div>
                <template data-file-upload-target="template">
                    <div data-slot="attachment" data-state="idle" data-file-upload-attachment>
                        <span data-file-upload-name></span>
                        <span data-file-upload-description></span>
                        <div data-file-upload-progress hidden>
                            <div data-slot="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-value="0" data-max="100" style="--progress-value: 0%">
                                <div data-slot="progress-track"><div data-slot="progress-indicator"></div></div>
                            </div>
                        </div>
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
