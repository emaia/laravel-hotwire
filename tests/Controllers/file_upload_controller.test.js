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
        if (!(blob instanceof Blob)) throw new TypeError("Object URL source must be a Blob");

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

function defaultHtml(extraAttrs = "", extraChildren = "", controllers = "file-upload", customDropzone = false) {
    const dropzoneContent = customDropzone
        ? '<span data-custom-uploader>Custom uploader</span>'
        : '<span data-file-upload-target="feedback" data-file-upload-default-feedback="Drop files here">Drop files here</span>';
    const customFeedback = customDropzone
        ? '<p data-slot="file-upload-feedback" data-file-upload-target="feedback" data-file-upload-default-feedback="" hidden></p>'
        : '';

    return `
        <form id="parent-form">
            <div data-controller="${controllers}"
                  data-file-upload-url-value="/upload"
                  data-file-upload-hidden-name-value="avatar"
                  ${extraAttrs}>
                <input type="file" hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-file-upload-target="dropzone">
                    ${dropzoneContent}
                </div>
                ${customFeedback}
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

function customHtml(extraAttrs = "") {
    return defaultHtml(extraAttrs, "", "file-upload", true);
}

function imageHtml(extraAttrs = "", extraChildren = "") {
    return `
        <form id="parent-form">
            <div data-controller="file-upload"
                  data-file-upload-url-value="/upload"
                  data-file-upload-hidden-name-value="avatar"
                  data-file-upload-accept-value="image/*"
                  data-file-upload-view-value="image"
                  ${extraAttrs}>
                <input type="file" hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-file-upload-target="dropzone">
                    <div data-slot="file-upload-image-base">Confirmed image</div>
                    <img data-slot="file-upload-image-preview" data-file-upload-target="imagePreview" alt="" hidden>
                </div>
                <p data-slot="file-upload-feedback" data-file-upload-target="feedback" data-file-upload-default-feedback="" hidden></p>
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

test("image view previews locally without rendering attachment cards", async () => {
    await mount(imageHtml('data-file-upload-preview-url-key-value="cdn_url"'));
    const preview = mounted.root.querySelector('[data-slot="file-upload-image-preview"]');

    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });

    expect(requests).toHaveLength(1);
    expect(mounted.root.querySelector('[data-slot="attachment"]')).toBeNull();
    expect(preview.hidden).toBe(false);
    expect(preview.getAttribute("src")).toBe("blob:avatar.png-0");
    expect(mounted.root.dataset.uploadState).toBe("uploading");

    requests[0].respond(201, { token: "avatar-token", cdn_url: "/uploads/avatar.png" });
    await wait(0);

    const item = mounted.controller.items[0];
    expect(preview.getAttribute("src")).toBe("blob:avatar.png-0");
    item.imageLoader.dispatchEvent(new Event("load"));

    expect(preview.getAttribute("src")).toBe("/uploads/avatar.png");
    expect(preview.dataset.fileUploadId).toBe(item.id);
    expect(revokedUrls).toEqual(["blob:avatar.png-0"]);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("avatar-token");
});

test("image view keeps the local preview when the durable URL does not load", async () => {
    await mount(imageHtml());
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "avatar-token", preview_url: "/uploads/missing.png" });
    await wait(0);

    const item = mounted.controller.items[0];
    item.imageLoader.dispatchEvent(new Event("error"));

    expect(mounted.root.querySelector('[data-slot="file-upload-image-preview"]').getAttribute("src"))
        .toBe("blob:avatar.png-0");
    expect(item.imageLoader).toBeNull();
    expect(revokedUrls).toEqual([]);
});

test("image view rolls a failed candidate back to the confirmed preview", async () => {
    await mount(imageHtml());
    mounted.controller.select({
        target: { files: [file("saved.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "saved-token", preview_url: "/uploads/saved.png" });
    await wait(0);
    mounted.controller.items[0].imageLoader.dispatchEvent(new Event("load"));

    mounted.controller.select({
        target: { files: [file("candidate.png", { type: "image/png" })], value: "x" },
    });
    const preview = mounted.root.querySelector('[data-slot="file-upload-image-preview"]');
    expect(preview.getAttribute("src")).toBe("blob:candidate.png-1");

    requests[1].respond(422, { errors: { file: ["The candidate is invalid."] } });
    await wait(0);

    expect(preview.getAttribute("src")).toBe("/uploads/saved.png");
    expect(revokedUrls).toEqual(["blob:saved.png-0", "blob:candidate.png-1"]);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("saved-token");
    expect(mounted.root.dataset.uploadState).toBe("error");
});

test("image view rejects non-image files before upload", async () => {
    await mount(imageHtml());

    mounted.controller.select({
        target: { files: [file("document.pdf", { type: "application/pdf" })], value: "x" },
    });

    expect(requests).toHaveLength(0);
    expect(objectUrls).toHaveLength(0);
    expect(mounted.root.dataset.uploadState).toBe("error");
    expect(mounted.root.querySelector('[data-slot="file-upload-feedback"]').textContent)
        .toBe("File type is not allowed");
});

test("image view does not trust an image extension over a contradictory MIME", async () => {
    await mount(imageHtml());

    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "text/plain" })], value: "x" },
    });

    expect(requests).toHaveLength(0);
    expect(objectUrls).toHaveLength(0);
    expect(mounted.root.dataset.uploadState).toBe("error");
});

