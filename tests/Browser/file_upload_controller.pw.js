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

    await mountPage(page, `
        <form id="parent-form">
            <div data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar">
                <div role="status" data-file-upload-target="announcer"></div>
            </div>
        </form>
    `);

    const hiddenInput = page.locator('input[type="file"].dz-hidden-input');
    await hiddenInput.setInputFiles({
        name: "photo.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    const hidden = page.locator('#parent-form input[type="hidden"][name="avatar"]');
    await expect(hidden).toHaveValue("01HQVZTOKEN");
    await expect(page.locator('[data-file-upload-target="announcer"]')).toContainText("Uploaded");
});

test("server-side 422 — error message appears in the announcer, no hidden input added", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 422,
            contentType: "application/json",
            body: JSON.stringify({ message: "File too large" }),
        });
    });

    await mountPage(page, `
        <form id="parent-form">
            <div data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar">
                <div role="status" data-file-upload-target="announcer"></div>
            </div>
        </form>
    `);

    const hiddenInput = page.locator('input[type="file"].dz-hidden-input');
    await hiddenInput.setInputFiles({
        name: "huge.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('[data-file-upload-target="announcer"]')).toContainText(/fail/i);
    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveCount(0);
});

test("removedfile in real browser — hidden input is removed and announcer reads 'Removed'", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "01HQVZTOKEN" }),
        });
    });

    await mountPage(page, `
        <form id="parent-form">
            <div data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar">
                <div role="status" data-file-upload-target="announcer"></div>
            </div>
        </form>
    `);

    const hiddenInput = page.locator('input[type="file"].dz-hidden-input');
    await hiddenInput.setInputFiles({
        name: "photo.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveCount(1);

    // Real Dropzone removedfile chain — same path the X button click triggers.
    await page.evaluate(() => {
        const el = document.querySelector('[data-controller~="file-upload"]');
        const ctrl = window.app.getControllerForElementAndIdentifier(el, "file-upload");
        ctrl.dropzone.removeFile(ctrl.dropzone.files[0]);
    });

    await expect(page.locator('#parent-form input[type="hidden"][name="avatar"]')).toHaveCount(0);
    await expect(page.locator('[data-file-upload-target="announcer"]')).toContainText("Removed");
});

test("keyboard activation — Enter on the focused wrapper triggers the file picker", async ({ page }) => {
    await mountPage(page, `
        <div data-controller="file-upload"
             data-action="keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker"
             data-file-upload-url-value="http://test.local/upload"
             data-file-upload-hidden-name-value="avatar"
             tabindex="0"
             role="button">
            <div role="status" data-file-upload-target="announcer"></div>
        </div>
    `);

    // Spy on hiddenFileInput.click — Dropzone uses this to open the picker.
    const sentinel = await page.evaluate(() => {
        const el = document.querySelector('[data-controller~="file-upload"]');
        const ctrl = window.app.getControllerForElementAndIdentifier(el, "file-upload");
        window.__pickerClicks = 0;
        ctrl.dropzone.hiddenFileInput.click = () => window.__pickerClicks++;
        return ctrl.dropzone.hiddenFileInput !== null;
    });
    expect(sentinel).toBe(true);

    const wrapper = page.locator('[data-controller~="file-upload"]');
    await wrapper.focus();
    await expect(wrapper).toBeFocused();

    await page.keyboard.press("Enter");
    expect(await page.evaluate(() => window.__pickerClicks)).toBe(1);

    await page.keyboard.press("Space");
    expect(await page.evaluate(() => window.__pickerClicks)).toBe(2);
});

async function mountPage(page, bodyHtml) {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${bodyHtml}</body></html>`);
    await page.addStyleTag({ path: "node_modules/@deltablot/dropzone/dist/dropzone.css" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@deltablot/dropzone/dist/dropzone-min.js" });
    await page.addScriptTag({ content: await bundleController() });
    await page.evaluate(() => {
        window.Dropzone.autoDiscover = false;
        window.app = window.Stimulus.Application.start();
        window.app.register("file-upload", window.FileUploadController);
    });
}

async function bundleController() {
    const source = await readFile("resources/js/controllers/file_upload_controller.js", "utf8");
    return `
        const { Controller } = window.Stimulus;
        const Dropzone = window.Dropzone;
        ${source
            .replace(/^\/\/ @hotwire-package\s*/m, "")
            .replace(/^import \{ Controller \} from "@hotwired\/stimulus";\s*/m, "")
            .replace(/^import Dropzone from "@deltablot\/dropzone";\s*/m, "")
            .replace(/^import "@deltablot\/dropzone\/dist\/dropzone\.css";\s*/m, "")
            .replace("Dropzone.autoDiscover = false;", "")
            .replace("export default class extends Controller", "class FileUploadController extends Controller")}
        window.FileUploadController = FileUploadController;
    `;
}
