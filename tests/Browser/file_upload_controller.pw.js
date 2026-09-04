import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

const IMAGE_DATA_URL = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";

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

test("output-less uploads over 2 MB send and expose server size errors", async ({ page }) => {
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
        'data-file-upload-output-mode-value="none" data-file-upload-max-size-bytes-value="10485760"'
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

test("Turbo Stream uploads expose redirected HTML failures", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 200,
            contentType: "text/html",
            body: '<!DOCTYPE html><html><body><div data-toast-message-value="The file failed to upload."></div></body></html>',
        });
    });

    await mountPage(page, nativeUploadHtml(
        'data-file-upload-mode-value="turbo-stream"'
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

test("custom dropzone exposes upload lifecycle and feedback", async ({ page }) => {
    let releaseUpload;
    const uploadReleased = new Promise((resolve) => (releaseUpload = resolve));
    await page.route("**/upload", async (route) => {
        await uploadReleased;
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "custom-token" }),
        });
    });

    await mountPage(page, customUploadHtml());

    const uploader = page.locator('[data-controller="file-upload"]');
    const feedback = page.locator('[data-slot="file-upload-feedback"]');
    await expect(uploader).toHaveAttribute("data-loading", "false");
    await expect(uploader).toHaveAttribute("data-upload-state", "idle");
    await expect(feedback).toBeHidden();

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "avatar.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(uploader).toHaveAttribute("data-loading", "true");
    await expect(uploader).toHaveAttribute("data-upload-state", "uploading");
    await expect(feedback).toHaveText("Uploading avatar.png");
    await expect(feedback).toBeVisible();

    releaseUpload();

    await expect(uploader).toHaveAttribute("data-loading", "false");
    await expect(uploader).toHaveAttribute("data-upload-state", "done");
    await expect(feedback).toHaveText("Uploaded avatar.png");
});

test("image view swaps a local preview for the durable server image", async ({ page }) => {
    let releaseUpload;
    const uploadReleased = new Promise((resolve) => (releaseUpload = resolve));
    await page.route("**/upload", async (route) => {
        await uploadReleased;
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "image-token", preview_url: IMAGE_DATA_URL }),
        });
    });

    await mountPage(page, imageUploadHtml());
    const uploader = page.locator('[data-controller="file-upload"]');
    const preview = page.locator('[data-slot="file-upload-image-preview"]');

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "avatar.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(preview).toHaveAttribute("src", /^blob:/);
    await expect(uploader).toHaveAttribute("data-upload-state", "uploading");
    await expect(page.locator('[data-slot="attachment"]')).toHaveCount(0);

    releaseUpload();

    await expect(preview).toHaveAttribute("src", IMAGE_DATA_URL);
    await expect(uploader).toHaveAttribute("data-upload-state", "done");
    await expect(page.locator('input[type="hidden"][name="avatar"]')).toHaveValue("image-token");
});

test("hybrid JSON commits image state and renders its embedded Turbo Stream", async ({ page }) => {
    const stream = '<turbo-stream action="append" target="stream-target"><template><span id="stream-result">Saved</span></template></turbo-stream>';
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "hybrid-token", preview_url: IMAGE_DATA_URL, stream }),
        });
    });
    await mountPage(page, `${imageUploadHtml()}<div id="stream-target"></div>`, ["turbo"]);

    await uploadBrowserImage(page, "hybrid-avatar.png");

    await expect(page.locator('[data-slot="file-upload-image-preview"]')).toHaveAttribute("src", IMAGE_DATA_URL);
    await expect(page.locator('input[type="hidden"][name="avatar"]')).toHaveValue("hybrid-token");
    await expect(page.locator("#stream-result")).toHaveText("Saved");
});

test("hybrid JSON renders its stream before a delayed durable image finishes loading", async ({ page }) => {
    let releasePreview;
    const previewReleased = new Promise((resolve) => (releasePreview = resolve));
    const previewUrl = "http://test.local/preview.gif";
    const stream = '<turbo-stream action="append" target="stream-target"><template><span id="stream-result">Saved</span></template></turbo-stream>';
    await page.route("**/preview.gif", async (route) => {
        await previewReleased;
        await route.fulfill({
            status: 200,
            contentType: "image/gif",
            body: Buffer.from("R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==", "base64"),
        });
    });
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "delayed-token", preview_url: previewUrl, stream }),
        });
    });
    await mountPage(page, `${imageUploadHtml()}<div id="stream-target"></div>`, ["turbo"]);

    await uploadBrowserImage(page, "delayed-avatar.png");

    await expect(page.locator("#stream-result")).toHaveText("Saved");
    await expect(page.locator('[data-slot="file-upload-image-preview"]')).toHaveAttribute("src", /^blob:/);

    releasePreview();
    await expect(page.locator('[data-slot="file-upload-image-preview"]')).toHaveAttribute("src", previewUrl);
});