test("image view recognizes image extensions when MIME is unavailable", async () => {
    await mount(imageHtml());

    mounted.controller.select({
        target: { files: [file("avatar.svg", { type: "" })], value: "x" },
    });

    expect(requests).toHaveLength(1);
    expect(mounted.root.querySelector('[data-slot="file-upload-image-preview"]').getAttribute("src"))
        .toBe("blob:avatar.svg-0");
});

test("preview false leaves image rendering to the server", async () => {
    await mount(imageHtml('data-file-upload-preview-value="false"'));
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });

    expect(objectUrls).toHaveLength(0);
    expect(mounted.root.querySelector('[data-slot="file-upload-image-preview"]').hidden).toBe(true);

    requests[0].respond(201, { token: "avatar-token", preview_url: "/uploads/avatar.png" });
    await wait(0);

    expect(mounted.controller.items[0].imageLoader).toBeNull();
    expect(mounted.root.querySelector('[data-slot="file-upload-image-preview"]').hidden).toBe(true);
});

test("reconnect hydrates a durable image preview without an attachment list", async () => {
    await mount(imageHtml());
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "avatar-token", preview_url: "/uploads/avatar.png" });
    await wait(0);
    mounted.controller.items[0].imageLoader.dispatchEvent(new Event("load"));

    mounted.controller.disconnect();
    mounted.controller.connect();

    const preview = mounted.root.querySelector('[data-slot="file-upload-image-preview"]');
    expect(mounted.controller.items).toHaveLength(1);
    expect(mounted.controller.items[0].state).toBe("done");
    expect(preview.getAttribute("src")).toBe("/uploads/avatar.png");
    expect(preview.hidden).toBe(false);
    expect(mounted.root.dataset.uploadState).toBe("done");
});

test("Turbo morph restores a durable image preview and its hidden token", async () => {
    await mount(imageHtml());
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "avatar-token", preview_url: "/uploads/avatar.png" });
    await wait(0);
    mounted.controller.items[0].imageLoader.dispatchEvent(new Event("load"));

    const oldPreview = mounted.root.querySelector('[data-slot="file-upload-image-preview"]');
    oldPreview.remove();
    mounted.root.querySelector('input[type="hidden"][name="avatar"]').remove();
    mounted.root.insertAdjacentHTML(
        "beforeend",
        '<input type="hidden" name="avatar" value="stale-token" data-hw-upload-preserved>'
    );
    mounted.root.querySelector('[data-file-upload-target="dropzone"]').insertAdjacentHTML(
        "beforeend",
        '<img data-slot="file-upload-image-preview" data-file-upload-target="imagePreview" alt="" hidden>'
    );
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    const preview = mounted.root.querySelector('[data-slot="file-upload-image-preview"]');
    expect(preview.getAttribute("src")).toBe("/uploads/avatar.png");
    expect(preview.hidden).toBe(false);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("avatar-token");
    expect(mounted.root.querySelectorAll('input[type="hidden"][name="avatar"]')).toHaveLength(1);
    expect(mounted.root.querySelector('[data-hw-upload-preserved]')).toBeNull();
    expect(mounted.controller.items[0].hidden?.isConnected).toBe(true);
    expect(mounted.root.dataset.uploadState).toBe("done");
});

test("preview-enabled uploads write to any external feedback target", async () => {
    await mount(imageHtml().replace('data-slot="file-upload-feedback" ', ""));
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });

    const feedback = mounted.root.querySelector('[data-file-upload-target="feedback"]');
    expect(feedback.hidden).toBe(false);
    expect(feedback.textContent).toBe("Uploading avatar.png");
});

