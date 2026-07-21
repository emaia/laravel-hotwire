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

function nativeUploadHtml() {
    return `
        <form id="parent-form">
            <div data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar">
                <input type="file" hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-file-upload-target="dropzone"
                     tabindex="0"
                     role="button"
                     data-action="click->file-upload#openPicker keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker">
                    Choose files
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

async function mountPage(page, bodyHtml) {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${bodyHtml}</body></html>`);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await bundleController() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("file-upload", window.FileUploadController);
    });
}

async function bundleController() {
    const source = await readFile("resources/js/controllers/file_upload_controller.js", "utf8");
    return `
        const { Controller } = window.Stimulus;
        ${source
            .replace(/^\/\/ @hotwire-package\s*/m, "")
            .replace(/^import \{ Controller \} from "@hotwired\/stimulus";\s*/m, "")
            .replace("export default class extends Controller", "class FileUploadController extends Controller")}
        window.FileUploadController = FileUploadController;
    `;
}