test("a Turbo Stream that removes the uploader does not start the next queued upload", async ({ page }) => {
    let uploadRequests = 0;
    const stream = '<turbo-stream action="remove" target="parent-form"></turbo-stream>';
    await page.route("**/upload", async (route) => {
        uploadRequests++;
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: `queued-token-${uploadRequests}`, stream }),
        });
    });
    await mountPage(page, nativeUploadHtml(
        'data-file-upload-multiple-value="true" data-file-upload-parallel-uploads-value="1"'
    ), ["turbo"]);

    await page.locator('[data-file-upload-target="input"]').setInputFiles([
        { name: "first.png", mimeType: "image/png", buffer: Buffer.from([137, 80, 78, 71]) },
        { name: "second.png", mimeType: "image/png", buffer: Buffer.from([137, 80, 78, 71]) },
    ]);

    await expect(page.locator("#parent-form")).toHaveCount(0);
    await page.waitForTimeout(100);
    expect(uploadRequests).toBe(1);
});

test("image view lets a size utility control the interactive surface", async ({ page }) => {
    await mountPage(page, imageUploadHtml());
    await page.addStyleTag({
        content: `
            * { box-sizing: border-box; }
            @layer components, utilities;
            @layer components {
                [data-slot="file-upload-dropzone"]:not([data-file-upload-dropzone-variant="bare"]) {
                    min-height: 8rem;
                    width: 100%;
                    padding: 1.5rem;
                }
                [data-slot="file-upload-dropzone"][data-file-upload-dropzone-variant="bare"] {
                    width: fit-content;
                    max-width: 100%;
                    cursor: pointer;
                }
                [data-view="image"] [data-file-upload-dropzone-variant="bare"] { position: relative; }
                [data-file-upload-dropzone-variant="bare"] > [data-slot="file-upload-image-base"] { display: flex; min-width: 0; }
                [data-view="image"] [data-slot="file-upload-image-preview"] { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
            }
            @layer utilities {
                .size-20 { width: 5rem; height: 5rem; }
            }
        `,
    });

    const box = await page.locator('[data-slot="file-upload-dropzone"]').boundingBox();
    expect(box?.width).toBe(80);
    expect(box?.height).toBe(80);
});

test("bare image preview overlays avatar content without changing its layout", async ({ page }) => {
    await page.route("**/upload", () => {});
    await mountPage(page, imageUploadHtml("", {
        dropzoneClass: "avatar-surface",
        content: '<span class="test-avatar"></span>',
    }));
    await page.addStyleTag({ content: `
        * { box-sizing: border-box; }
        [data-file-upload-dropzone-variant="bare"] {
            width: fit-content;
            max-width: 100%;
            cursor: pointer;
        }
        [data-view="image"] [data-file-upload-dropzone-variant="bare"] { position: relative; }
        [data-file-upload-dropzone-variant="bare"] > [data-slot="file-upload-image-base"] { display: flex; min-width: 0; }
        [data-view="image"] [data-slot="file-upload-image-preview"] { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        [data-view="image"] [data-slot="file-upload-dropzone"]:has(> [data-slot="file-upload-image-preview"]:not([hidden])) > [data-slot="file-upload-image-base"] { visibility: hidden; }
        .test-avatar { position: relative; display: inline-flex; width: 40px; height: 40px; border-radius: 9999px; background: black; }
    ` });

    const dropzone = page.locator('[data-file-upload-target="dropzone"]');
    const base = page.locator('[data-slot="file-upload-image-base"]');
    const before = await dropzone.boundingBox();
    await expect(base).toHaveCSS("visibility", "visible");
    await uploadBrowserImage(page, "avatar.png");
    const after = await dropzone.boundingBox();
    const preview = page.locator('[data-slot="file-upload-image-preview"]');
    const previewBox = await preview.boundingBox();
    const styles = await dropzone.evaluate((element) => {
        const style = getComputedStyle(element);
        return {
            background: style.backgroundColor,
            border: style.borderTopWidth,
            overflow: style.overflow,
            radius: style.borderRadius,
        };
    });

    await expect(preview).toHaveCSS("position", "absolute");
    await expect(base).toHaveCSS("visibility", "hidden");
    expect(before).toEqual(after);
    expect(previewBox).toEqual(after);
    expect(after?.width).toBe(40);
    expect(after?.height).toBe(40);
    expect(styles).toEqual({ background: "rgba(0, 0, 0, 0)", border: "0px", overflow: "visible", radius: "0px" });
});