test("Turbo morph restores attachment cards and their actions", async () => {
    await mount();
    mounted.controller.select({
        target: { files: [file("photo.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "photo-token" });
    await wait(0);

    mounted.root.querySelector('[data-file-upload-attachment]').remove();
    mounted.root.querySelector('input[type="hidden"][name="avatar"]').remove();
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    const attachment = mounted.root.querySelector('[data-file-upload-attachment]');
    expect(attachment).not.toBeNull();
    expect(attachment.dataset.state).toBe("done");
    expect(attachment.querySelector('[data-file-upload-remove]')).not.toBeNull();
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("photo-token");
    expect(revokedUrls).toEqual(["blob:photo.png-0"]);
    expect(objectUrls).toHaveLength(2);
});

test("Turbo morph restores hydrated image cards without creating a URL from synthetic files", async () => {
    await mount();
    mounted.controller.select({
        target: { files: [file("photo.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "photo-token" });
    await wait(0);
    mounted.controller.disconnect();
    mounted.controller.connect();

    mounted.root.querySelector('[data-file-upload-attachment]').remove();
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    const attachment = mounted.root.querySelector('[data-file-upload-attachment]');
    expect(attachment).not.toBeNull();
    expect(attachment.querySelector('[data-slot="attachment-media"] img')).toBeNull();
    expect(objectUrls).toHaveLength(1);
    expect(revokedUrls).toEqual(["blob:photo.png-0"]);
});

test("Turbo morph preserves initial values when hidden emission is disabled", async () => {
    await mount(defaultHtml(
        'data-file-upload-emit-hidden-value="false"',
        '<input type="hidden" name="avatar" value="preserved-token" data-hw-upload-preserved>'
    ));
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "unused-token" });
    await wait(0);

    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    expect(mounted.root.querySelector('[data-hw-upload-preserved]')?.value).toBe("preserved-token");
    expect(mounted.root.querySelectorAll('input[type="hidden"][name="avatar"]')).toHaveLength(1);
});

test("Turbo morph restores custom error feedback and dropzone ARIA", async () => {
    await mount(customHtml('data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false"'));
    mounted.controller.select({ target: { files: [file("avatar.png")], value: "x" } });
    requests[0].respond(422, { errors: { file: ["The avatar is invalid."] } });
    await wait(0);

    const previousFeedback = mounted.root.querySelector('[data-file-upload-target="feedback"]');
    previousFeedback.replaceWith(previousFeedback.cloneNode(false));
    const dropzone = mounted.root.querySelector('[data-file-upload-target="dropzone"]');
    dropzone.removeAttribute("aria-invalid");
    delete dropzone.dataset.state;
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    const feedback = mounted.root.querySelector('[data-file-upload-target="feedback"]');
    expect(feedback.hidden).toBe(false);
    expect(feedback.textContent).toBe("The avatar is invalid.");
    expect(dropzone.getAttribute("aria-invalid")).toBe("true");
    expect(dropzone.dataset.state).toBe("error");
});

test("Turbo cache revokes transient image previews and restores the confirmed base", async () => {
    await mount(imageHtml());
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "avatar-token" });
    await wait(0);

    const preview = mounted.root.querySelector('[data-slot="file-upload-image-preview"]');
    expect(preview.getAttribute("src")).toBe("blob:avatar.png-0");

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(preview.hidden).toBe(true);
    expect(preview.hasAttribute("src")).toBe(false);
    expect(revokedUrls).toEqual(["blob:avatar.png-0"]);
});

test("custom dropzones expose loading and upload state through the root", async () => {
    await mount(customHtml('data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false"'));
    const feedback = mounted.root.querySelector('[data-slot="file-upload-feedback"]');

    expect(mounted.root.dataset.loading).toBe("false");
    expect(mounted.root.dataset.uploadState).toBe("idle");
    expect(feedback.hidden).toBe(true);

    mounted.controller.select({ target: { files: [file("avatar.png", { type: "image/png" })], value: "x" } });

    expect(mounted.root.dataset.loading).toBe("true");
    expect(mounted.root.dataset.uploadState).toBe("uploading");
    expect(feedback.hidden).toBe(false);
    expect(feedback.textContent).toBe("Uploading avatar.png");

    requests[0].respond(201, { token: "avatar" });
    await wait(0);

    expect(mounted.root.dataset.loading).toBe("false");
    expect(mounted.root.dataset.uploadState).toBe("done");
    expect(feedback.textContent).toBe("Uploaded avatar.png");
});

test("custom dropzone feedback resets after errors are cleared", async () => {
    await mount(customHtml('data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false"'));
    const feedback = mounted.root.querySelector('[data-slot="file-upload-feedback"]');
    mounted.controller.select({ target: { files: [file("avatar.png")], value: "x" } });
    requests[0].respond(422, { message: "Invalid", errors: { file: ["The avatar is invalid."] } });
    await wait(0);

    expect(mounted.root.dataset.loading).toBe("false");
    expect(mounted.root.dataset.uploadState).toBe("error");
    expect(feedback.hidden).toBe(false);
    expect(feedback.textContent).toBe("The avatar is invalid.");

    mounted.controller.clear({ preventDefault() {} });

    expect(mounted.root.dataset.uploadState).toBe("idle");
    expect(feedback.hidden).toBe(true);
    expect(feedback.textContent).toBe("");
});

test("Turbo cache resets custom dropzone loading and feedback", async () => {
    await mount(customHtml('data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false"'));
    const feedback = mounted.root.querySelector('[data-slot="file-upload-feedback"]');
    mounted.controller.select({ target: { files: [file("avatar.png")], value: "x" } });

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(mounted.root.dataset.loading).toBe("false");
    expect(mounted.root.dataset.uploadState).toBe("idle");
    expect(feedback.hidden).toBe(true);
});

test("custom feedback follows the remaining completed item after removing an active upload", async () => {
    await mount(customHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="1"'
    ));
    mounted.controller.select({ target: { files: [file("saved.png"), file("active.png")], value: "x" } });
    requests[0].respond(201, { token: "saved" });
    await wait(0);

    const active = mounted.controller.items.find((item) => item.file.name === "active.png");
    mounted.controller.remove({ preventDefault() {}, params: { id: active.id } });

    const feedback = mounted.root.querySelector('[data-slot="file-upload-feedback"]');
    const dropzone = mounted.root.querySelector('[data-file-upload-target="dropzone"]');
    expect(mounted.root.dataset.loading).toBe("false");
    expect(mounted.root.dataset.uploadState).toBe("done");
    expect(feedback.textContent).toBe("Uploaded saved.png");
    expect(dropzone.dataset.state).toBe("done");
    expect(dropzone.hasAttribute("aria-busy")).toBe(false);
});

test("custom feedback follows a remaining error after removing another failed item", async () => {
    await mount(customHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true"'
    ));
    mounted.controller.select({
        target: { files: [file("first.png"), file("second.png")], value: "x" },
    });
    requests[0].respond(422, { errors: { file: ["The first file is invalid."] } });
    requests[1].respond(422, { errors: { file: ["The second file is invalid."] } });
    await wait(0);

    const second = mounted.controller.items.find((item) => item.file.name === "second.png");
    mounted.controller.remove({ preventDefault() {}, params: { id: second.id } });

    expect(mounted.root.dataset.uploadState).toBe("error");
    expect(mounted.root.querySelector('[data-slot="file-upload-feedback"]').textContent)
        .toBe("The first file is invalid.");
});

test("Turbo page and frame morphs refresh server-rendered validation state", async () => {
    await mount(customHtml());

    mounted.root.setAttribute("data-invalid", "");
    document.dispatchEvent(new CustomEvent("turbo:morph"));
    expect(mounted.root.dataset.uploadState).toBe("error");

    mounted.root.removeAttribute("data-invalid");
    document.dispatchEvent(new CustomEvent("turbo:morph"));
    expect(mounted.root.dataset.uploadState).toBe("idle");

    mounted.root.setAttribute("data-invalid", "");
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));
    expect(mounted.root.dataset.uploadState).toBe("error");
});

test("removing the last active upload recalculates busy state while preserving delete errors", async () => {
    const response = { ok: false, status: 500 };
    globalThis.fetch = mock(() => Promise.resolve(response));
    await mount(customHtml(
        'data-file-upload-preview-value="false" data-file-upload-multiple-value="true" data-file-upload-delete-url-value="/uploads/:token"'
    ));
    mounted.controller.select({ target: { files: [file("saved.png"), file("active.png")], value: "x" } });
    requests[0].respond(201, { token: "saved" });
    await wait(0);

    const saved = mounted.controller.items.find((item) => item.file.name === "saved.png");
    const active = mounted.controller.items.find((item) => item.file.name === "active.png");
    mounted.controller.remove({ preventDefault() {}, params: { id: saved.id } });
    await wait(0);

    const dropzone = mounted.root.querySelector('[data-file-upload-target="dropzone"]');
    expect(dropzone.hasAttribute("aria-busy")).toBe(true);
    mounted.controller.remove({ preventDefault() {}, params: { id: active.id } });

    expect(mounted.root.dataset.loading).toBe("false");
    expect(mounted.root.dataset.uploadState).toBe("error");
    expect(dropzone.hasAttribute("aria-busy")).toBe(false);
    expect(mounted.root.querySelector('[data-slot="file-upload-feedback"]').textContent)
        .toBe("Failed to remove file: saved.png");
});

test("uploads negotiate Laravel JSON responses", async () => {
    await mount();

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });

    expect(requests[0].headers.Accept).toBe("application/json");
    expect(requests[0].headers["X-Requested-With"]).toBe("XMLHttpRequest");
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

test("preview-disabled uploads over 2 MB still send and show server size errors", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-max-size-bytes-value="10485760"'
    ));

    mounted.controller.select({
        target: { files: [file("large.png", { type: "image/png", size: 3 * 1024 * 1024 })], value: "x" },
    });

    expect(requests).toHaveLength(1);
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("Uploading large.png");

    requests[0].respond(
        413,
        "<html><body>Request Entity Too Large</body></html>",
        { "content-type": "text/html" }
    );
    await wait(0);

    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.getAttribute("aria-invalid"))
        .toBe("true");
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("File is too large");
});

