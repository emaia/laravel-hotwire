import { afterEach, beforeEach, describe, expect, test } from "bun:test";
import { Window } from "happy-dom";

import Dropzone from "@deltablot/dropzone";

// Smoke spike — confirms that @deltablot/dropzone 7.x still exposes the event
// names the wrapper depends on. If any of these breaks, the wrapper spec needs
// to adjust before file_upload_controller.js is written.

Dropzone.autoDiscover = false;

let testWindow;
let element;
let dropzone;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.HTMLElement = testWindow.HTMLElement;
    globalThis.HTMLFormElement = testWindow.HTMLFormElement;
    globalThis.FormData = testWindow.FormData;
    globalThis.XMLHttpRequest = testWindow.XMLHttpRequest;
    globalThis.File = testWindow.File;
    globalThis.Blob = testWindow.Blob;
    globalThis.Event = testWindow.Event;
    globalThis.CustomEvent = testWindow.CustomEvent;

    element = document.createElement("div");
    element.classList.add("dropzone");
    document.body.appendChild(element);
    dropzone = new Dropzone(element, { url: "/upload", autoProcessQueue: false });
});

afterEach(() => {
    dropzone?.destroy();
    testWindow.close();
});

describe("@deltablot/dropzone 7.x event surface", () => {
    test("exposes addedfile / uploadprogress / success / error / removedfile via .on(name, fn)", () => {
        const calls = { addedfile: 0, uploadprogress: 0, success: 0, error: 0, removedfile: 0 };

        dropzone.on("addedfile", () => calls.addedfile++);
        dropzone.on("uploadprogress", () => calls.uploadprogress++);
        dropzone.on("success", () => calls.success++);
        dropzone.on("error", () => calls.error++);
        dropzone.on("removedfile", () => calls.removedfile++);

        const fakeFile = { name: "x.png", size: 10, type: "image/png" };

        dropzone.emit("addedfile", fakeFile);
        dropzone.emit("uploadprogress", fakeFile, 50, 5);
        dropzone.emit("success", fakeFile, { token: "abc" });
        dropzone.emit("error", fakeFile, "boom", null);
        dropzone.emit("removedfile", fakeFile);

        expect(calls).toEqual({
            addedfile: 1,
            uploadprogress: 1,
            success: 1,
            error: 1,
            removedfile: 1,
        });
    });

    test("supports the configuration keys the wrapper relies on", () => {
        const fresh = new Dropzone(document.createElement("div"), {
            url: "/x",
            paramName: "file",
            acceptedFiles: "image/*",
            maxFilesize: 5,
            maxFiles: 3,
            parallelUploads: 2,
            uploadMultiple: false,
            previewsContainer: false,
            headers: { "X-CSRF-TOKEN": "t" },
        });

        expect(fresh.options.paramName).toBe("file");
        expect(fresh.options.acceptedFiles).toBe("image/*");
        expect(fresh.options.maxFilesize).toBe(5);
        expect(fresh.options.maxFiles).toBe(3);
        expect(fresh.options.parallelUploads).toBe(2);

        fresh.destroy();
    });
});