test("bare image preview follows a horizontal banner surface", async ({ page }) => {
    await page.route("**/upload", () => {});
    await mountPage(page, imageUploadHtml("", {
        dropzoneClass: "banner-surface",
        content: '<span class="test-banner"></span>',
    }));
    await page.addStyleTag({ content: `
        * { box-sizing: border-box; }
        [data-file-upload-dropzone-variant="bare"] { width: fit-content; max-width: 100%; cursor: pointer; }
        [data-view="image"] [data-file-upload-dropzone-variant="bare"] { position: relative; }
        [data-file-upload-dropzone-variant="bare"] > [data-slot="file-upload-image-base"] { display: flex; min-width: 0; }
        [data-view="image"] [data-slot="file-upload-image-preview"] { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .banner-surface { width: 360px; }
        .test-banner { display: block; width: 100%; aspect-ratio: 3 / 1; background: black; }
    ` });

    const before = await page.locator('[data-file-upload-target="dropzone"]').boundingBox();
    await uploadBrowserImage(page, "banner.png");

    const surface = await page.locator('[data-file-upload-target="dropzone"]').boundingBox();
    const previewLocator = page.locator('[data-slot="file-upload-image-preview"]');
    const preview = await previewLocator.boundingBox();
    await expect(previewLocator).toHaveCSS("position", "absolute");
    expect(surface).toEqual(before);
    expect(preview).toEqual(surface);
    expect(surface?.width).toBe(360);
    expect(surface?.height).toBe(120);
    expect(preview?.width).toBe(360);
    expect(preview?.height).toBe(120);
});

test("bare non-image dropzone does not reorganize custom inline content", async ({ page }) => {
    const html = customUploadHtml().replace(
        '<span data-custom-uploader>Custom uploader</span>',
        '<span data-custom-first>First</span><span data-custom-second>Second</span>',
    );
    await mountPage(page, html);
    await page.addStyleTag({ content: `
        [data-slot="file-upload-dropzone"][data-file-upload-dropzone-variant="bare"] { width: fit-content; max-width: 100%; cursor: pointer; }
    ` });

    const first = await page.locator("[data-custom-first]").boundingBox();
    const second = await page.locator("[data-custom-second]").boundingBox();
    expect(first?.y).toBe(second?.y);
});