test("preview-disabled uploads expose client size errors without sending a request", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-max-size-bytes-value="10485760"'
    ));

    mounted.controller.select({
        target: { files: [file("too-large.png", { type: "image/png", size: 11 * 1024 * 1024 })], value: "x" },
    });

    expect(requests).toHaveLength(0);
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.getAttribute("aria-invalid"))
        .toBe("true");
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("File is too large");
});

test("preview-disabled mixed selections keep validation errors visible while valid files upload", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true" data-file-upload-max-size-bytes-value="3"'
    ));

    mounted.controller.select({
        target: { files: [file("too-large.png", { size: 4 }), file("valid.png", { size: 3 })], value: "x" },
    });

    expect(requests).toHaveLength(1);
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("File is too large");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(true);

    requests[0].respond(201, { token: "valid" });
    await wait(0);

    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.getAttribute("aria-invalid"))
        .toBe("true");
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("File is too large");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(false);
    expect(mounted.root.querySelector("[data-file-upload-target='announcer']")?.textContent)
        .toBe("Uploaded valid.png");
});

test("later selections keep previous multiple-upload errors visible", async () => {
    await mount(customHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true" data-file-upload-max-size-bytes-value="3"'
    ));
    mounted.controller.select({
        target: { files: [file("too-large.png", { size: 4 })], value: "x" },
    });
    mounted.controller.select({
        target: { files: [file("valid.png", { size: 3 })], value: "x" },
    });
    requests[0].respond(201, { token: "valid" });
    await wait(0);

    const dropzone = mounted.root.querySelector('[data-file-upload-target="dropzone"]');
    expect(mounted.root.dataset.uploadState).toBe("error");
    expect(dropzone.getAttribute("aria-invalid")).toBe("true");
    expect(mounted.root.querySelector('[data-file-upload-target="feedback"]').textContent)
        .toBe("File is too large");
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

test("single mode keeps the completed upload when a replacement is invalid", async () => {
    await mount(defaultHtml('data-file-upload-accept-value="image/*"'));
    mounted.controller.select({ target: { files: [file("old.png", { type: "image/png" })], value: "x" } });
    requests[0].respond(201, { token: "old-token" });
    await wait(0);

    mounted.controller.select({ target: { files: [file("notes.txt")], value: "x" } });

    expect(mounted.root.querySelector('input[type="hidden"]')?.value).toBe("old-token");
    expect([...mounted.root.querySelectorAll('[data-slot="attachment"]')].map((item) => item.dataset.state))
        .toEqual(["done", "error"]);
});

test("single mode keeps the completed upload when a replacement fails", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("old.png")], value: "x" } });
    requests[0].respond(201, { token: "old-token" });
    await wait(0);

    mounted.controller.select({ target: { files: [file("new.png")], value: "x" } });
    requests[1].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')?.value).toBe("old-token");
    expect([...mounted.root.querySelectorAll('[data-slot="attachment"]')].map((item) => item.dataset.state))
        .toEqual(["done", "error"]);
});

