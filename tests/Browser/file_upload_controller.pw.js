import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("real upload — mocked 201 token lands in a hidden input on the form", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "01HQVZTOKEN" }),
        });
    });

    await mountPage(page, nativeUploadHtml());

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "photo.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    const hidden = page.locator('#parent-form input[type="hidden"][name="avatar"]');
    await expect(hidden).toHaveValue("01HQVZTOKEN");
    await expect(page.locator('[data-file-upload-target="announcer"]')).toContainText("Uploaded");
    await expect(page.locator('[data-slot="attachment"]')).toHaveAttribute("data-state", "done");
});

test("server-side 422 — attachment shows an error and no hidden input is added", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 422,
            contentType: "application/json",
            body: JSON.stringify({ message: "File too large" }),
        });
    });

    await mountPage(page, nativeUploadHtml());

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "huge.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('[data-file-upload-target="announcer"]')).toContainText(/fail/i);
    await expect(page.locator('[data-slot="attachment"]')).toHaveAttribute("data-state", "error");
    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveCount(0);
});

test("preview-disabled uploads over 2 MB send and expose server size errors", async ({ page }) => {
    let uploadRequests = 0;
    await page.route("**/upload", async (route) => {
        uploadRequests++;
        await route.fulfill({
            status: 413,
            contentType: "text/html",
            body: "<html><body>Request Entity Too Large</body></html>",
        });
    });

    await mountPage(page, nativeUploadHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-max-size-bytes-value="10485760"'
    ));

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "large.png",
        mimeType: "image/png",
        buffer: Buffer.alloc(3 * 1024 * 1024),
    });

    await expect.poll(() => uploadRequests).toBe(1);
    await expect(page.locator('[data-file-upload-target="dropzone"]')).toHaveAttribute("aria-invalid", "true");
    await expect(page.locator('[data-file-upload-target="feedback"]')).toHaveText("File is too large");
    await expect(page.locator('[data-slot="attachment"]')).toHaveCount(0);
});

test("preview-disabled Turbo uploads expose redirected HTML failures", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 200,
            contentType: "text/html",
            body: '<!DOCTYPE html><html><body><div data-toast-message-value="The file failed to upload."></div></body></html>',
        });
    });

    await mountPage(page, nativeUploadHtml(
        'data-file-upload-preview-value="false" data-file-upload-emit-hidden-value="false" data-file-upload-turbo-stream-value="true"'
    ));

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "large.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('[data-file-upload-target="dropzone"]')).toHaveAttribute("aria-invalid", "true");
    await expect(page.locator('[data-file-upload-target="feedback"]')).toHaveText(
        "The server rejected this file. Check the file type and server upload-size limit."
    );
    await expect(page.locator('[data-slot="attachment"]')).toHaveCount(0);
});

test("remove in real browser — hidden input is removed and announcer reads Removed", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "01HQVZTOKEN" }),
        });
    });

    await mountPage(page, nativeUploadHtml());

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "photo.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveCount(1);
    await page.locator('[data-slot="attachment"] [data-file-upload-remove]').click();

    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveCount(0);
    await expect(page.locator('[data-file-upload-target="announcer"]')).toContainText("Removed");
});

test("keyboard activation — Enter and Space on the drop area trigger the file picker", async ({ page }) => {
    await mountPage(page, nativeUploadHtml());

    const sentinel = await page.evaluate(() => {
        const input = document.querySelector('[data-file-upload-target="input"]');
        window.__pickerClicks = 0;
        input.click = () => window.__pickerClicks++;
        return input !== null;
    });
    expect(sentinel).toBe(true);

    const dropzone = page.locator('[data-file-upload-target="dropzone"]');
    await dropzone.focus();
    await expect(dropzone).toBeFocused();

    await page.keyboard.press("Enter");
    expect(await page.evaluate(() => window.__pickerClicks)).toBe(1);

    await page.keyboard.press("Space");
    expect(await page.evaluate(() => window.__pickerClicks)).toBe(2);
});

test("native uploader works inside an open Modal", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "modal-token" }),
        });
    });

    await mountPage(
        page,
        `
        <div data-controller="modal" data-modal-lock-scroll-value="false">
            <button id="open-modal" type="button" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert role="dialog" aria-modal="true">
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    ${nativeUploadHtml()}
                </div>
            </div>
        </div>
    `,
        ["modal"],
    );

    const modal = page.locator('[data-modal-target="modal"]');
    await page.locator("#open-modal").click();
    await expect(modal).toHaveAttribute("data-state", "open");

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "modal-photo.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveValue("modal-token");
    await expect(page.locator('[data-slot="attachment"]')).toHaveAttribute("data-state", "done");
    await expect(modal).toHaveAttribute("data-state", "open");
});