test("legacy dropzone markup keeps the default preset and same-layer app overrides", async ({ page }) => {
    await mountPage(page, nativeUploadHtml());
    const dropzone = page.locator('[data-file-upload-target="dropzone"]');
    await dropzone.evaluate((element) => {
        element.removeAttribute("data-file-upload-dropzone-variant");
        element.classList.add("app-dropzone");
    });
    await page.addStyleTag({ content: `
        @layer components {
            :where([data-slot="file-upload-dropzone"]:not([data-file-upload-dropzone-variant="bare"])) {
                min-height: 128px;
                background-color: rgb(1, 2, 3);
            }
            .app-dropzone { min-height: 40px; }
        }
    ` });

    await expect(dropzone).toHaveCSS("min-height", "40px");
    await expect(dropzone).toHaveCSS("background-color", "rgb(1, 2, 3)");
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

test("custom dropzone works inside an open Modal", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "modal-custom-token" }),
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
                    ${customUploadHtml()}
                </div>
            </div>
        </div>
    `,
        ["modal"],
    );

    const modal = page.locator('[data-modal-target="modal"]');
    await page.locator("#open-modal").click();
    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "modal-avatar.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('[data-controller="file-upload"]')).toHaveAttribute("data-upload-state", "done");
    await expect(page.locator('[data-slot="file-upload-feedback"]')).toHaveText("Uploaded modal-avatar.png");
    await expect(modal).toHaveAttribute("data-state", "open");
});

test("image view works inside an open Modal", async ({ page }) => {
    await routeImageUpload(page, "modal-image-token");
    await mountPage(
        page,
        `
        <div data-controller="modal" data-modal-lock-scroll-value="false">
            <button id="open-modal" type="button" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert role="dialog" aria-modal="true">
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">${imageUploadHtml()}</div>
            </div>
        </div>
    `,
        ["modal"],
    );

    const modal = page.locator('[data-modal-target="modal"]');
    await page.locator("#open-modal").click();
    await uploadBrowserImage(page, "modal-avatar.png");

    await expect(page.locator('[data-slot="file-upload-image-preview"]')).toHaveAttribute("src", IMAGE_DATA_URL);
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

test("custom dropzone works inside a persistent Dropdown", async ({ page }) => {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token: "dropdown-custom-token" }),
        });
    });

    await mountPage(
        page,
        `
        <div data-controller="dropdown" data-dropdown-close-on-select-value="false">
            <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Open uploads</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="none" hidden inert>
                ${customUploadHtml()}
            </div>
        </div>
    `,
        ["dropdown"],
    );

    const menu = page.locator('[data-dropdown-target="menu"]');
    await page.locator('[data-dropdown-target="trigger"]').click();
    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "dropdown-avatar.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });

    await expect(page.locator('[data-controller="file-upload"]')).toHaveAttribute("data-upload-state", "done");
    await expect(page.locator('[data-slot="file-upload-feedback"]')).toHaveText("Uploaded dropdown-avatar.png");
    await expect(menu).toHaveAttribute("data-state", "open");
    await expect(menu).toBeVisible();
});

test("image view works inside a persistent Dropdown", async ({ page }) => {
    await routeImageUpload(page, "dropdown-image-token");
    await mountPage(
        page,
        `
        <div data-controller="dropdown" data-dropdown-close-on-select-value="false">
            <button type="button" data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Open uploads</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="none" hidden inert>
                ${imageUploadHtml()}
            </div>
        </div>
    `,
        ["dropdown"],
    );

    const menu = page.locator('[data-dropdown-target="menu"]');
    await page.locator('[data-dropdown-target="trigger"]').click();
    await uploadBrowserImage(page, "dropdown-avatar.png");

    await expect(page.locator('[data-slot="file-upload-image-preview"]')).toHaveAttribute("src", IMAGE_DATA_URL);
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

test("Turbo Frame cache resets an interrupted custom dropzone", async ({ page }) => {
    let interceptUpload;
    const uploadIntercepted = new Promise((resolve) => (interceptUpload = resolve));
    await page.route("**/upload", () => {
        interceptUpload();
    });

    await mountPage(page, `<turbo-frame id="upload-frame">${customUploadHtml()}</turbo-frame>`);
    const uploader = page.locator('#upload-frame [data-controller="file-upload"]');
    const feedback = page.locator('[data-slot="file-upload-feedback"]');

    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name: "interrupted-avatar.png",
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });
    await uploadIntercepted;
    await expect(uploader).toHaveAttribute("data-upload-state", "uploading");

    await page.evaluate(() => {
        window.__customUploadReconnects = 0;
        document.addEventListener("file-upload:ready", () => window.__customUploadReconnects++);
        document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

        const current = document.querySelector('#upload-frame [data-controller="file-upload"]');
        current.replaceWith(current.cloneNode(true));
    });

    await expect.poll(() => page.evaluate(() => window.__customUploadReconnects)).toBe(1);
    await expect(uploader).toHaveAttribute("data-loading", "false");
    await expect(uploader).toHaveAttribute("data-upload-state", "idle");
    await expect(feedback).toBeHidden();
});

test("Turbo Frame reconnect preserves a durable image preview", async ({ page }) => {
    await routeImageUpload(page, "frame-image-token");
    await mountPage(page, `<turbo-frame id="upload-frame">${imageUploadHtml()}</turbo-frame>`);
    await uploadBrowserImage(page, "frame-avatar.png");

    const preview = page.locator('[data-slot="file-upload-image-preview"]');
    await expect(preview).toHaveAttribute("src", IMAGE_DATA_URL);

    await page.evaluate(() => {
        window.__imageUploadReconnects = 0;
        document.addEventListener("file-upload:ready", () => window.__imageUploadReconnects++);
        document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

        const current = document.querySelector('#upload-frame [data-controller="file-upload"]');
        current.replaceWith(current.cloneNode(true));
    });

    await expect.poll(() => page.evaluate(() => window.__imageUploadReconnects)).toBe(1);
    await expect(preview).toHaveAttribute("src", IMAGE_DATA_URL);
    await expect(preview).toBeVisible();
    await expect(page.locator('#upload-frame [data-controller="file-upload"]')).toHaveAttribute("data-upload-state", "done");
});

function nativeUploadHtml(extraAttrs = "", customDropzone = false) {
    const multiple = extraAttrs.includes('data-file-upload-multiple-value="true"') ? "multiple" : "";
    const dropzoneContent = customDropzone
        ? '<span data-custom-uploader>Custom uploader</span>'
        : '<span data-file-upload-target="feedback" data-file-upload-default-feedback="Drop files here">Drop files here</span>';
    const customFeedback = customDropzone
        ? '<p data-slot="file-upload-feedback" data-file-upload-target="feedback" data-file-upload-default-feedback="" hidden></p>'
        : "";
    const dropzoneVariant = customDropzone ? "bare" : "default";

    return `
        <form id="parent-form">
            <div data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar"
                 ${extraAttrs}>
                <input type="file" ${multiple} hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-slot="file-upload-dropzone"
                     data-file-upload-dropzone-variant="${dropzoneVariant}"
                     data-file-upload-target="dropzone"
                     tabindex="0"
                     role="button"
                     data-action="click->file-upload#openPicker keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker">
                     ${dropzoneContent}
                </div>
                ${customFeedback}
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

function customUploadHtml(extraAttrs = "") {
    return nativeUploadHtml(extraAttrs, true);
}

function imageUploadHtml(extraAttrs = "", { dropzoneClass = "size-20", content = "Current image" } = {}) {
    return `
        <form id="parent-form">
            <div data-slot="file-upload"
                 data-view="image"
                 data-controller="file-upload"
                 data-file-upload-url-value="http://test.local/upload"
                 data-file-upload-hidden-name-value="avatar"
                 data-file-upload-accept-value="image/*"
                 data-file-upload-view-value="image"
                 ${extraAttrs}>
                <input type="file" hidden data-file-upload-target="input" data-action="change->file-upload#select">
                <div data-slot="file-upload-dropzone"
                     data-file-upload-dropzone-variant="bare"
                     class="${dropzoneClass}"
                     data-file-upload-target="dropzone"
                     tabindex="0"
                     role="button"
                     data-action="click->file-upload#openPicker keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker">
                    <div data-slot="file-upload-image-base">${content}</div>
                    <img data-slot="file-upload-image-preview" data-file-upload-target="imagePreview" alt="" hidden>
                </div>
                <p data-slot="file-upload-feedback" data-file-upload-target="feedback" data-file-upload-default-feedback="" hidden></p>
                <div role="status" data-file-upload-target="announcer"></div>
            </div>
        </form>
    `;
}

async function routeImageUpload(page, token) {
    await page.route("**/upload", async (route) => {
        await route.fulfill({
            status: 201,
            contentType: "application/json",
            body: JSON.stringify({ token, preview_url: IMAGE_DATA_URL }),
        });
    });
}

async function uploadBrowserImage(page, name) {
    await page.locator('[data-file-upload-target="input"]').setInputFiles({
        name,
        mimeType: "image/png",
        buffer: Buffer.from([137, 80, 78, 71]),
    });
}

async function mountPage(page, bodyHtml, controllers = []) {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${bodyHtml}</body></html>`);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    if (controllers.includes("turbo")) {
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    }
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
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");

    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export class FocusTrap", "class FocusTrap");

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function activateTopOverlay", "function activateTopOverlay")
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
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
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
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export default class extends Controller", "class DropdownController extends Controller");

    const uploadFeedback = (await readFile("resources/js/controllers/_upload_feedback.js", "utf8")).replace(
        "export function createUploadFeedback",
        "function createUploadFeedback",
    );

    const fileUpload = (await readFile("resources/js/controllers/file_upload_controller.js", "utf8"))
        .replace(/^\/\/ @hotwire-package\s*/m, "")
        .replace(/^import \{ Controller \} from "@hotwired\/stimulus";\s*/m, "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_upload_feedback\.js";\s*/, "")
        .replace("export default class extends Controller", "class FileUploadController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${composition}
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${presence}
        ${overlay}
        ${frameOverlay}
        ${floating}
        ${modal}
        ${dropdown}
        ${uploadFeedback}
        ${fileUpload}
        window.ModalController = ModalController;
        window.DropdownController = DropdownController;
        window.FileUploadController = FileUploadController;
    `;
}