test("single mode removes the previous upload only after its replacement succeeds", async () => {
    await mount(defaultHtml('data-file-upload-delete-url-value="/uploads/:token"'));
    mounted.controller.select({ target: { files: [file("old.png")], value: "x" } });
    requests[0].respond(201, { token: "old-token" });
    await wait(0);

    mounted.controller.select({ target: { files: [file("new.png")], value: "x" } });

    expect(mounted.root.querySelector('input[type="hidden"]')?.value).toBe("old-token");
    expect(mounted.root.querySelectorAll('[data-slot="attachment"]')).toHaveLength(2);
    expect(fetchCalls).toHaveLength(0);

    requests[1].respond(201, { token: "new-token" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')?.value).toBe("new-token");
    expect(mounted.root.querySelectorAll('[data-slot="attachment"]')).toHaveLength(1);
    expect(mounted.root.querySelector("[data-file-upload-name]")?.textContent).toBe("new.png");
    expect(fetchCalls).toEqual([{ url: "/uploads/old-token", init: { method: "DELETE", headers: {} } }]);
});

test("single mode lets the same file replace its completed upload", async () => {
    await mount();
    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].respond(201, { token: "old-token" });
    await wait(0);

    mounted.controller.select({ target: { files: [upload], value: "x" } });

    expect(requests).toHaveLength(2);
    expect(mounted.root.querySelectorAll('[data-slot="attachment"]')).toHaveLength(2);
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

test("failed list image previews restore their icon and retry with a fresh object URL", async () => {
    await mount();
    mounted.controller.select({
        target: { files: [file("photo.png", { type: "image/png" })], value: "x" },
    });

    const attachment = mounted.root.querySelector('[data-slot="attachment"]');
    const media = attachment.querySelector('[data-slot="attachment-media"]');
    expect(media.querySelector("img")?.getAttribute("src")).toBe("blob:photo.png-0");

    requests[0].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    expect(media.dataset.variant).toBe("icon");
    expect(media.querySelector("img")).toBeNull();
    expect(media.querySelector("svg")).not.toBeNull();
    expect(revokedUrls).toEqual(["blob:photo.png-0"]);

    mounted.controller.retry({
        preventDefault() {},
        params: { id: attachment.dataset.fileUploadId },
    });

    expect(media.dataset.variant).toBe("image");
    expect(media.querySelector("img")?.getAttribute("src")).toBe("blob:photo.png-1");
});

test("retry respects max-files and remains available after capacity is freed", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-max-files-value="1"'));
    mounted.controller.select({ target: { files: [file("failed.png")], value: "x" } });
    requests[0].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    const failed = mounted.root.querySelector('[data-file-upload-id]');
    const failedId = failed.dataset.fileUploadId;
    mounted.controller.select({ target: { files: [file("active.png")], value: "x" } });

    mounted.controller.retry({ preventDefault() {}, params: { id: failedId } });

    expect(requests).toHaveLength(2);
    expect(failed.querySelector("[data-file-upload-description]")?.textContent).toBe("Maximum number of files reached");
    expect(failed.querySelector("[data-file-upload-retry]")?.hidden).toBe(false);

    const activeId = [...mounted.root.querySelectorAll('[data-file-upload-id]')][1].dataset.fileUploadId;
    mounted.controller.remove({ preventDefault() {}, params: { id: activeId } });
    mounted.controller.retry({ preventDefault() {}, params: { id: failedId } });

    expect(requests).toHaveLength(3);
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

test("malformed JSON responses become upload errors without hidden input values", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, "{not valid json", { "content-type": "application/json" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("error");
});

test("single mode keeps the completed upload when a replacement response has no token", async () => {
    await mount(defaultHtml('data-file-upload-delete-url-value="/uploads/:token"'));
    mounted.controller.select({ target: { files: [file("old.png")], value: "x" } });
    requests[0].respond(201, { token: "old-token" });
    await wait(0);

    mounted.controller.select({ target: { files: [file("new.png")], value: "x" } });
    requests[1].respond(201, {}, { "content-type": "application/json" });
    await wait(0);

    expect(mounted.root.querySelector('input[type="hidden"]')?.value).toBe("old-token");
    expect([...mounted.root.querySelectorAll('[data-slot="attachment"]')].map((item) => item.dataset.state))
        .toEqual(["done", "error"]);
    expect(fetchCalls).toHaveLength(0);
});

test("JSON success renders an embedded stream after committing client state", async () => {
    const rendered = [];
    globalThis.Turbo = {
        renderStreamMessage: (html) => rendered.push({
            html,
            state: mounted.root.dataset.uploadState,
            token: mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value,
        }),
    };
    await mount();

    const stream = '<turbo-stream action="append" target="flash-container"><template>Saved</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "photo-token", stream });
    await wait(0);

    expect(rendered).toEqual([{ html: stream, state: "done", token: "photo-token" }]);
    expect(mounted.root.querySelector('[data-slot="attachment"]')?.dataset.state).toBe("done");
});

test("JSON errors render an embedded stream after normalizing error state", async () => {
    const rendered = [];
    globalThis.Turbo = {
        renderStreamMessage: (html) => rendered.push({
            html,
            state: mounted.root.dataset.uploadState,
            text: mounted.root.querySelector('[data-file-upload-description]')?.textContent,
        }),
    };
    await mount();

    const stream = '<turbo-stream action="append" target="flash-container"><template>Failed</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(422, {
        errors: { file: ["The file is invalid."] },
        stream,
    });
    await wait(0);

    expect(rendered).toEqual([{ html: stream, state: "error", text: "The file is invalid." }]);
});

test("JSON stream works with image view without Turbo negotiation", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(imageHtml());

    const stream = '<turbo-stream action="append" target="flash-container"><template>Image saved</template></turbo-stream>';
    mounted.controller.select({
        target: { files: [file("avatar.png", { type: "image/png" })], value: "x" },
    });
    requests[0].respond(201, { token: "avatar-token", stream });
    await wait(0);

    expect(rendered).toEqual([stream]);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("avatar-token");
    expect(mounted.root.querySelector('[data-slot="file-upload-image-preview"]').getAttribute("src"))
        .toBe("blob:avatar.png-0");
});

test("JSON responses ignore embedded HTML that is not a Turbo Stream", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount();

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "photo-token", stream: "<div>Not a stream</div>" });
    await wait(0);

    expect(rendered).toEqual([]);
    expect(mounted.root.querySelector('input[type="hidden"][name="avatar"]')?.value).toBe("photo-token");
});