test("interactive uploader controls do not close a Dropdown when close-on-select is false", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "dropdown-token" }),
        });
    });

    await mountPage(
        page,
        `
        <div data-controller="dropdown" data-dropdown-close-on-select-value="false">
            <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Open uploads</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="none" hidden inert>
                ${nativeUploadHtml()}
            </div>
        </div>
    `,
        ["dropdown"],
    );

    const menu = page.locator('[data-dropdown-target="menu"]');
    await page.locator('[data-dropdown-target="trigger"]').click();
    await expect(menu).toHaveAttribute("data-state", "open");

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "dropdown-photo.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('[data-slot="attachment"]')).toHaveAttribute("data-state", "done");
    await page.locator("[data-file-upload-remove]").click();

    await expect(page.locator('[data-slot="attachment"]')).toHaveCount(0);
    await expect(menu).toHaveAttribute("data-state", "open");
    await expect(menu).toBeVisible();
});

test("Turbo Frame cache reconnect normalizes an interrupted upload and allows reselection", async ({ page }) => {
    let interceptFirstUpload;
    const firstUpload = new Promise((resolve) => {
        interceptFirstUpload = resolve;
    });
    let uploadCount = 0;

    await page.route("**/upload", async (route) => {
        uploadCount++;
        if (uploadCount === 1) {
            interceptFirstUpload();
            return;
        }

        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "recovered-token" }),
        });
    });

    await mountPage(page, `<turbo-frame id="upload-frame">${nativeUploadHtml()}</turbo-frame>`);

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "interrupted.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });
    await firstUpload;
    await expect(page.locator('[data-slot="attachment"]')).toHaveAttribute("data-state", "uploading");

    await page.evaluate(() => {
        window.__fileUploadReconnects = 0;
        document.addEventListener("file-upload:ready", () => window.__fileUploadReconnects++);
        document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

        const uploader = document.querySelector('#upload-frame [data-controller~="file-upload"]');
        uploader.replaceWith(uploader.cloneNode(true));
    });

    await expect.poll(() => page.evaluate(() => window.__fileUploadReconnects)).toBe(1);
    await expect(page.locator('[data-slot="attachment"]')).toHaveCount(0);

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "interrupted.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveValue("recovered-token");
    await expect(page.locator('[data-slot="attachment"]')).toHaveAttribute("data-state", "done");
});

function nativeUploadHtml(extraAttrs = "") {
    return `
        <form id="parent-form">
            <div data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar"
                 ${extraAttrs}>
                <input type="file" hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-file-upload-target="dropzone"
                     tabindex="0"
                     role="button"
                     data-action="click->file-upload#openPicker keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker">
                     <span data-file-upload-target="feedback" data-file-upload-default-feedback="Drop files here">Drop files here</span>
                </div>
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
            </div>
        </form>
    `;
}

async function mountPage(page, bodyHtml, controllers = []) {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${bodyHtml}</body></html>`);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await bundleControllers() });
    await page.evaluate((names) => {
        window.app = window.Stimulus.Application.start();
        window.app.register("file-upload", window.FileUploadController);
        if (names.includes("modal")) window.app.register("modal", window.ModalController);
        if (names.includes("dropdown")) window.app.register("dropdown", window.DropdownController);
    }, controllers);
}

async function bundleControllers() {
    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8")).replace(
        "export class FocusTrap",
        "class FocusTrap",
    );

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function isTopOverlay", "function isTopOverlay")
        .replace("export function overlayPosition", "function overlayPosition");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8")).replace(
        "export function createTopLayer",
        "function createTopLayer",
    );

    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8")).replace(
        "export function createPresence",
        "function createPresence",
    );

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_stack\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

    const frameOverlay = (await readFile("resources/js/controllers/_frame_overlay.js", "utf8")).replace(
        "export function createFrameOverlay",
        "function createFrameOverlay",
    );

    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    const modal = (await readFile("resources/js/controllers/modal_controller.js", "utf8"))
        .replace(/^\/\/ @hotwire-package\s*/m, "")
        .replace(/^import \{ Controller \} from "@hotwired\/stimulus";\s*/m, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace("export default class ModalController extends Controller", "class ModalController extends Controller");

    const dropdown = (await readFile("resources/js/controllers/dropdown_controller.js", "utf8"))
        .replace(/^\/\/ @hotwire-package\s*/m, "")
        .replace(/^import \{ Controller \} from "@hotwired\/stimulus";\s*/m, "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export default class extends Controller", "class DropdownController extends Controller");

    const fileUpload = (await readFile("resources/js/controllers/file_upload_controller.js", "utf8"))
        .replace(/^\/\/ @hotwire-package\s*/m, "")
        .replace(/^import \{ Controller \} from "@hotwired\/stimulus";\s*/m, "")
        .replace("export default class extends Controller", "class FileUploadController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${presence}
        ${overlay}
        ${frameOverlay}
        ${floating}
        ${modal}
        ${dropdown}
        ${fileUpload}
        window.ModalController = ModalController;
        window.DropdownController = DropdownController;
        window.FileUploadController = FileUploadController;
    `;
}
