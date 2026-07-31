import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

import { createUploadFeedback } from "../../resources/js/controllers/_upload_feedback.js";

let testWindow;
let root;
let dropzone;
let status;
let announcer;
let onChange;
let feedback;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.Node = testWindow.Node;

    document.body.innerHTML = `
        <div id="root">
            <div id="dropzone">
                <span id="status" data-file-upload-default-feedback="Choose files">Choose files</span>
            </div>
            <div id="announcer"></div>
        </div>
    `;
    root = document.getElementById("root");
    dropzone = document.getElementById("dropzone");
    status = document.getElementById("status");
    announcer = document.getElementById("announcer");
    onChange = mock(() => {});
    feedback = createUploadFeedback({
        root: () => root,
        dropzone: () => dropzone,
        status: () => status,
        announcer: () => announcer,
        onChange,
    });
});

afterEach(() => {
    testWindow.close();
});

test("reset restores captured baseline feedback and validation ARIA", () => {
    status.textContent = "Uploaded photo.png";
    status.hidden = true;
    dropzone.dataset.state = "done";
    dropzone.setAttribute("aria-busy", "true");
    dropzone.setAttribute("aria-invalid", "true");

    feedback.reset(snapshot());

    expect(status.textContent).toBe("Choose files");
    expect(status.hidden).toBe(false);
    expect(dropzone.hasAttribute("data-state")).toBe(false);
    expect(dropzone.hasAttribute("aria-busy")).toBe(false);
    expect(dropzone.hasAttribute("aria-invalid")).toBe(false);
});

test("present writes status and keeps busy orthogonal to an error", () => {
    feedback.present(
        { text: "File is too large", state: "error" },
        snapshot({ busy: true }),
    );

    expect(feedback.error).toBe("File is too large");
    expect(status.textContent).toBe("File is too large");
    expect(dropzone.dataset.state).toBe("error");
    expect(dropzone.hasAttribute("aria-busy")).toBe(true);
    expect(dropzone.getAttribute("aria-invalid")).toBe("true");
});

test("sticky errors suppress later non-error presentation until cleared", () => {
    feedback.present({ text: "Delete failed", state: "error" }, snapshot());

    const presented = feedback.present(
        { text: "Uploading next.png", state: "uploading" },
        snapshot({ busy: true }),
    );

    expect(presented).toBe(false);
    expect(status.textContent).toBe("Delete failed");
    expect(dropzone.hasAttribute("aria-busy")).toBe(true);

    feedback.clearError();
    feedback.reconcile(snapshot({ pendingText: "Uploading next.png", busy: true }));
    expect(status.textContent).toBe("Uploading next.png");
});

test("reconcile prioritizes item error, pending, completed and baseline", () => {
    feedback.reconcile(snapshot({ completedText: "Uploaded saved.png" }));
    expect(status.textContent).toBe("Uploaded saved.png");

    feedback.reconcile(snapshot({ pendingText: "Uploading next.png", completedText: "Uploaded saved.png", busy: true }));
    expect(status.textContent).toBe("Uploading next.png");

    feedback.reconcile(snapshot({ itemErrorText: "Invalid file", pendingText: "Uploading next.png", busy: true }));
    expect(status.textContent).toBe("Invalid file");

    feedback.reconcile(snapshot(), { clearError: true });
    expect(status.textContent).toBe("Choose files");
});

test("managed preview keeps internal copy while projecting busy and invalid ARIA", () => {
    feedback.reconcile(snapshot({ preview: true, itemErrorText: "Invalid file", busy: true }));

    expect(status.textContent).toBe("Choose files");
    expect(dropzone.hasAttribute("aria-busy")).toBe(true);
    expect(dropzone.getAttribute("aria-invalid")).toBe("true");
});

test("managed preview reconciliation never replaces internal dropzone copy", () => {
    status.textContent = "Server-morphed copy";

    feedback.reconcile(snapshot({ preview: true, pendingText: "Uploading photo.png", busy: true }));
    expect(status.textContent).toBe("Server-morphed copy");

    feedback.reconcile(snapshot({ preview: true, completedText: "Uploaded photo.png" }));
    expect(status.textContent).toBe("Server-morphed copy");
});

test("managed preview updates busy ARIA while preserving a sticky item error", () => {
    feedback.present(
        { text: "Invalid file", state: "error" },
        snapshot({ preview: true, busy: true }),
    );

    feedback.present(
        { text: "Uploaded valid.png", state: "done" },
        snapshot({ preview: true, busy: false }),
    );

    expect(status.textContent).toBe("Choose files");
    expect(dropzone.hasAttribute("aria-busy")).toBe(false);
    expect(dropzone.getAttribute("aria-invalid")).toBe("true");
});

test("target resolvers write to elements replaced by a Turbo morph", () => {
    const replacement = status.cloneNode(false);
    replacement.id = "replacement";
    status.replaceWith(replacement);
    status = replacement;

    feedback.reconcile(snapshot({ pendingText: "Uploading morphed.png", busy: true }));

    expect(replacement.textContent).toBe("Uploading morphed.png");
});

test("reset uses the latest server-morphed baseline", () => {
    const replacement = status.cloneNode(false);
    replacement.dataset.fileUploadDefaultFeedback = "Updated server copy";
    replacement.textContent = "Updated server copy";
    replacement.hidden = true;
    status.replaceWith(replacement);
    status = replacement;

    feedback.reconcile(snapshot({ preview: true, completedText: "Uploaded photo.png" }));
    feedback.reset(snapshot());

    expect(replacement.textContent).toBe("Updated server copy");
    expect(replacement.hidden).toBe(true);
});

test("announcements are explicit and suspended presenters ignore late writes", () => {
    feedback.reconcile(snapshot({ completedText: "Uploaded photo.png" }));
    expect(announcer.textContent).toBe("");

    feedback.announce("Uploaded photo.png");
    expect(announcer.textContent).toBe("Uploaded photo.png");

    feedback.suspend();
    feedback.present({ text: "Late failure", state: "error" }, snapshot(), { force: true });
    feedback.announce("Late failure");
    expect(status.textContent).toBe("Uploaded photo.png");
    expect(announcer.textContent).toBe("Uploaded photo.png");
});

function snapshot(overrides = {}) {
    return {
        busy: false,
        completedText: null,
        itemErrorText: null,
        pendingText: null,
        preview: false,
        serverInvalid: false,
        ...overrides,
    };
}