test("JSON streams strip surrounding non-stream markup before rendering", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount();

    const stream = '<turbo-stream action="append" target="flash-container"><template>Saved</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, {
        token: "photo-token",
        stream: `<div id="unexpected">Ignore me</div>${stream}`,
    });
    await wait(0);

    expect(rendered).toEqual([stream]);
});

test("a response stream that removes the uploader does not start the next queued upload", async () => {
    globalThis.Turbo = { renderStreamMessage: () => mounted.root.remove() };
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="1"'
    ));

    const stream = '<turbo-stream action="remove" target="parent-form"></turbo-stream>';
    mounted.controller.select({ target: { files: [file("first.png"), file("second.png")], value: "x" } });
    requests[0].respond(201, { token: "first-token", stream });
    await wait(0);

    expect(requests).toHaveLength(1);
});

test("a pending response stream gates queue advancement from concurrent uploads", async () => {
    let finishRendering;
    globalThis.Turbo = {
        renderStreamMessage: () => new Promise((resolve) => {
            finishRendering = () => {
                mounted.root.remove();
                resolve();
            };
        }),
    };
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="2"'
    ));

    const stream = '<turbo-stream action="remove" target="parent-form"></turbo-stream>';
    mounted.controller.select({
        target: { files: [file("first.png"), file("second.png"), file("third.png")], value: "x" },
    });
    requests[0].respond(201, { token: "first-token", stream });
    requests[1].respond(201, { token: "second-token" });
    await wait(0);

    expect(requests).toHaveLength(2);

    finishRendering();
    await wait(0);
    expect(requests).toHaveLength(2);
});

test("all concurrent response streams settle before the queue advances", async () => {
    const finishRendering = [];
    globalThis.Turbo = {
        renderStreamMessage: () => new Promise((resolve) => finishRendering.push(resolve)),
    };
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="2"'
    ));

    const stream = '<turbo-stream action="append" target="results"><template>ok</template></turbo-stream>';
    mounted.controller.select({
        target: { files: [file("first.png"), file("second.png"), file("third.png")], value: "x" },
    });
    requests[0].respond(201, { token: "first-token", stream });
    requests[1].respond(201, { token: "second-token", stream });
    await wait(0);

    finishRendering[0]();
    await wait(0);
    expect(requests).toHaveLength(2);

    finishRendering[1]();
    await wait(0);
    expect(requests).toHaveLength(3);
});

test("a stream completion from before Turbo cache cannot release the new queue", async () => {
    const finishRendering = [];
    globalThis.Turbo = {
        renderStreamMessage: () => new Promise((resolve) => finishRendering.push(resolve)),
    };
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="1"'
    ));

    const stream = '<turbo-stream action="append" target="results"><template>ok</template></turbo-stream>';
    mounted.controller.select({
        target: { files: [file("old-active.png"), file("old-queued.png")], value: "x" },
    });
    requests[0].respond(201, { token: "old-token", stream });
    await wait(0);

    mounted.controller.prepareForCache();
    mounted.controller.select({
        target: { files: [file("new-active.png"), file("new-queued.png")], value: "x" },
    });
    requests[1].respond(201, { token: "new-token", stream });
    await wait(0);

    finishRendering[0]();
    await wait(0);
    expect(requests).toHaveLength(2);

    finishRendering[1]();
    await wait(0);
    expect(requests).toHaveLength(3);
});

test("turbo-stream=true negotiates stream responses and renders stream success without hidden input", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));

    const stream = '<turbo-stream action="append" target="files"><template>ok</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    expect(requests[0].headers.Accept).toBe("application/json, text/vnd.turbo-stream.html");
    requests[0].respond(200, stream, { "content-type": "text/vnd.turbo-stream.html" });
    await wait(0);

    expect(rendered).toEqual([stream]);
    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("turbo-stream error responses are rendered too", async () => {
    const rendered = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));

    const stream = '<turbo-stream action="replace" target="upload-error"><template>no</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(422, stream, { "content-type": "text/vnd.turbo-stream.html" });
    await wait(0);

    expect(rendered).toEqual([stream]);
});

test("raw Turbo Stream mode rejects successful responses without a stream", async () => {
    const errors = [];
    const successes = [];
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));
    mounted.root.addEventListener("file-upload:success", (event) => successes.push(event.detail));

    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(204, "", { "content-type": "text/plain" });
    await wait(0);

    expect(successes).toEqual([]);
    expect(errors).toEqual(["Upload failed"]);
    expect(mounted.root.dataset.uploadState).toBe("error");
});

test("raw Turbo Stream mode rejects JSON envelopes even when they contain a stream", async () => {
    const rendered = [];
    const errors = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    const stream = '<turbo-stream action="append" target="files"><template>ok</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(200, { stream });
    await wait(0);

    expect(rendered).toEqual([]);
    expect(errors).toEqual(["Upload failed"]);
    expect(mounted.root.dataset.uploadState).toBe("error");
});

test("raw Turbo Stream mode rejects a JSON scalar containing stream markup", async () => {
    const rendered = [];
    const errors = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    const stream = '<turbo-stream action="append" target="files"><template>ok</template></turbo-stream>';
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(200, JSON.stringify(stream), { "content-type": "application/json" });
    await wait(0);

    expect(rendered).toEqual([]);
    expect(errors).toEqual(["Upload failed"]);
    expect(mounted.root.dataset.uploadState).toBe("error");
});

test("turbo-stream=true requires an actual stream when used on a controller directly", async () => {
    const rendered = [];
    const errors = [];
    globalThis.Turbo = { renderStreamMessage: (html) => rendered.push(html) };
    await mount(defaultHtml('data-file-upload-turbo-stream-value="true"'));
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));

    const upload = file("photo.png");
    const text = "stored <turbo-streamish>not a stream</turbo-streamish>";
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].respond(200, text, { "content-type": "text/html" });
    await wait(0);

    expect(rendered).toEqual([]);
    expect(errors).toEqual(["The server rejected this file. Check the file type and server upload-size limit."]);
    expect(mounted.root.querySelector('input[type="hidden"]')).toBeNull();
});

test("preview-disabled Turbo uploads treat redirected HTML documents as visible errors", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));
    const errors = [];
    const successes = [];
    mounted.root.addEventListener("file-upload:error", (event) => errors.push(event.detail.text));
    mounted.root.addEventListener("file-upload:success", (event) => successes.push(event.detail));
    mounted.controller.select({ target: { files: [file("large.png", { type: "image/png" })], value: "x" } });

    requests[0].respond(
        200,
        '<!DOCTYPE html><html><body><div data-toast-message-value="The file failed to upload."></div></body></html>',
        { "content-type": "text/html; charset=UTF-8" }
    );
    await wait(0);

    expect(errors).toEqual(["The server rejected this file. Check the file type and server upload-size limit."]);
    expect(successes).toEqual([]);
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.getAttribute("aria-invalid"))
        .toBe("true");
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("The server rejected this file. Check the file type and server upload-size limit.");
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

test("non-successful remote deletes dispatch a cleanup error", async () => {
    const response = { ok: false, status: 500 };
    globalThis.fetch = mock((url, init) => {
        fetchCalls.push({ url, init });
        return Promise.resolve(response);
    });
    await mount(defaultHtml('data-file-upload-delete-url-value="/uploads/:token"'));
    const upload = file("photo.png");
    const errors = [];
    mounted.root.addEventListener("file-upload:delete-error", (event) => errors.push(event.detail));
    mounted.controller.select({ target: { files: [upload], value: "x" } });
    requests[0].respond(201, { token: "abc" });
    await wait(0);

    const id = mounted.root.querySelector('[data-file-upload-id]').dataset.fileUploadId;
    mounted.controller.remove({ preventDefault() {}, params: { id } });
    await wait(0);

    expect(errors).toEqual([{
        error: expect.any(Error),
        file: upload,
        response,
        text: "Failed to remove file",
        value: "abc",
    }]);
    expect(mounted.root.querySelector("[data-file-upload-target='announcer']")?.textContent)
        .toBe("Failed to remove file: photo.png");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.getAttribute("aria-invalid"))
        .toBe("true");
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("Failed to remove file: photo.png");
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

test("clear resets preview-disabled upload feedback", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true"'
    ));
    mounted.controller.select({ target: { files: [file("active.png")], value: "x" } });

    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(true);

    mounted.controller.clear({ preventDefault() {} });

    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("Drop files here");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(false);
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-invalid"))
        .toBe(false);
});

test("removing the final failed item allows pending upload feedback to resume", async () => {
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="2"'
    ));
    mounted.controller.select({ target: { files: [file("failed.png"), file("active.png")], value: "x" } });
    requests[0].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    const failed = mounted.controller.items.find((item) => item.state === "error");
    mounted.controller.remove({ preventDefault() {}, params: { id: failed.id } });
    requests[1].respond(201, { token: "active-token" });
    await wait(0);

    expect(mounted.root.querySelector("[data-file-upload-target='announcer']")?.textContent)
        .toBe("Uploaded active.png");
});

test("clear deletes preserved remote upload tokens", async () => {
    await mount(defaultHtml(
        'data-file-upload-multiple-value="true" data-file-upload-delete-url-value="/uploads/:token"',
        '<input type="hidden" name="avatar" value="old-a" data-hw-upload-preserved><input type="hidden" name="avatar" value="old-b" data-hw-upload-preserved>'
    ));

    mounted.controller.clear({ preventDefault() {} });
    await wait(0);

    expect(fetchCalls.map((call) => call.url)).toEqual(["/uploads/old-a", "/uploads/old-b"]);
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

test("reconnect hydrates hidden-only uploads when preview is disabled", async () => {
    await mount(defaultHtml('data-file-upload-preview-value="false" data-file-upload-delete-url-value="/uploads/:token"'));
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(201, { token: "abc" });
    await wait(0);

    expect(mounted.root.querySelector('[data-file-upload-attachment]')).toBeNull();
    expect(mounted.root.querySelector('input[data-hw-upload]')?.value).toBe("abc");

    mounted.controller.disconnect();
    mounted.controller.connect();
    mounted.controller.clear({ preventDefault() {} });
    await wait(0);

    expect(mounted.root.querySelector('input[data-hw-upload]')).toBeNull();
    expect(fetchCalls).toEqual([{ url: "/uploads/abc", init: { method: "DELETE", headers: {} } }]);
});

test("reconnect removes interrupted uploads and allows the same file to be selected again", async () => {
    await mount();
    const upload = file("photo.png");
    mounted.controller.select({ target: { files: [upload], value: "x" } });

    mounted.controller.disconnect();
    mounted.controller.connect();

    expect(mounted.root.querySelector('[data-file-upload-attachment]')).toBeNull();

    mounted.controller.select({ target: { files: [upload], value: "x" } });

    expect(requests).toHaveLength(2);
    expect(mounted.root.querySelectorAll('[data-file-upload-attachment]')).toHaveLength(1);
    expect(mounted.root.querySelector('[data-file-upload-attachment]')?.dataset.state).toBe("uploading");
});

test("reconnect removes failed uploads that no longer have a retryable File", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png")], value: "x" } });
    requests[0].respond(500, "Server error", { "content-type": "text/plain" });
    await wait(0);

    mounted.controller.disconnect();
    mounted.controller.connect();

    expect(mounted.root.querySelector('[data-file-upload-attachment]')).toBeNull();
});

test("reconnect restores the generic media after revoking a completed image preview", async () => {
    await mount();
    mounted.controller.select({ target: { files: [file("photo.png", { type: "image/png" })], value: "x" } });
    requests[0].respond(201, { token: "abc" });
    await wait(0);

    mounted.controller.disconnect();
    mounted.controller.connect();

    const media = mounted.root.querySelector('[data-slot="attachment-media"]');
    expect(revokedUrls).toEqual(["blob:photo.png-0"]);
    expect(media?.dataset.variant).toBe("icon");
    expect(media?.querySelector("img")).toBeNull();
    expect(media?.querySelector("svg")).not.toBeNull();
});

test("reconnect revokes blob previews found in hydrated markup", async () => {
    await mount();
    mounted.controller.disconnect();
    mounted.root.querySelector('[data-file-upload-target="list"]').innerHTML = `
        <div data-file-upload-attachment data-file-upload-id="9" data-state="done">
            <div data-slot="attachment-media" data-variant="image"><img src="blob:cached-preview"></div>
            <span data-file-upload-name>photo.png</span>
        </div>
    `;

    mounted.controller.connect();

    const media = mounted.root.querySelector('[data-slot="attachment-media"]');
    expect(revokedUrls).toContain("blob:cached-preview");
    expect(media?.dataset.variant).toBe("icon");
    expect(media?.querySelector("img")).toBeNull();
    expect(media?.querySelector("svg")).not.toBeNull();
});

test("Turbo cache clears stale drag state", async () => {
    await mount();
    mounted.controller.dragEnter({ preventDefault() {} });

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(mounted.root.dataset.dragging).toBe("false");
});

test("parallel-uploads limits concurrent native XHRs", async () => {
    await mount(defaultHtml('data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="2"'));

    mounted.controller.select({ target: { files: [file("a.txt"), file("b.txt"), file("c.txt")], value: "x" } });
    expect(requests).toHaveLength(2);

    requests[0].respond(201, { token: "a" });
    await wait(0);
    expect(requests).toHaveLength(3);
});

test("preview-disabled parallel uploads stay busy until every upload finishes", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="2"'
    ));
    mounted.controller.select({ target: { files: [file("a.txt"), file("b.txt")], value: "x" } });

    requests[0].respond(201, { token: "a" });
    await wait(0);

    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("Uploading b.txt");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(true);
    expect(mounted.root.querySelector("[data-file-upload-target='announcer']")?.textContent)
        .toBe("Uploaded a.txt");

    requests[1].respond(201, { token: "b" });
    await wait(0);

    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("Uploaded b.txt");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(false);
});

test("adding to a saturated preview-disabled queue preserves active feedback", async () => {
    await mount(defaultHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="1"'
    ));
    mounted.controller.select({ target: { files: [file("active.txt")], value: "x" } });

    mounted.controller.select({ target: { files: [file("queued.txt")], value: "x" } });

    expect(requests).toHaveLength(1);
    expect(mounted.root.querySelector("[data-file-upload-target='feedback']")?.textContent)
        .toBe("Uploading active.txt");
    expect(mounted.root.querySelector("[data-file-upload-target='dropzone']")?.hasAttribute("aria-busy"))
        .toBe(true);
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
