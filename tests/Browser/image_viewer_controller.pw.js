import { expect, test } from "@playwright/test";
import { execFileSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { compileCssFixture, packageSourceEntrypoint } from "../../scripts/css_build_contract.js";

let bundle;
test.beforeAll(() => {
    bundle = execFileSync(
        "bun",
        ["build", "./tests/Browser/fixtures/image_viewer.js", "--target=browser", "--format=iife"],
        { maxBuffer: 10 * 1024 * 1024 },
    ).toString();
});

const image = (width = 1600, height = 1200, color = "green") =>
    `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}"><rect width="100%" height="100%" fill="${color}"/></svg>`)}`;
const item = (id, url = image(), caption = id) =>
    `<a id="${id}" href="${url}"><img src="${image(80, 60)}" data-image-key="${id}" data-original="${url}" data-caption="${caption}" alt="${id}"></a>`;

function markup(
    items = item("one") + item("two", image(800, 1200, "blue")),
    thumbnails = false,
    size = "default",
    { rotate = false, flip = false, arrows = false, tools = false } = {},
) {
    // The touch tools fixture mirrors the component's icon buttons so preset measurements stay realistic.
    const control = (id, target, label) =>
        tools
            ? `<button ${id ? `id="${id}"` : ""} data-slot="image-viewer-button" data-image-viewer-target="${target}" data-action="image-viewer#${target}" aria-label="${label}"><svg data-slot="image-viewer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/></svg></button>`
            : `<button ${id ? `id="${id}"` : ""} data-image-viewer-target="${target}" data-action="image-viewer#${target}">${label}</button>`;
    return `<div id="gallery" data-controller="image-viewer">
        <div id="sources" data-image-viewer-target="source">${items}</div>
        <div data-slot="image-viewer-overlay" data-image-viewer-target="overlay" hidden inert>
            <div data-slot="image-viewer-backdrop" data-image-viewer-target="backdrop"></div>
            <section data-slot="image-viewer-dialog" data-image-viewer-target="dialog" data-size="${size}" role="dialog" aria-modal="true" aria-label="Photos" tabindex="-1">
                <div data-slot="image-viewer-toolbar" role="group" aria-label="Image controls">
                    <span data-slot="image-viewer-counter" data-image-viewer-target="counter"></span>
                    <div data-slot="image-viewer-actions">
                        ${
                            arrows
                                ? ""
                                : `<button id="prev" data-side="previous" data-image-viewer-target="previous" data-action="image-viewer#previous">Previous</button>
                        <button id="next" data-side="next" data-image-viewer-target="next" data-action="image-viewer#next">Next</button>`
                        }
                        ${
                            tools
                                ? `<button id="tools-toggle" data-slot="image-viewer-tools-trigger" data-image-viewer-target="toolsToggle" data-action="image-viewer#toggleTools" aria-expanded="false" aria-controls="tools" aria-label="More controls">More</button>
                        <div id="tools" data-slot="image-viewer-tools" data-image-viewer-target="tools" data-state="closed">`
                                : ""
                        }
                        ${control("zoom", "zoomIn", "Zoom in")}
                        ${control("", "zoomOut", "Zoom out")}
                        ${control("reset", "reset", "Reset")}
                        ${
                            rotate
                                ? `
                            ${control("rotate-left", "rotateLeft", "Rotate left")}
                            ${control("rotate-right", "rotateRight", "Rotate right")}
                        `
                                : ""
                        }
                        ${
                            flip
                                ? `
                            ${control("flip-horizontal", "flipHorizontal", "Flip horizontally")}
                            ${control("flip-vertical", "flipVertical", "Flip vertically")}
                        `
                                : ""
                        }
                        ${tools ? `<span data-slot="image-viewer-zoom-control">` : `<span data-image-viewer-target="zoomLabel"></span>`}
                        <select data-slot="image-viewer-zoom" data-image-viewer-target="zoomSelect" data-action="change->image-viewer#changeZoom" aria-label="Zoom level" disabled>
                            <option value="fit" data-label="Fit image">Fit image</option>
                            <option value="0.25">25%</option>
                            <option value="0.5">50%</option>
                            <option value="0.75">75%</option>
                            <option value="1">100%</option>
                            <option value="1.5">150%</option>
                            <option value="2">200%</option>
                            <option value="3">300%</option>
                            <option value="4">400%</option>
                            <option value="current" disabled hidden></option>
                        </select>
                        ${tools ? `<span id="zoom-value" data-slot="image-viewer-zoom-value" data-image-viewer-target="zoomLabel" aria-hidden="true"></span></span></div>` : ""}
                        <a data-image-viewer-target="download" data-turbo="false" download>Download</a>
                        <button id="close" data-action="image-viewer#close">Close</button>
                    </div>
                </div>
                <div data-slot="image-viewer-stage">
                <div data-slot="image-viewer-viewport" data-image-viewer-target="viewport" role="group" tabindex="0" aria-label="Image">
                    <img data-slot="image-viewer-image" data-image-viewer-target="image" alt="" hidden>
                    <div data-slot="image-viewer-status" data-image-viewer-target="loading" role="status" aria-atomic="true" hidden>
                        <svg data-slot="image-viewer-loading-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                        <span>Loading</span>
                    </div>
                    <div data-slot="image-viewer-status" data-image-viewer-target="error" role="status" aria-atomic="true" hidden>
                        <svg data-slot="image-viewer-error-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h18v18H3zM3 3l18 18" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                        <span>Cannot load image</span>
                    </div>
                </div>
                ${
                    arrows
                        ? `
                    <button id="prev" data-slot="image-viewer-arrow" data-side="previous" data-image-viewer-target="previous" data-action="image-viewer#previous" aria-label="Previous">‹</button>
                    <button id="next" data-slot="image-viewer-arrow" data-side="next" data-image-viewer-target="next" data-action="image-viewer#next" aria-label="Next">›</button>
                `
                        : ""
                }
                </div>
                <p data-slot="image-viewer-caption" data-image-viewer-target="caption"></p>
                ${
                    thumbnails
                        ? `
                    <div data-slot="image-viewer-thumbnails" data-image-viewer-target="thumbnails" role="group" aria-label="Choose image"></div>
                    <template data-image-viewer-target="thumbnailTemplate">
                        <button type="button" data-slot="image-viewer-thumbnail" data-label="View image :index of :total" data-action="image-viewer#selectThumbnail">
                            <img data-slot="image-viewer-thumbnail-image" alt="" loading="lazy" draggable="false">
                        </button>
                    </template>
                `
                        : ""
                }
            </section>
        </div>
    </div>`;
}

async function start(
    page,
    items,
    { thumbnails = false, size = "default", rotate = false, flip = false, arrows = false, tools = false } = {},
) {
    await page.setContent(`<meta name="viewport" content="width=device-width, initial-scale=1"><base href="https://gallery.test/"><style>
        /*
         * Mirror the application build's layer order before structural.css names its layers: the stand-ins
         * stay lowest and Tailwind's preflight stays below the preset compiled into later tests.
         */
        @layer fixture, properties, theme, base, components, utilities, hotwire-accessibility;
        ${readFileSync("resources/css/structural.css", "utf8")}
        @layer fixture {
            [data-slot="image-viewer-toolbar"] { display:flex; gap:8px; padding:8px }
            [data-slot="image-viewer-dialog"] { background:Canvas; color:CanvasText }
            #sources img { width:80px; height:60px }
            [data-slot="image-viewer-thumbnail"] { flex-shrink:0; width:64px; height:48px }
        }
    </style>${markup(items, thumbnails, size, { rotate, flip, arrows, tools })}`);
    await page.addScriptTag({ content: bundle });
    await page.waitForFunction(() => window.viewer()?.connected);
}

const overlay = (page) => page.locator('[data-image-viewer-target="overlay"]');
const displayed = (page) => page.locator('[data-image-viewer-target="image"]');

for (const arrows of [false, true]) {
    for (const direction of ["ltr", "rtl"]) {
        test(`${direction} navigation agrees across dialog, viewport, toolbar, thumbnails and swipe with arrows ${arrows}`, async ({
            page,
            context,
        }) => {
            await start(page, item("one") + item("two") + item("three"), { arrows, thumbnails: true });
            await page.locator("#gallery").evaluate((root, dir) => (root.dir = dir), direction);
            await page.locator("#prev, #next").evaluateAll((buttons) => {
                for (const button of buttons) {
                    button.dataset.slot ??= "image-viewer-button";
                    button.insertAdjacentHTML(
                        "beforeend",
                        '<svg data-slot="image-viewer-icon" aria-hidden="true"><path d="M3 3h1"/></svg>',
                    );
                }
            });
            await page.locator("#two").click();
            await expect(displayed(page)).toBeVisible();
            for (const id of ["prev", "next"])
                await expect(page.locator(`#${id} svg`)).toHaveCSS("rotate", direction === "rtl" ? "180deg" : "none");
            const thumbnails = page.locator('[data-slot="image-viewer-thumbnail"]');
            const counter = page.locator('[data-image-viewer-target="counter"]');
            const nextKey = direction === "rtl" ? "ArrowLeft" : "ArrowRight";
            const previousKey = direction === "rtl" ? "ArrowRight" : "ArrowLeft";
            for (const target of [
                '[data-image-viewer-target="dialog"]',
                '[data-image-viewer-target="viewport"]',
                "#close",
                '[data-slot="image-viewer-thumbnail"][aria-current="true"]',
            ]) {
                await thumbnails.nth(1).click();
                await page.locator(target).focus();
                await page.keyboard.press(nextKey);
                await expect(counter).toHaveText("3 / 3");
                await thumbnails.nth(1).click();
                await page.locator(target).focus();
                await page.keyboard.press(previousKey);
                await expect(counter).toHaveText("1 / 3");
            }
            await thumbnails.nth(1).click();
            const box = await page.locator('[data-image-viewer-target="viewport"]').boundingBox();
            const x = box.x + box.width / 2;
            const y = box.y + box.height / 2;
            const session = await context.newCDPSession(page);
            for (const [dx, expected] of [
                [200, direction === "rtl" ? "3 / 3" : "1 / 3"],
                [-200, direction === "rtl" ? "1 / 3" : "3 / 3"],
            ]) {
                await thumbnails.nth(1).click();
                await expect(displayed(page)).toBeVisible();
                await session.send("Input.dispatchTouchEvent", { type: "touchStart", touchPoints: [{ x, y, id: 1 }] });
                await session.send("Input.dispatchTouchEvent", {
                    type: "touchMove",
                    touchPoints: [{ x: x + dx, y, id: 1 }],
                });
                await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [] });
                await expect(counter).toHaveText(expected);
            }
            await thumbnails.nth(1).click();
            await page.getByRole("combobox", { name: "Zoom level" }).selectOption("2");
            const viewport = page.locator('[data-image-viewer-target="viewport"]');
            await viewport.focus();
            const before = await page.evaluate(() => window.viewer().geometry.x);
            await page.keyboard.press("ArrowRight");
            expect(await page.evaluate(() => window.viewer().geometry.x)).toBeCloseTo(before + 40);
            await expect(counter).toHaveText("2 / 3");
            await thumbnails.nth(0).click();
            await page.locator("#gallery").evaluate((root) => (root.dataset.imageViewerLoopValue = "true"));
            await expect(page.locator("#prev")).toBeEnabled();
            await page.locator('[data-image-viewer-target="dialog"]').focus();
            await page.keyboard.press(previousKey);
            await expect(counter).toHaveText("3 / 3");
            await page.keyboard.press(nextKey);
            await expect(counter).toHaveText("1 / 3");
        });
    }
}

test("opening focuses the dialog rather than a control, with predictable Tab and focus return", async ({ page }) => {
    let release;
    await page.route("https://gallery.test/focus-pending.svg", async (route) => {
        await new Promise((resolve) => (release = resolve));
        await route.fulfill({
            contentType: "image/svg+xml",
            body: '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1200"/>',
        });
    });
    await start(page, item("one", "/focus-pending.svg"), { arrows: true, thumbnails: true, rotate: true, flip: true });
    await page.locator("#one").click();
    const dialog = page.locator('[data-image-viewer-target="dialog"]');
    await expect(dialog).toBeFocused();
    await expect.poll(() => Boolean(release)).toBe(true);
    release();
    await expect(displayed(page)).toBeVisible();
    await expect(dialog).toBeFocused();
    await page.keyboard.press("Tab");
    await expect(page.locator("#zoom")).toBeFocused();
    await page.keyboard.press("Shift+Tab");
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]')).toBeFocused();
    await page.keyboard.press("Escape");
    await expect(page.locator("#one")).toBeFocused();
    // The dialog receives focus only to anchor the modal context; it is not a control and shows no ring.
    await page.keyboard.press("Enter");
    await expect(dialog).toBeFocused();
    await expect(dialog).toHaveCSS("outline-style", "none");
});

test.describe("touch presentation", () => {
    test.use({ hasTouch: true, isMobile: true, viewport: { width: 390, height: 844 } });

    const center = async (locator) => {
        const box = await locator.boundingBox();
        return { x: box.x + box.width / 2, y: box.y + box.height / 2 };
    };
    const dialog = (page) => page.locator('[data-image-viewer-target="dialog"]');
    const viewport = (page) => page.locator('[data-image-viewer-target="viewport"]');
    const toolbar = (page) => page.locator('[data-slot="image-viewer-toolbar"]');
    const tap = async (page, locator = viewport(page)) => {
        const point = await center(locator);
        await page.touchscreen.tap(point.x, point.y);
    };
    const open = async (page, options = {}) => {
        await start(page, item("one") + item("two") + item("three"), {
            arrows: true,
            thumbnails: true,
            rotate: true,
            flip: true,
            tools: true,
            ...options,
        });
        await page.locator("#one img").tap();
        await expect(displayed(page)).toBeVisible();
        await page.evaluate(() => window.viewer().overlay.settled);
    };

    test("overlays the controls on a full-viewport stage and collapses zoom and orientation tools", async ({
        page,
    }) => {
        await open(page);
        expect(await dialog(page).boundingBox()).toEqual({ x: 0, y: 0, width: 390, height: 844 });
        expect(await viewport(page).boundingBox()).toEqual(await dialog(page).boundingBox());
        const bar = await toolbar(page).boundingBox();
        expect(bar.y).toBe(0);
        expect(
            await page.locator('[data-image-viewer-target="thumbnails"]').evaluate((node) => {
                const box = node.getBoundingClientRect();
                return box.bottom;
            }),
        ).toBe(844);
        const trigger = page.locator("#tools-toggle");
        await expect(trigger).toBeVisible();
        await expect(trigger).toHaveAttribute("aria-expanded", "false");
        for (const id of ["#zoom", "#reset", "#rotate-left", "#flip-vertical"])
            await expect(page.locator(id)).toBeHidden();
        await expect(page.getByRole("combobox", { name: "Zoom level" })).toBeHidden();
        expect(bar.height).toBeLessThan(80);
        await trigger.tap();
        await expect(trigger).toHaveAttribute("aria-expanded", "true");
        await expect(page.locator("#tools")).toHaveAttribute("data-state", "open");
        for (const id of ["#zoom", "#rotate-left", "#flip-vertical"]) await expect(page.locator(id)).toBeVisible();
        // Fit stays reachable through the zoom selector and a double tap, so its button gives way on touch.
        await expect(page.locator("#reset")).toBeHidden();
        await expect(page.getByRole("combobox", { name: "Zoom level" }).locator('option[value="fit"]')).toHaveText(
            /^Fit image/,
        );
        await page.locator("#zoom").tap();
        await expect.poll(() => page.evaluate(() => window.viewer().atFit)).toBe(false);
        await trigger.tap();
        await expect(trigger).toHaveAttribute("aria-expanded", "false");
        await expect(page.locator("#zoom")).toBeHidden();
    });

    test("the zoom selector shows only the current percentage while keeping its options and name", async ({ page }) => {
        await open(page);
        await page.locator("#tools-toggle").tap();
        const select = page.getByRole("combobox", { name: "Zoom level" });
        const value = page.locator("#zoom-value");
        const percentage = () => page.evaluate(() => `${Math.round(window.viewer().geometry.scale * 100)}%`);
        await expect(select).toBeVisible();
        await expect(select).toHaveCSS("color", "rgba(0, 0, 0, 0)");
        await expect(value).toBeVisible();
        await expect(value).toHaveAttribute("aria-hidden", "true");
        await expect(value).toHaveText(await percentage());
        await expect(select.locator('option[value="fit"]')).toHaveText(`Fit image (${await percentage()})`);
        expect(
            await select.locator('option[value="fit"]').evaluate((option) => getComputedStyle(option).color),
        ).not.toBe("rgba(0, 0, 0, 0)");
        const selectBox = await select.boundingBox();
        const valueBox = await value.boundingBox();
        expect(valueBox.x).toBeGreaterThanOrEqual(selectBox.x);
        expect(valueBox.x + valueBox.width).toBeLessThanOrEqual(selectBox.x + selectBox.width);
        expect(valueBox.y).toBeGreaterThanOrEqual(selectBox.y);
        expect(valueBox.y + valueBox.height).toBeLessThanOrEqual(selectBox.y + selectBox.height);
        await select.selectOption("2");
        await expect(value).toHaveText("200%");
    });

    test("a tap toggles the controls without refitting the image, a double tap zooms instead", async ({ page }) => {
        await open(page);
        const geometry = () => page.evaluate(() => ({ ...window.viewer().geometry }));
        const fitted = await geometry();
        await page.locator("#tools-toggle").tap();
        await tap(page);
        await expect(dialog(page)).toHaveAttribute("data-controls", "hidden");
        await expect(page.locator("#tools-toggle")).toHaveAttribute("aria-expanded", "false");
        for (const selector of [
            '[data-slot="image-viewer-toolbar"]',
            '[data-slot="image-viewer-arrow"][data-side="next"]',
            '[data-slot="image-viewer-caption"]',
            '[data-slot="image-viewer-thumbnails"]',
        ]) {
            await expect(page.locator(selector)).toHaveCSS("opacity", "0");
            await expect(page.locator(selector)).toHaveCSS("pointer-events", "none");
        }
        expect(await geometry()).toEqual(fitted);
        // A tap where the hidden arrow sits reaches the image and restores the controls rather than navigating.
        await tap(page, page.locator("#next"));
        await expect(dialog(page)).not.toHaveAttribute("data-controls");
        await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 3");
        expect(await geometry()).toEqual(fitted);

        const point = await center(viewport(page));
        await page.touchscreen.tap(point.x, point.y);
        await page.touchscreen.tap(point.x, point.y);
        await expect.poll(() => page.evaluate(() => window.viewer().atFit)).toBe(false);
        const zoomed = (await geometry()).scale;
        await page.waitForTimeout(400);
        await expect(dialog(page)).not.toHaveAttribute("data-controls");
        expect((await geometry()).scale).toBe(zoomed);
        await page.touchscreen.tap(point.x, point.y);
        await page.touchscreen.tap(point.x, point.y);
        await expect.poll(() => page.evaluate(() => window.viewer().atFit)).toBe(true);
        await page.waitForTimeout(400);
        await expect(dialog(page)).not.toHaveAttribute("data-controls");
    });

    test("swipes, drags and pinches never toggle the controls", async ({ page, context }) => {
        await open(page);
        const session = await context.newCDPSession(page);
        const { x, y } = await center(viewport(page));
        await session.send("Input.dispatchTouchEvent", { type: "touchStart", touchPoints: [{ x, y, id: 1 }] });
        await session.send("Input.dispatchTouchEvent", { type: "touchMove", touchPoints: [{ x: x - 200, y, id: 1 }] });
        await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [] });
        await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 3");
        await expect(displayed(page)).toBeVisible();
        await session.send("Input.dispatchTouchEvent", {
            type: "touchStart",
            touchPoints: [
                { x: x - 20, y, id: 1 },
                { x: x + 20, y, id: 2 },
            ],
        });
        await session.send("Input.dispatchTouchEvent", {
            type: "touchMove",
            touchPoints: [
                { x: x - 22, y, id: 1 },
                { x: x + 22, y, id: 2 },
            ],
        });
        await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [] });
        await session.send("Input.dispatchTouchEvent", { type: "touchStart", touchPoints: [{ x, y, id: 1 }] });
        await session.send("Input.dispatchTouchEvent", { type: "touchMove", touchPoints: [{ x, y: y + 40, id: 1 }] });
        await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [] });
        await page.waitForTimeout(400);
        await expect(dialog(page)).not.toHaveAttribute("data-controls");
    });

    test("hidden controls return on focus, while loading or failing, and on every opening", async ({ page }) => {
        await page.route("https://gallery.test/broken.svg", (route) => route.fulfill({ status: 404, body: "" }));
        await open(page);
        await page.locator("#next").tap();
        await expect(page.locator("#next")).toBeFocused();
        await tap(page);
        await expect(dialog(page)).toHaveAttribute("data-controls", "hidden");
        // Focus must not stay on a control the person can no longer see.
        await expect(dialog(page)).toBeFocused();
        await page.keyboard.press("Tab");
        await expect(dialog(page)).not.toHaveAttribute("data-controls");

        await page.locator("#three img").evaluate((node) => (node.dataset.original = "/broken.svg"));
        await page.locator("#next").tap();
        await expect(page.locator('[data-image-viewer-target="error"]')).toBeVisible();
        await tap(page);
        await expect(dialog(page)).toHaveAttribute("data-controls", "hidden");
        await tap(page);
        await expect(dialog(page)).not.toHaveAttribute("data-controls");

        await page.locator("#tools-toggle").tap();
        await tap(page);
        await expect(dialog(page)).toHaveAttribute("data-controls", "hidden");
        await page.keyboard.press("Escape");
        await expect(overlay(page)).toBeHidden();
        await page.locator("#one img").tap();
        await expect(displayed(page)).toBeVisible();
        await expect(dialog(page)).not.toHaveAttribute("data-controls");
        await expect(page.locator("#tools-toggle")).toHaveAttribute("aria-expanded", "false");
    });
});

test("a mouse click on the image keeps the controls and the tools inline", async ({ page }) => {
    await start(page, item("one") + item("two"), { tools: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.evaluate(() => window.viewer().overlay.settled);
    await expect(page.locator("#tools-toggle")).toBeHidden();
    await expect(page.locator("#zoom")).toBeVisible();
    await expect(page.locator("#reset")).toBeVisible();
    await expect(page.locator("#zoom-value")).toBeHidden();
    await expect(page.getByRole("combobox", { name: "Zoom level" })).not.toHaveCSS("color", "rgba(0, 0, 0, 0)");
    await page.locator('[data-image-viewer-target="viewport"]').click();
    await page.waitForTimeout(400);
    await expect(page.locator('[data-image-viewer-target="dialog"]')).not.toHaveAttribute("data-controls");
    await page.locator('[data-image-viewer-target="viewport"]').dblclick();
    await expect.poll(() => page.evaluate(() => window.viewer().atFit)).toBe(false);
});

for (const direction of ["ltr", "rtl"]) {
    test(`side arrows navigate without capturing image gestures and caption precedes thumbnails in ${direction}`, async ({
        page,
    }) => {
        await start(page, item("one") + item("two"), { arrows: true, thumbnails: true });
        await page.addStyleTag({ content: '[data-slot="image-viewer-arrow"] { --image-viewer-arrow-inset: 12px; }' });
        await page.locator("#gallery").evaluate((root, dir) => (root.dir = dir), direction);
        await page.locator("#one").click();
        await expect(displayed(page)).toBeVisible();
        const prev = page.locator("#prev");
        const next = page.locator("#next");
        await expect(prev).toBeDisabled();
        expect(await next.evaluate((node) => node.closest('[data-image-viewer-target="viewport"]') === null)).toBe(
            true,
        );
        const viewport = await page.locator('[data-image-viewer-target="viewport"]').boundingBox();
        const arrow = await next.boundingBox();
        expect(arrow.y + arrow.height / 2).toBeCloseTo(viewport.y + viewport.height / 2, 1);
        if (direction === "ltr") expect(arrow.x + arrow.width).toBeCloseTo(viewport.x + viewport.width - 12, 1);
        else expect(arrow.x).toBeCloseTo(viewport.x + 12, 1);
        await next.click();
        await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
        await expect(prev).toBeFocused();
        expect(await page.evaluate(() => window.viewer().pointers.size)).toBe(0);
        await page.locator("#gallery").evaluate((root) => (root.dataset.imageViewerLoopValue = "true"));
        await expect(next).toBeEnabled();
        await next.click();
        await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 2");
        const caption = await page.locator('[data-image-viewer-target="caption"]').boundingBox();
        const thumbnails = await page.locator('[data-image-viewer-target="thumbnails"]').boundingBox();
        expect(caption.y + caption.height).toBeLessThanOrEqual(thumbnails.y);
    });
}

async function motionFixture(page) {
    await page.emulateMedia({ reducedMotion: "no-preference" });
    await start(page, undefined, { rotate: true, flip: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.evaluate(() => window.viewer().overlay.settled);
    await page.addStyleTag({
        content:
            '[data-slot="image-viewer-image"] { --image-viewer-transform-duration: 1s; --image-viewer-fade-duration: 1s; }',
    });
}

for (const operation of ["zoomIn", "rotateRight", "flipHorizontal", "reset"]) {
    test(`${operation} preserves displayed opacity when interrupting an image fade`, async ({ page }) => {
        await motionFixture(page);
        await displayed(page).evaluate((img) => (img.style.opacity = "0.4"));
        await page.locator("#next").click();
        await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
        const result = await displayed(page).evaluate((img, action) => {
            const fade = img.getAnimations()[0];
            fade.pause();
            fade.currentTime = 300;
            const before = Number(getComputedStyle(img).opacity);
            window.viewer()[action]();
            const animation = img.getAnimations()[0];
            if (!animation) return { before, after: Number(getComputedStyle(img).opacity), end: null };
            animation.pause();
            animation.currentTime = 0;
            return {
                before,
                after: Number(getComputedStyle(img).opacity),
                end: animation.effect.getKeyframes().at(-1).opacity,
            };
        }, operation);
        expect(result.before).toBeGreaterThan(0);
        expect(result.before).toBeLessThan(0.4);
        expect(result.after).toBeCloseTo(result.before, 6);
        expect(result.end).toBe("0.4");
    });
}

test("discrete motion rotates across zero in the requested direction and retargets from the displayed pose", async ({
    page,
}) => {
    await motionFixture(page);
    // Reach 270° without motion, then enable it for the wraparound.
    await page.locator('[data-image-viewer-target="overlay"]').evaluate((node) => (node.dataset.motion = "none"));
    for (let i = 0; i < 3; i++) await page.locator("#rotate-right").click();
    await page.locator("#flip-horizontal").click();
    await page.locator('[data-image-viewer-target="overlay"]').evaluate((node) => delete node.dataset.motion);
    await page.locator("#rotate-right").click();
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
    const result = await displayed(page).evaluate((img) => {
        const animation = img.getAnimations()[0];
        animation.pause();
        animation.currentTime = 500;
        const previous = getComputedStyle(img).transform;
        const keyframes = animation.effect.getKeyframes();
        window.viewer().rotateRight();
        const replacement = img.getAnimations()[0];
        replacement.pause();
        replacement.currentTime = 0;
        return {
            previous,
            current: getComputedStyle(img).transform,
            end: keyframes.at(-1).transform,
            count: img.getAnimations().length,
        };
    });
    expect(result.end).toContain("rotate(360deg)");
    expect(result.current).toBe(result.previous);
    expect(result.count).toBe(1);
    await displayed(page).evaluate((img) => img.getAnimations()[0].finish());
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(0);
    const state = await page.evaluate(() => window.viewer().geometry);
    const box = await displayed(page).boundingBox();
    expect(box.width).toBeCloseTo(state.naturalHeight * state.scale, 1);
    expect(box.height).toBeCloseTo(state.naturalWidth * state.scale, 1);
});

test("direct wheel input finishes discrete motion immediately and morph cancels presentation effects", async ({
    page,
}) => {
    await motionFixture(page);
    await page.locator("#zoom").click();
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
    const viewport = await page.locator('[data-image-viewer-target="viewport"]').boundingBox();
    await page.mouse.move(viewport.x + viewport.width / 2, viewport.y + viewport.height / 2);
    await page.mouse.wheel(0, -30);
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(0);
    await page.locator("#rotate-left").click();
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
    const geometry = await page.evaluate(() => ({ ...window.viewer().geometry }));
    await page.evaluate(
        (html) => {
            const template = document.createElement("template");
            template.innerHTML = html;
            window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
        },
        markup(undefined, false, "default", { rotate: true, flip: true }),
    );
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(0);
    expect(await page.evaluate(() => window.viewer().geometry)).toEqual(geometry);
});

for (const preference of ["reduce", "none"]) {
    test(`image motion cancels when ${preference} is enabled and stays off for new actions`, async ({ page }) => {
        await motionFixture(page);
        await page.locator("#zoom").click();
        await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
        if (preference === "reduce") await page.emulateMedia({ reducedMotion: "reduce" });
        else
            await page
                .locator('[data-image-viewer-target="overlay"]')
                .evaluate((node) => (node.dataset.motion = "none"));
        await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(0);
        await page.locator("#rotate-right").click();
        await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(0);
    });
}

test("navigation fades only the current successful load and cleanup retires its effects", async ({ page }) => {
    await motionFixture(page);
    await displayed(page).evaluate((img) => (img.style.opacity = "0.4"));
    await page.locator("#next").click();
    await expect(displayed(page)).toHaveAttribute("src", image(800, 1200, "blue"));
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
    expect(await displayed(page).evaluate((img) => img.getAnimations()[0].effect.getKeyframes()[0].opacity)).toBe("0");
    expect(await displayed(page).evaluate((img) => img.getAnimations()[0].effect.getKeyframes().at(-1).opacity)).toBe(
        "0.4",
    );
    await page.locator("#prev").click();
    await expect(displayed(page)).toHaveAttribute("src", image());
    await expect.poll(() => displayed(page).evaluate((img) => img.getAnimations().length)).toBe(1);
    await page.evaluate(() => {
        window.retiredImage = document.querySelector('[data-image-viewer-target="image"]');
        document.dispatchEvent(new Event("turbo:before-cache"));
    });
    expect(await page.evaluate(() => window.retiredImage.getAnimations().length)).toBe(0);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    await page.evaluate(() => document.querySelector("#gallery").remove());
    expect(await page.evaluate(() => window.retiredImage.getAnimations().length)).toBe(0);
});

test("rotation and screen-axis flips render the fitted bitmap without reloading the original", async ({ page }) => {
    await start(page, item("one", image(1600, 800)) + item("two"), { rotate: true, flip: true });
    for (const id of ["rotate-left", "rotate-right", "flip-horizontal", "flip-vertical"]) {
        await expect(page.locator(`#${id}`)).toBeDisabled();
    }
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.evaluate(() => {
        window.orientationChanges = 0;
        document.querySelector("#gallery").addEventListener("image-viewer:changed", () => window.orientationChanges++);
    });
    await page.locator("#rotate-right").click();
    const state = await page.evaluate(() => window.viewer().geometry);
    expect(state.rotation).toBe(90);
    expect(state.scale).toBe(Math.min(1, state.viewportWidth / 800, state.viewportHeight / 1600));
    const box = await displayed(page).boundingBox();
    expect(box.width).toBeCloseTo(800 * state.scale, 1);
    expect(box.height).toBeCloseTo(1600 * state.scale, 1);
    await page.locator("#flip-horizontal").click();
    await expect(page.locator("#flip-horizontal")).not.toHaveAttribute("aria-pressed");
    const matrix = await displayed(page).evaluate((img) => {
        const matrix = new DOMMatrix(getComputedStyle(img).transform);
        return { a: matrix.a, b: matrix.b, c: matrix.c, d: matrix.d };
    });
    expect(matrix.a).toBeCloseTo(0);
    expect(matrix.b).toBeCloseTo(1);
    expect(matrix.c).toBeCloseTo(1);
    expect(matrix.d).toBeCloseTo(0);
    await page.locator("#rotate-right").click();
    await expect(page.locator("#flip-horizontal")).not.toHaveAttribute("aria-pressed");
    await expect(page.locator("#flip-vertical")).not.toHaveAttribute("aria-pressed");
    await expect(page.locator("#rotate-right")).toBeFocused();
    expect(await page.evaluate(() => window.orientationChanges)).toBe(0);
    await expect(page.locator('[data-image-viewer-target="download"]')).toHaveAttribute("href", image(1600, 800));
});

test("equivalent double flips and a half turn expose momentary actions, not conflicting toggle states", async ({
    page,
}) => {
    await start(page, item("one"), { rotate: true, flip: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#flip-horizontal").click();
    await page.locator("#flip-vertical").click();
    const readMatrix = (img) => {
        const matrix = new DOMMatrix(getComputedStyle(img).transform);
        return [matrix.a, matrix.b, matrix.c, matrix.d, matrix.e, matrix.f];
    };
    const flipped = await displayed(page).evaluate(readMatrix);
    await page.keyboard.press("Escape");
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#rotate-right").click();
    await page.locator("#rotate-right").click();
    const rotated = await displayed(page).evaluate(readMatrix);
    for (let index = 0; index < 6; index++) expect(rotated[index]).toBeCloseTo(flipped[index], 5);
    await expect(page.locator("#flip-horizontal")).not.toHaveAttribute("aria-pressed");
    await expect(page.locator("#flip-vertical")).not.toHaveAttribute("aria-pressed");
});

test("fit preserves rotation and flips while navigation and reopening reset them", async ({ page }) => {
    await start(page, undefined, { rotate: true, flip: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#rotate-left").click();
    await page.locator("#flip-vertical").click();
    await page.locator("#zoom").click();
    await page.locator("#reset").click();
    expect(await page.evaluate(() => window.viewer().geometry)).toMatchObject({
        rotation: 270,
        flipX: false,
        flipY: true,
    });
    expect(await page.evaluate(() => window.viewer().atFit)).toBe(true);
    await page.locator("#next").click();
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer().geometry)).toMatchObject({
        rotation: 0,
        flipX: false,
        flipY: false,
    });
    await page.locator("#flip-horizontal").click();
    await page.keyboard.press("Escape");
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await expect(page.locator("#flip-horizontal")).not.toHaveAttribute("aria-pressed");
    expect(await page.evaluate(() => window.viewer().geometry.rotation)).toBe(0);
});

test("transformed geometry and momentary controls survive morphs, shell replacement and snapshot normalization", async ({
    page,
}) => {
    const sources = item("one") + item("two");
    await start(page, sources, { rotate: true, flip: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#rotate-right").click();
    await page.locator("#flip-horizontal").click();
    await page.getByRole("combobox", { name: "Zoom level" }).selectOption("2");
    const geometry = await page.evaluate(() => ({ ...window.viewer().geometry }));
    await page.evaluate(
        (html) => {
            const template = document.createElement("template");
            template.innerHTML = html;
            window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
        },
        markup(sources, false, "default", { rotate: true, flip: true }),
    );
    await expect(page.locator("#flip-horizontal")).not.toHaveAttribute("aria-pressed");
    expect(await page.evaluate(() => window.viewer().geometry)).toEqual(geometry);
    const box = await displayed(page).boundingBox();
    expect(box.width).toBeCloseTo(geometry.naturalHeight * geometry.scale, 1);
    expect(box.height).toBeCloseTo(geometry.naturalWidth * geometry.scale, 1);
    await page
        .locator('[data-image-viewer-target="overlay"]')
        .evaluate((shell) => shell.replaceWith(shell.cloneNode(true)));
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer().geometry)).toEqual(geometry);
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            const img = copy.querySelector('[data-image-viewer-target="image"]');
            return {
                angle: img.style.getPropertyValue("--image-viewer-rotation"),
                flip: img.style.getPropertyValue("--image-viewer-flip-x"),
                pressed: copy.querySelector("#flip-horizontal").getAttribute("aria-pressed"),
            };
        }),
    ).toEqual({ angle: "", flip: "", pressed: null });
});

test("wheel, drag, keyboard and pinch use the oriented bounds without reversing screen-space pan", async ({
    page,
    context,
}) => {
    await start(page, undefined, { rotate: true, flip: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#rotate-right").click();
    await page.locator("#flip-horizontal").click();
    await page.getByRole("combobox", { name: "Zoom level" }).selectOption("1");
    const viewport = page.locator('[data-image-viewer-target="viewport"]');
    const box = await viewport.boundingBox();
    const center = { x: box.x + box.width / 2, y: box.y + box.height / 2 };
    await page.mouse.move(center.x, center.y);
    await page.mouse.wheel(0, -100);
    await expect.poll(() => page.evaluate(() => window.viewer().geometry.scale)).toBeGreaterThan(1);
    const before = await page.evaluate(() => ({ ...window.viewer().geometry }));
    await page.mouse.down();
    await page.mouse.move(center.x + 30, center.y + 20, { steps: 3 });
    await page.mouse.up();
    const dragged = await page.evaluate(() => window.viewer().geometry);
    expect(dragged.x).toBeCloseTo(before.x + 30, 1);
    expect(dragged.y).toBeCloseTo(before.y + 20, 1);
    await viewport.focus();
    await page.keyboard.press("ArrowLeft");
    expect(await page.evaluate(() => window.viewer().geometry.x)).toBeCloseTo(dragged.x - 40, 1);
    await page.keyboard.press("0");
    const fitScale = await page.evaluate(() => window.viewer().geometry.scale);
    const session = await context.newCDPSession(page);
    await session.send("Input.dispatchTouchEvent", {
        type: "touchStart",
        touchPoints: [
            { x: center.x - 50, y: center.y, id: 1 },
            { x: center.x + 50, y: center.y, id: 2 },
        ],
    });
    await session.send("Input.dispatchTouchEvent", {
        type: "touchMove",
        touchPoints: [
            { x: center.x - 100, y: center.y, id: 1 },
            { x: center.x + 100, y: center.y, id: 2 },
        ],
    });
    await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [] });
    const pinched = await page.evaluate(() => window.viewer().geometry);
    expect(pinched.scale).toBeGreaterThan(fitScale);
    expect(pinched).toMatchObject({ rotation: 90, flipX: true, flipY: false });
    const rendered = await displayed(page).boundingBox();
    expect(rendered.x - box.x).toBeCloseTo(pinched.x, 1);
    expect(rendered.y - box.y).toBeCloseTo(pinched.y, 1);
    expect(rendered.width).toBeCloseTo(pinched.naturalHeight * pinched.scale, 1);
    expect(rendered.height).toBeCloseTo(pinched.naturalWidth * pinched.scale, 1);
});

test("rotation and flip controls disable during a replacement load and after failure", async ({ page }) => {
    let release;
    await page.route("https://gallery.test/transform-error.svg", async (route) => {
        await new Promise((resolve) => (release = resolve));
        await route.abort("failed");
    });
    await start(page, item("one"), { rotate: true, flip: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#flip-horizontal").click();
    await page.locator("#one img").evaluate((img) => (img.dataset.original = "/transform-error.svg"));
    await expect.poll(() => Boolean(release)).toBe(true);
    for (const id of ["rotate-left", "rotate-right", "flip-horizontal", "flip-vertical"]) {
        await expect(page.locator(`#${id}`)).toBeDisabled();
    }
    await expect(page.locator("#flip-horizontal")).not.toHaveAttribute("aria-pressed");
    release();
    await expect(page.locator('[data-image-viewer-target="error"]')).toBeVisible();
    for (const id of ["rotate-left", "rotate-right", "flip-horizontal", "flip-vertical"]) {
        await expect(page.locator(`#${id}`)).toBeDisabled();
    }
    await page.evaluate(() => {
        window.viewer().rotateLeft();
        window.viewer().flipVertical();
    });
    await expect(displayed(page)).toBeHidden();
});

test("full size fills the viewport without a preset and retains zoom through resizing and morphs", async ({ page }) => {
    await start(page, item("one") + item("two"), { size: "full", thumbnails: true });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const dialog = page.locator('[data-image-viewer-target="dialog"]');
    await expect.poll(() => dialog.boundingBox()).toEqual({ x: 0, y: 0, ...page.viewportSize() });
    await page.getByRole("combobox", { name: "Zoom level" }).selectOption("1");
    await page.setViewportSize({ width: 390, height: 844 });
    await expect.poll(() => dialog.boundingBox()).toEqual({ x: 0, y: 0, width: 390, height: 844 });
    await page.evaluate(
        (html) => {
            const template = document.createElement("template");
            template.innerHTML = html;
            window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
        },
        markup(item("one") + item("two"), true, "full"),
    );
    await expect.poll(() => dialog.boundingBox()).toEqual({ x: 0, y: 0, width: 390, height: 844 });
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(1);
    expect(await page.evaluate(() => document.fullscreenElement)).toBeNull();
    await page.keyboard.press("Escape");
    await expect(overlay(page)).toBeHidden();
    await expect(page.locator("#one")).toBeFocused();
});

for (const direction of ["ltr", "rtl"]) {
    test(`full size keeps content within physical safe areas in ${direction}`, async ({ page, context }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await start(page, item("one") + item("two"), { size: "full", thumbnails: true });
        await page.evaluate((dir) => {
            document.documentElement.dir = dir;
            const viewport = document.createElement("meta");
            viewport.name = "viewport";
            viewport.content = "width=device-width, initial-scale=1, viewport-fit=cover";
            document.head.append(viewport);
        }, direction);
        const session = await context.newCDPSession(page);
        await session.send("Emulation.setSafeAreaInsetsOverride", {
            insets: { top: 32, right: 24, bottom: 20, left: 12 },
        });
        await page.locator("#one").click();
        await expect(displayed(page)).toBeVisible();
        const dialog = page.locator('[data-image-viewer-target="dialog"]');
        await expect.poll(() => dialog.boundingBox()).toEqual({ x: 0, y: 0, width: 390, height: 844 });
        const toolbar = await page.locator('[data-slot="image-viewer-toolbar"]').boundingBox();
        const strip = await page.locator('[data-image-viewer-target="thumbnails"]').boundingBox();
        const caption = await page.locator('[data-image-viewer-target="caption"]').boundingBox();
        expect(toolbar.y).toBeGreaterThanOrEqual(32);
        for (const box of [toolbar, strip, caption]) {
            expect(box.x).toBeGreaterThanOrEqual(12);
            expect(box.x + box.width).toBeLessThanOrEqual(390 - 24);
            expect(box.y + box.height).toBeLessThanOrEqual(844 - 20);
        }
        expect(await page.evaluate(() => window.viewer().geometry.viewportWidth)).toBe(390 - 12 - 24);
    });
}

test("thumbnail selection follows clicks, toolbar navigation and native keyboard focus", async ({ page }) => {
    await start(page, item("one") + item("two") + item("three"), { thumbnails: true });
    await page.locator("#one img").evaluate((img) => {
        img.referrerPolicy = "no-referrer";
        img.crossOrigin = "anonymous";
    });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const thumbnails = page.locator('[data-slot="image-viewer-thumbnail"]');
    await expect(thumbnails).toHaveCount(3);
    await expect(thumbnails.nth(0)).toHaveAttribute("aria-current", "true");
    await expect(thumbnails.nth(0)).toHaveAccessibleName("View image 1 of 3 — one");
    await expect(thumbnails.nth(0).locator("img")).toHaveAttribute("src", image(80, 60));
    await expect(thumbnails.nth(0).locator("img")).toHaveAttribute("referrerpolicy", "no-referrer");
    await expect(thumbnails.nth(0).locator("img")).toHaveAttribute("crossorigin", "anonymous");
    await thumbnails.nth(2).click();
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("3 / 3");
    await expect(thumbnails.nth(2)).toHaveAttribute("aria-current", "true");
    await thumbnails.nth(2).press("Home");
    await expect(thumbnails.nth(0)).toBeFocused();
    await thumbnails.nth(0).press("ArrowRight");
    await expect(thumbnails.nth(1)).toBeFocused();
    await expect(thumbnails.nth(1)).toHaveAttribute("aria-current", "true");
    await page.locator("#next").click();
    await expect(thumbnails.nth(2)).toHaveAttribute("aria-current", "true");
});

test("loop wraps toolbar and thumbnail keyboard navigation in both directions", async ({ page }) => {
    await start(page, item("one") + item("two") + item("three"), { thumbnails: true });
    await page.locator("#gallery").evaluate((root) => root.setAttribute("data-image-viewer-loop-value", "true"));
    await page.locator("#three").click();
    await expect(displayed(page)).toBeVisible();
    await expect(page.locator("#next")).toBeEnabled();
    await page.locator("#next").click();
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 3");
    await expect(page.locator("#prev")).toBeEnabled();
    await page.locator("#prev").click();
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("3 / 3");
    const thumbnails = page.locator('[data-slot="image-viewer-thumbnail"]');
    await thumbnails.nth(2).focus();
    await thumbnails.nth(2).press("ArrowRight");
    await expect(thumbnails.nth(0)).toBeFocused();
    await expect(thumbnails.nth(0)).toHaveAttribute("aria-current", "true");
    await thumbnails.nth(0).press("ArrowLeft");
    await expect(thumbnails.nth(2)).toBeFocused();
    await expect(thumbnails.nth(2)).toHaveAttribute("aria-current", "true");
});

test("navigation to the current image preserves zoom and emits no extra change, including a one-image swipe", async ({
    page,
    context,
}) => {
    await start(page, item("one"));
    await page.locator("#gallery").evaluate((root) => root.setAttribute("data-image-viewer-loop-value", "true"));
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    const geometry = await page.evaluate(() => {
        window.noopChanges = 0;
        document.querySelector("#gallery").addEventListener("image-viewer:changed", () => window.noopChanges++);
        return { ...window.viewer().geometry };
    });
    await page.locator("#close").focus();
    for (const key of ["ArrowRight", "ArrowLeft", "Home", "End"]) await page.keyboard.press(key);
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer().geometry)).toEqual(geometry);
    expect(await page.evaluate(() => window.noopChanges)).toBe(0);

    await page.locator("#reset").click();
    const viewport = await page.locator('[data-image-viewer-target="viewport"]').boundingBox();
    const x = viewport.x + viewport.width / 2;
    const y = viewport.y + viewport.height / 2;
    const session = await context.newCDPSession(page);
    await session.send("Input.dispatchTouchEvent", { type: "touchStart", touchPoints: [{ x, y, id: 1 }] });
    await session.send("Input.dispatchTouchEvent", { type: "touchMove", touchPoints: [{ x: x - 200, y, id: 1 }] });
    await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [] });
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.noopChanges)).toBe(0);
});

test("Home and End do not reload images already selected at the gallery boundaries", async ({ page }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    const firstScale = await page.evaluate(() => window.viewer().geometry.scale);
    await page.locator("#close").focus();
    await page.keyboard.press("Home");
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(firstScale);
    await page.keyboard.press("End");
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    const lastScale = await page.evaluate(() => window.viewer().geometry.scale);
    await page.locator("#close").focus();
    await page.keyboard.press("End");
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(lastScale);
});

test("the displayed image shares loader request policies and snapshots restore the authored defaults", async ({
    page,
}) => {
    const origins = [];
    await page.route("https://images.test/credentials.svg", async (route) => {
        const headers = await route.request().allHeaders();
        origins.push(headers.origin);
        await route.fulfill({
            contentType: "image/svg+xml",
            headers: {
                "Access-Control-Allow-Origin": headers.origin || "null",
                "Access-Control-Allow-Credentials": "true",
            },
            body: '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1200"/>',
        });
    });
    await start(page, item("one", "https://images.test/credentials.svg") + item("two"));
    await page.locator("#one img").evaluate((img) => {
        img.crossOrigin = "use-credentials";
        img.referrerPolicy = "no-referrer";
    });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await expect(displayed(page)).toHaveAttribute("crossorigin", "use-credentials");
    await expect(displayed(page)).toHaveAttribute("referrerpolicy", "no-referrer");
    await page.waitForFunction(() => document.querySelector('[data-image-viewer-target="image"]').naturalWidth > 0);
    expect(origins.length).toBeGreaterThan(0);
    // The setContent fixture has an opaque origin; CORS image requests carry the literal Origin: null.
    expect(origins).toEqual(Array(origins.length).fill("null"));
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            const image = copy.querySelector('[data-image-viewer-target="image"]');
            return {
                crossorigin: image.hasAttribute("crossorigin"),
                referrerpolicy: image.hasAttribute("referrerpolicy"),
            };
        }),
    ).toEqual({ crossorigin: false, referrerpolicy: false });
    await page.locator("#next").click();
    await expect(displayed(page)).toHaveAttribute("src", image());
    await expect(displayed(page)).not.toHaveAttribute("crossorigin");
    expect(await displayed(page).evaluate((img) => img.referrerPolicy)).toBe("");
});

test("an enclosing link href change updates the fallback original without morphing", async ({ page }) => {
    await start(page, `<a id="one" href="${image()}"><img src="${image(80, 60)}" data-image-key="one" alt="one"></a>`);
    await page.locator("#one").click();
    await expect(displayed(page)).toHaveAttribute("src", image());
    const original = image(2000, 1000, "red");
    await page.locator("#one").evaluate((link, url) => (link.href = url), original);
    await expect(displayed(page)).toHaveAttribute("src", original);
    await expect(page.locator('[data-image-viewer-target="download"]')).toHaveAttribute("href", original);
});

test("keyed thumbnail buttons survive source changes and remain safe to restore from cache", async ({ page }) => {
    await start(page, item("one") + item("two") + item("three"), { thumbnails: true });
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator('[data-slot="image-viewer-thumbnail"]').nth(1).focus();
    await page.evaluate(() => {
        window.retainedThumbnail = document.querySelectorAll('[data-slot="image-viewer-thumbnail"]')[1];
        document.querySelector("#sources").prepend(document.querySelector("#two"));
    });
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(0)).toBeFocused();
    expect(
        await page.evaluate(
            () => document.querySelector('[data-slot="image-viewer-thumbnail"]') === window.retainedThumbnail,
        ),
    ).toBe(true);
    await page.locator("#two img").evaluate((img) => {
        img.src = "javascript:alert(1)";
        img.alt = "Changed";
    });
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(0)).toHaveAccessibleName(
        "View image 1 of 3 — Changed",
    );
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(0).locator("img")).not.toHaveAttribute("src");
    await page.locator("#two").evaluate((node) => node.remove());
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]')).toHaveCount(2);
    await expect(page.locator('[data-slot="image-viewer-thumbnail"][aria-current="true"]')).toBeFocused();
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            return {
                thumbnails: copy.querySelectorAll('[data-slot="image-viewer-thumbnail"]').length,
                sources: copy.querySelector("#sources").querySelectorAll("img").length,
                template: !!copy.querySelector('[data-image-viewer-target="thumbnailTemplate"]'),
            };
        }),
    ).toEqual({ thumbnails: 0, sources: 2, template: true });
    await page.locator("#sources").evaluate((node) => node.replaceChildren());
    await expect(overlay(page)).toBeHidden();
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]')).toHaveCount(0);
    await page.locator("#sources").evaluate((node, html) => (node.innerHTML = html), item("restored"));
    await page.locator("#restored").click();
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]')).toHaveCount(1);
});

test("a pending original load reports the current selection after source reordering and text changes", async ({
    page,
}) => {
    let release;
    let first = true;
    await page.route("https://gallery.test/pending.svg", async (route) => {
        if (first) {
            first = false;
            await new Promise((resolve) => (release = resolve));
        }
        await route.fulfill({
            contentType: "image/svg+xml",
            body: '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1200"/>',
        });
    });
    await start(page, item("one", "/pending.svg") + item("two"), { thumbnails: true });
    await page.evaluate(() => {
        document.querySelector("#gallery").addEventListener(
            "image-viewer:changed",
            (event) =>
                (window.changedImage = {
                    index: event.detail.index,
                    alt: event.detail.image.alt,
                }),
        );
    });
    await page.locator("#one").click();
    await expect.poll(() => Boolean(release)).toBe(true);
    await page.evaluate(() => {
        document.querySelector("#sources").prepend(document.querySelector("#two"));
        document.querySelector("#one img").alt = "Updated description";
    });
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
    release();
    await expect(displayed(page)).toHaveAttribute("alt", "Updated description");
    await expect.poll(() => page.evaluate(() => window.changedImage)).toEqual({ index: 1, alt: "Updated description" });
});

test("thumbnail presentation recovers after a full morph and a normalized cache reconnection", async ({ page }) => {
    const sources = item("one") + item("two") + item("three");
    await start(page, sources, { thumbnails: true });
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator('[data-slot="image-viewer-thumbnail"]').nth(1).focus();
    await page.evaluate(() => {
        window.focusedThumbnail = document.activeElement;
    });
    await page.evaluate(
        (html) => {
            const template = document.createElement("template");
            template.innerHTML = html;
            window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
        },
        markup(sources, true),
    );
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]')).toHaveCount(3);
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(1)).toHaveAttribute("aria-current", "true");
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(1)).toBeFocused();
    expect(await page.evaluate(() => document.activeElement === window.focusedThumbnail)).toBe(true);
    await page.evaluate(() => {
        const root = document.querySelector("#gallery");
        const copy = root.cloneNode(true);
        window.normalizeSnapshot(copy);
        root.replaceWith(copy);
    });
    await page.waitForFunction(() => window.viewer()?.connected);
    await expect(overlay(page)).toBeHidden();
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]')).toHaveCount(3);
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(1)).toHaveAttribute("aria-current", "true");
});

test("replacing the thumbnail template retains the focused image while applying new markup", async ({ page }) => {
    await start(page, item("one") + item("two"), { thumbnails: true });
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator('[data-slot="image-viewer-thumbnail"]').nth(1).focus();
    await page.locator('[data-image-viewer-target="thumbnailTemplate"]').evaluate((template) => {
        const replacement = template.cloneNode(true);
        replacement.content.querySelector("button").dataset.label = "Photo :index of :total";
        template.replaceWith(replacement);
    });
    const selected = page.locator('[data-slot="image-viewer-thumbnail"]').nth(1);
    await expect(selected).toHaveAccessibleName("Photo 2 of 2 — two");
    await expect(selected).toBeFocused();
});

test("a morph replacing the viewer shell restores focus to the same keyed thumbnail", async ({ page }) => {
    const sources = item("one") + item("two");
    await start(page, sources, { thumbnails: true });
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator('[data-slot="image-viewer-thumbnail"]').nth(1).focus();
    await page.evaluate(
        (html) => {
            const template = document.createElement("template");
            template.innerHTML = html;
            const old = template.content.querySelector('[data-image-viewer-target="overlay"]');
            const replacement = document.createElement("aside");
            for (const { name, value } of old.attributes) replacement.setAttribute(name, value);
            replacement.id = "replacement-shell";
            replacement.innerHTML = old.innerHTML;
            old.replaceWith(replacement);
            window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
        },
        markup(sources, true),
    );
    await expect(page.locator("#replacement-shell")).toBeVisible();
    await expect(page.locator('[data-slot="image-viewer-thumbnail"]').nth(1)).toBeFocused();
});

for (const thumbnails of [false, true]) {
    test(`responsive source loads keep the authored original and zoom with thumbnails ${thumbnails ? "on" : "off"}`, async ({
        page,
    }) => {
        await page.setViewportSize({ width: 600, height: 700 });
        await page.route("https://gallery.test/*.svg", (route) =>
            route.fulfill({
                contentType: "image/svg+xml",
                body: '<svg xmlns="http://www.w3.org/2000/svg" width="2400" height="1800"/>',
            }),
        );
        await start(
            page,
            '<img id="one" src="/fallback.svg" srcset="/small.svg 800w, /big.svg 2400w" sizes="100vw" alt="Responsive">',
            { thumbnails },
        );
        await expect
            .poll(() => page.locator("#one").evaluate((img) => img.currentSrc))
            .toBe("https://gallery.test/small.svg");
        await page.locator("#one").click();
        await expect(displayed(page)).toBeVisible();
        await expect(displayed(page)).toHaveAttribute("src", "https://gallery.test/fallback.svg");
        await page.getByRole("combobox", { name: "Zoom level" }).selectOption("2");
        await page.evaluate(() => {
            window.responsiveChanges = 0;
            document
                .querySelector("#gallery")
                .addEventListener("image-viewer:changed", () => window.responsiveChanges++);
        });
        await page.setViewportSize({ width: 1600, height: 900 });
        await expect
            .poll(() => page.locator("#one").evaluate((img) => img.currentSrc))
            .toBe("https://gallery.test/big.svg");
        if (thumbnails) {
            await expect(page.locator('[data-slot="image-viewer-thumbnail"] img')).toHaveAttribute(
                "src",
                "https://gallery.test/big.svg",
            );
        }
        await expect(displayed(page)).toHaveAttribute("src", "https://gallery.test/fallback.svg");
        await expect(page.locator('[data-image-viewer-target="download"]')).toHaveAttribute(
            "href",
            "https://gallery.test/fallback.svg",
        );
        expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(2);
        expect(await page.evaluate(() => window.responsiveChanges)).toBe(0);
    });
}

for (const thumbnails of [false, true]) {
    test(`source image loads do not rebuild the gallery with thumbnails ${thumbnails ? "on" : "off"}`, async ({
        page,
    }) => {
        await start(page, item("one") + item("two"), { thumbnails });
        expect(
            await page.evaluate(async () => {
                const viewer = window.viewer();
                let refreshes = 0;
                const refresh = viewer.refresh.bind(viewer);
                viewer.refresh = () => {
                    refreshes++;
                    return refresh();
                };
                for (const img of document.querySelectorAll("#sources img")) {
                    img.dispatchEvent(new Event("load"));
                    await Promise.resolve();
                }
                const plainLoads = refreshes;
                // A responsive load updates just its thumbnail, never the full gallery.
                const img = document.querySelector("#one img");
                img.setAttribute("srcset", img.src);
                await new Promise(requestAnimationFrame);
                refreshes = 0;
                img.dispatchEvent(new Event("load"));
                await Promise.resolve();
                viewer.disconnect();
                img.dispatchEvent(new Event("load"));
                await Promise.resolve();
                return { plainLoads, responsiveLoads: refreshes };
            }),
        ).toEqual({ plainLoads: 0, responsiveLoads: 0 });
    });
}

for (const direction of ["ltr", "rtl"]) {
    test(`the active thumbnail remains in view and arrow keys follow ${direction} layout`, async ({ page }) => {
        await start(page, Array.from({ length: 30 }, (_, index) => item(`photo-${index}`)).join(""), {
            thumbnails: true,
        });
        await page.locator("#gallery").evaluate((root, dir) => (root.dir = dir), direction);
        await page.locator("#photo-0").click();
        await expect(displayed(page)).toBeVisible();
        const thumbnails = page.locator('[data-slot="image-viewer-thumbnail"]');
        await thumbnails.nth(0).focus();
        await thumbnails.nth(0).press(direction === "rtl" ? "ArrowLeft" : "ArrowRight");
        await expect(thumbnails.nth(1)).toBeFocused();
        await thumbnails.nth(1).press("End");
        await expect(thumbnails.nth(29)).toBeFocused();
        await expect
            .poll(() =>
                thumbnails.nth(29).evaluate((node) => {
                    const button = node.getBoundingClientRect();
                    const strip = node.parentElement.getBoundingClientRect();
                    return button.left >= strip.left - 1 && button.right <= strip.right + 1;
                }),
            )
            .toBe(true);
    });
}

test("opens the original, zooms and pans within bounds, resets and returns focus", async ({ page }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(overlay(page)).toBeVisible();
    await expect(displayed(page)).toBeVisible();
    await expect(displayed(page)).toHaveAttribute("src", image());
    expect(await displayed(page).getAttribute("srcset")).toBeNull();
    const initial = await page.evaluate(() => window.viewer().geometry.scale);
    for (let step = 0; step < 4; step++) await page.locator("#zoom").click();
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBeGreaterThan(initial);
    const bounds = await page.locator('[data-image-viewer-target="viewport"]').boundingBox();
    await page.mouse.move(bounds.x + bounds.width / 2, bounds.y + bounds.height / 2);
    await page.mouse.down();
    await page.mouse.move(bounds.x + bounds.width, bounds.y + bounds.height, { steps: 5 });
    await page.mouse.up();
    expect(await page.evaluate(() => window.viewer().geometry.x)).toBeLessThanOrEqual(0);
    await page.locator("#reset").click();
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(initial);
    await page.keyboard.press("Escape");
    await expect(overlay(page)).toBeHidden();
    await expect(page.locator("#one")).toBeFocused();
});

test("keeps keyboard navigation on toolbar controls at both gallery boundaries", async ({ page }) => {
    await start(page, item("one") + item("two") + item("three"));
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await expect(page.locator('[data-image-viewer-target="dialog"]')).toBeFocused();
    await page.keyboard.press("Tab");
    await expect(page.locator("#next")).toBeFocused();
    await page.keyboard.press("ArrowRight");
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 3");
    await page.keyboard.press("ArrowRight");
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("3 / 3");
    await expect(page.locator("#prev")).toBeFocused();
    await expect(page.locator('[data-image-viewer-target="viewport"]')).not.toBeFocused();
    await page.keyboard.press("ArrowLeft");
    await page.keyboard.press("ArrowLeft");
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 3");
    await expect(page.locator("#next")).toBeFocused();
    await expect(page.locator('[data-image-viewer-target="viewport"]')).not.toBeFocused();
});

test("exposes grab only when panning is possible and grabbing only while dragging", async ({ page }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const viewport = page.locator('[data-image-viewer-target="viewport"]');
    await expect(viewport).toHaveCSS("cursor", "default");
    for (let step = 0; step < 4; step++) await page.locator("#zoom").click();
    await expect(viewport).toHaveCSS("cursor", "grab");
    const bounds = await viewport.boundingBox();
    await page.mouse.move(bounds.x + bounds.width / 2, bounds.y + bounds.height / 2);
    await page.mouse.down();
    await expect(viewport).toHaveCSS("cursor", "grabbing");
    await page.mouse.move(bounds.x + bounds.width / 2 + 20, bounds.y + bounds.height / 2 + 20);
    await page.mouse.up();
    await expect(viewport).toHaveCSS("cursor", "grab");
    await page.mouse.down();
    await expect(viewport).toHaveCSS("cursor", "grabbing");
    await viewport.dispatchEvent("pointercancel");
    await expect(viewport).toHaveCSS("cursor", "grab");
    await page.mouse.up();
    await page.locator("#reset").click();
    await expect(viewport).toHaveCSS("cursor", "default");
});

test("keeps the default cursor when zooming a small original still leaves no panning area", async ({ page }) => {
    await start(page, item("one", image(100, 100)));
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    await expect(page.locator('[data-image-viewer-target="viewport"]')).toHaveCSS("cursor", "default");
});

test("selects exact natural-size zoom, reflects intermediate zoom and returns to fit", async ({ page }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const select = page.getByRole("combobox", { name: "Zoom level" });
    await expect(select).toBeEnabled();
    await expect(select).toHaveValue("fit");
    await expect(select.locator('option[value="0.25"]')).toBeDisabled();
    await select.selectOption("1");
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(1);
    await expect(select).toHaveValue("1");
    await expect(displayed(page)).toHaveCSS("width", "1600px");
    await expect(displayed(page)).toHaveCSS("height", "1200px");
    await select.selectOption("2");
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(2);
    await page.locator("#zoom").click();
    await expect(select).toHaveValue("current");
    await expect(select.locator('option[value="current"]')).toHaveText("250%");
    await select.focus();
    await select.selectOption("fit");
    await expect(select).toHaveValue("fit");
    expect(await page.evaluate(() => window.viewer().atFit)).toBe(true);
    await expect(select).toBeFocused();
    await select.press("ArrowDown");
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 2");
    await expect(select).not.toHaveValue("fit");
});

test("the translated fit label survives zoom updates, resizing and a full morph", async ({ page }) => {
    await start(page);
    await page.locator('option[value="fit"]').evaluate((option) => {
        option.dataset.label = option.textContent = "Ajustar imagem";
    });
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const fit = page.locator('option[value="fit"]');
    await expect(fit).toHaveText(/^Ajustar imagem \(\d+%\)$/);
    await page.locator("#zoom").click();
    await expect(fit).toHaveText(/^Ajustar imagem \(\d+%\)$/);
    await page.setViewportSize({ width: 900, height: 700 });
    await expect(fit).toHaveText(/^Ajustar imagem \(\d+%\)$/);
    await page.evaluate((html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        const option = template.content.querySelector('option[value="fit"]');
        option.dataset.label = option.textContent = "Ajustar imagem";
        window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
    }, markup());
    await expect(fit).toHaveText(/^Ajustar imagem \(\d+%\)$/);
    await page.getByRole("combobox", { name: "Zoom level" }).selectOption("fit");
    expect(await page.evaluate(() => window.viewer().atFit)).toBe(true);
});

test("updates a surviving controller on reorder, original change, removal, empty and repopulation", async ({
    page,
}) => {
    await start(page);
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.evaluate(() => {
        window.originalController = window.viewer();
        const source = document.querySelector("#sources");
        source.prepend(document.querySelector("#two"));
    });
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 2");
    const changed = image(2000, 800, "red");
    await page.locator("#two img").evaluate((img, url) => (img.dataset.original = url), changed);
    await expect(displayed(page)).toHaveAttribute("src", changed);
    await page.locator("#two").evaluate((node) => node.remove());
    await expect(displayed(page)).toHaveAttribute("src", image());
    await page.locator("#sources").evaluate((node) => node.replaceChildren());
    await expect(overlay(page)).toBeHidden();
    await page.locator("#sources").evaluate((node, html) => (node.innerHTML = html), item("three"));
    await page.locator("#three").click();
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer() === window.originalController)).toBe(true);
});

test("ignores stale loads, exposes safe download metadata and shows broken-image feedback", async ({ page }) => {
    await start(page, item("one", "https://images.test/slow.svg") + item("two"));
    let release;
    await page.route("https://images.test/slow.svg", async (route) => {
        await new Promise((resolve) => (release = resolve));
        await route.fulfill({
            contentType: "image/svg+xml",
            body: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>',
        });
    });
    await page.locator("#two img").evaluate((node) => {
        node.dataset.downloadUrl = "/download/two";
        node.dataset.downloadName = "two.svg";
        node.dataset.caption = "<script>not HTML</script>";
    });
    await page.locator("#one").click();
    await expect.poll(() => Boolean(release)).toBe(true);
    await page.locator("#next").click();
    await expect(displayed(page)).toHaveAttribute("src", image());
    await expect(page.locator('[data-image-viewer-target="download"]')).toHaveAttribute(
        "href",
        "https://gallery.test/download/two",
    );
    await expect(page.locator('[data-image-viewer-target="download"]')).toHaveAttribute("download", "two.svg");
    await expect(page.locator('[data-image-viewer-target="caption"]')).toHaveText("<script>not HTML</script>");
    release();
    await expect(displayed(page)).toHaveAttribute("src", image());
    await page.locator("#two img").evaluate((node) => (node.dataset.original = "javascript:alert(1)"));
    await expect(page.locator('[data-image-viewer-target="error"]')).toBeVisible();
    await expect(page.locator('[data-image-viewer-target="download"]')).not.toHaveAttribute("href");
});

test("handles pinch, transition to one finger and cancellation without navigation", async ({ page, context }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const bounds = await page.locator('[data-image-viewer-target="viewport"]').boundingBox();
    const session = await context.newCDPSession(page);
    const x = bounds.x + bounds.width / 2;
    const y = bounds.y + bounds.height / 2;
    const points = (distance) => [
        { x: x - distance, y, id: 1 },
        { x: x + distance, y, id: 2 },
    ];
    const initial = await page.evaluate(() => window.viewer().geometry.scale);
    await session.send("Input.dispatchTouchEvent", { type: "touchStart", touchPoints: points(40) });
    await session.send("Input.dispatchTouchEvent", { type: "touchMove", touchPoints: points(100) });
    await expect.poll(() => page.evaluate(() => window.viewer().geometry.scale)).toBeGreaterThan(initial);
    await session.send("Input.dispatchTouchEvent", { type: "touchEnd", touchPoints: [{ x: x - 100, y, id: 1 }] });
    const before = await page.evaluate(() => window.viewer().geometry.x);
    await session.send("Input.dispatchTouchEvent", { type: "touchMove", touchPoints: [{ x: x - 90, y, id: 1 }] });
    const after = await page.evaluate(() => window.viewer().geometry.x);
    expect(Math.abs(after - before)).toBeLessThanOrEqual(11);
    await session.send("Input.dispatchTouchEvent", { type: "touchCancel", touchPoints: [] });
    expect(await page.evaluate(() => window.viewer().pointers.size)).toBe(0);
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 2");
});

test("survives source morph and normalizes cache state without duplicate overlays", async ({ page }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.evaluate(
        (html) => {
            const source = document.querySelector("#sources");
            const template = document.createElement("template");
            template.innerHTML = `<div id="sources" data-image-viewer-target="source">${html}</div>`;
            window.Turbo.morphElements(source, template.content.firstElementChild);
        },
        item("one", image(), "Updated caption") + item("two"),
    );
    await expect(page.locator('[data-image-viewer-target="caption"]')).toHaveText("Updated caption");
    await page.evaluate(() => document.dispatchEvent(new Event("turbo:before-cache")));
    await expect(overlay(page)).toBeHidden();
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            return copy.querySelector('[data-image-viewer-target="overlay"]').hidden;
        }),
    ).toBe(true);
    await page.locator("#gallery").evaluate((node) => node.remove());
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
    await expect(page.locator("[data-hotwire-top-layer]")).toHaveCount(0);
});

test("recovers the presentation after full morph and shell replacement while retaining selection", async ({ page }) => {
    await start(page);
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    const geometry = await page.evaluate(() => {
        window.morphChanges = 0;
        document.querySelector("#gallery").addEventListener("image-viewer:changed", () => window.morphChanges++);
        return { ...window.viewer().geometry };
    });
    const html = markup();
    await page.evaluate((html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
    }, html);
    await expect(displayed(page)).toHaveAttribute("src", image(800, 1200, "blue"));
    await expect(displayed(page)).toBeVisible();
    expect(await page.evaluate(() => window.viewer().geometry)).toEqual(geometry);
    expect(await page.evaluate(() => window.morphChanges)).toBe(0);
    const rendered = await displayed(page).boundingBox();
    expect(rendered.width).toBeCloseTo(geometry.naturalWidth * geometry.scale, 1);
    expect(rendered.height).toBeCloseTo(geometry.naturalHeight * geometry.scale, 1);
    await expect(page.locator('[data-image-viewer-target="loading"]')).toBeHidden();
    await page.locator("#gallery").evaluate((root, html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        root.replaceChildren(...template.content.firstElementChild.childNodes);
    }, html);
    await expect(displayed(page)).toHaveAttribute("src", image(800, 1200, "blue"));
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
    await expect(overlay(page)).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(overlay(page)).toBeHidden();
});

test("a full morph preserves pending loading feedback without restarting the request", async ({ page }) => {
    const url = "https://gallery.test/morph-pending.svg";
    const sources = item("one", url) + item("two");
    let release;
    let requests = 0;
    await page.route(url, async (route) => {
        if (++requests === 1) await new Promise((resolve) => (release = resolve));
        await route.fulfill({
            contentType: "image/svg+xml",
            body: '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1200"/>',
        });
    });
    await start(page, sources);
    await page.locator("#one").click();
    const loading = page.locator('[data-image-viewer-target="loading"]');
    const error = page.locator('[data-image-viewer-target="error"]');
    const viewport = page.locator('[data-image-viewer-target="viewport"]');
    await expect.poll(() => Boolean(release)).toBe(true);
    await expect(loading).toBeVisible();
    await expect(displayed(page)).toHaveAttribute("aria-busy", "true");
    await expect(viewport).not.toHaveAttribute("aria-busy");
    expect(await loading.evaluate((node) => node.closest('[aria-busy="true"]') === null)).toBe(true);
    await page.evaluate((html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
    }, markup(sources));
    await expect(loading).toBeVisible();
    await expect(error).toBeHidden();
    await expect(displayed(page)).toHaveAttribute("aria-busy", "true");
    await expect(viewport).not.toHaveAttribute("aria-busy");
    expect(await loading.evaluate((node) => node.closest('[aria-busy="true"]') === null)).toBe(true);
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            return copy.querySelector('[data-image-viewer-target="image"]').hasAttribute("aria-busy");
        }),
    ).toBe(false);
    await expect(displayed(page)).toBeHidden();
    expect(requests).toBe(1);
    release();
    await expect(displayed(page)).toBeVisible();
    await expect(loading).toBeHidden();
    await expect(error).toBeHidden();
    await expect(viewport).not.toHaveAttribute("aria-busy");
    await expect(displayed(page)).not.toHaveAttribute("aria-busy");
});

test("a full morph preserves failed loading feedback without retrying and navigation clears it", async ({ page }) => {
    const url = "https://gallery.test/morph-failed.svg";
    const sources = item("one", url) + item("two");
    let abort;
    let requests = 0;
    await page.route(url, async (route) => {
        if (++requests === 1) await new Promise((resolve) => (abort = resolve));
        await route.abort("failed");
    });
    await start(page, sources);
    await page.locator("#one").click();
    const loading = page.locator('[data-image-viewer-target="loading"]');
    const error = page.locator('[data-image-viewer-target="error"]');
    const viewport = page.locator('[data-image-viewer-target="viewport"]');
    await expect.poll(() => Boolean(abort)).toBe(true);
    abort();
    await expect(error).toBeVisible();
    await page.evaluate((html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
    }, markup(sources));
    await expect(error).toBeVisible();
    await expect(loading).toBeHidden();
    await expect(displayed(page)).toBeHidden();
    await expect(viewport).not.toHaveAttribute("aria-busy");
    await expect(displayed(page)).not.toHaveAttribute("aria-busy");
    expect(await error.evaluate((node) => node.closest('[aria-busy="true"]') === null)).toBe(true);
    expect(requests).toBe(1);
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            return {
                loading: copy.querySelector('[data-image-viewer-target="loading"]').hidden,
                error: copy.querySelector('[data-image-viewer-target="error"]').hidden,
                busy: copy.querySelector('[data-image-viewer-target="image"]').hasAttribute("aria-busy"),
            };
        }),
    ).toEqual({ loading: true, error: true, busy: false });
    await page.locator("#next").click();
    await expect(displayed(page)).toBeVisible();
    await expect(error).toBeHidden();
    await expect(loading).toBeHidden();
    await expect(viewport).not.toHaveAttribute("aria-busy");
});

test("preserves zoom across keyed source replacement and updates accessible text", async ({ page }) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#zoom").click();
    const scale = await page.evaluate(() => window.viewer().geometry.scale);
    await page
        .locator("#sources")
        .evaluate((source, html) => (source.innerHTML = html), item("one", image(), "New caption") + item("two"));
    await expect(page.locator('[data-image-viewer-target="caption"]')).toHaveText("New caption");
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(scale);
    await page.locator("#one img").evaluate((img) => (img.alt = "Updated accessible description"));
    await expect(displayed(page)).toHaveAttribute("alt", "Updated accessible description");
    await page.keyboard.press("Escape");
    await expect(page.locator("#one")).toBeFocused();
});

test("keyed source replacement retains the initiating focus destination rather than the current image", async ({
    page,
}) => {
    await start(page);
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    await page.locator("#next").click();
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
    await page.locator("#sources").evaluate((source, html) => (source.innerHTML = html), item("one") + item("two"));
    await expect(displayed(page)).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(page.locator("#one")).toBeFocused();
});

test("shares overlay stacking and scroll lock with a parent Modal", async ({ page }) => {
    await start(page);
    await page.evaluate(() => {
        const parent = document.createElement("div");
        parent.id = "parent";
        parent.dataset.controller = "modal";
        parent.setAttribute("data-modal-lock-scroll-class", "overflow-hidden");
        parent.innerHTML = `<div data-modal-target="modal" hidden inert>
            <div data-modal-target="backdrop"></div>
            <div data-modal-target="dialog" role="dialog" aria-label="Parent" tabindex="-1"><button id="parent-button">Parent</button></div>
        </div>`;
        parent.querySelector('[data-modal-target="dialog"]').append(document.querySelector("#gallery"));
        document.body.append(parent);
    });
    await page.waitForFunction(
        () => window.app.getControllerForElementAndIdentifier(document.querySelector("#parent"), "modal")?.overlay,
    );
    await page.evaluate(() =>
        window.app.getControllerForElementAndIdentifier(document.querySelector("#parent"), "modal").overlay.open(),
    );
    await page.locator("#two").click();
    await expect(displayed(page)).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(overlay(page)).toBeHidden();
    await expect(page.locator('[data-modal-target="modal"]')).toBeVisible();
    await expect(page.locator("#two")).toBeFocused();
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);
    await page.keyboard.press("Escape");
    await expect(page.locator('[data-modal-target="modal"]')).toBeHidden();
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
});

test("keeps modified links native and handles wheel, keyboard and failed image requests", async ({ page }) => {
    await start(page);
    expect(
        await page
            .locator("#one")
            .evaluate(
                (node) =>
                    !node.dispatchEvent(
                        new MouseEvent("click", { bubbles: true, cancelable: true, ctrlKey: true, button: 0 }),
                    ),
            ),
    ).toBe(false);
    await expect(overlay(page)).toBeHidden();
    await page.locator("#one").click();
    await expect(displayed(page)).toBeVisible();
    const initial = await page.evaluate(() => window.viewer().geometry.scale);
    await page.locator('[data-image-viewer-target="viewport"]').hover();
    await page.mouse.wheel(0, -100);
    await expect.poll(() => page.evaluate(() => window.viewer().geometry.scale)).toBeGreaterThan(initial);
    await page.keyboard.press("0");
    expect(await page.evaluate(() => window.viewer().geometry.scale)).toBe(initial);
    await page.keyboard.press("End");
    await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
    await page.route("https://gallery.test/broken.svg", (route) =>
        route.fulfill({ status: 404, body: "not an image" }),
    );
    await page.locator("#two img").evaluate((node) => (node.dataset.original = "/broken.svg"));
    await expect(page.locator('[data-image-viewer-target="error"]')).toBeVisible();
    await expect(displayed(page)).toBeHidden();
});

test.describe("preset presentation", () => {
    let css;
    test.beforeAll(async () => {
        css = Object.fromEntries(
            await Promise.all(
                ["nova", "bloom"].map(async (preset) => [
                    preset,
                    await compileCssFixture(
                        packageSourceEntrypoint(
                            `../../vendor/emaia/laravel-hotwire/resources/css/presets/${preset}.css`,
                        ),
                    ),
                ]),
            ),
        );
    });

    for (const preset of ["nova", "bloom"]) {
        for (const reducedMotion of ["no-preference", "reduce", "none"]) {
            test(`${preset} centers loading and error feedback with ${reducedMotion} motion`, async ({ page }) => {
                await page.emulateMedia({ reducedMotion: reducedMotion === "reduce" ? "reduce" : "no-preference" });
                let release;
                await page.route("https://gallery.test/status.svg", async (route) => {
                    await new Promise((resolve) => (release = resolve));
                    await route.abort("failed");
                });
                await start(page, item("one", "/status.svg"), { size: "full" });
                if (reducedMotion === "none") await overlay(page).evaluate((node) => (node.dataset.motion = "none"));
                await page.addStyleTag({ content: css[preset] });
                await page.locator("#one").click();
                await expect.poll(() => Boolean(release)).toBe(true);
                await page.evaluate(() => window.viewer().overlay.settled);
                const viewport = page.locator('[data-image-viewer-target="viewport"]');
                const loading = page.locator('[data-image-viewer-target="loading"]');
                const error = page.locator('[data-image-viewer-target="error"]');
                const spinner = page.locator('[data-slot="image-viewer-loading-icon"]');
                await expect(loading).toBeVisible();
                await expect(error).toBeHidden();
                await expect(spinner).toBeVisible();
                await expect(loading).toHaveCSS("align-items", "center");
                await expect(loading).toHaveCSS("justify-content", "center");
                expect(await loading.boundingBox()).toEqual(await viewport.boundingBox());
                if (reducedMotion === "none") await expect(spinner).toHaveCSS("animation-name", "none");
                else if (reducedMotion === "reduce") await expect(spinner).not.toHaveCSS("animation-name", "spin");
                else await expect(spinner).toHaveCSS("animation-name", "spin");
                release();
                await expect(error).toBeVisible();
                await expect(loading).toBeHidden();
                await expect(error.locator('[data-slot="image-viewer-error-icon"]')).toBeVisible();
                await expect(error).toHaveCSS("align-items", "center");
                await expect(error).toHaveCSS("justify-content", "center");
                expect(await error.boundingBox()).toEqual(await viewport.boundingBox());
                await expect(error).toHaveText("Cannot load image");
            });
        }

        for (const mobile of [false, true]) {
            test(`${preset} full size is edge-to-edge and borderless on ${mobile ? "mobile" : "desktop"}`, async ({
                page,
            }, testInfo) => {
                await page.setViewportSize(mobile ? { width: 390, height: 844 } : { width: 1280, height: 900 });
                await start(page, item("one") + item("two"), { size: "full", thumbnails: true });
                await page.addStyleTag({ content: css[preset] });
                await page.locator("#one").click();
                await page.evaluate(() => window.viewer().overlay.settled);
                const dialog = page.locator('[data-image-viewer-target="dialog"]');
                await expect.poll(() => dialog.boundingBox()).toEqual({ x: 0, y: 0, ...page.viewportSize() });
                const frame = await dialog.evaluate((node) => {
                    const style = getComputedStyle(node);
                    return {
                        borders: [
                            style.borderTopWidth,
                            style.borderRightWidth,
                            style.borderBottomWidth,
                            style.borderLeftWidth,
                        ],
                        corners: [
                            style.borderTopLeftRadius,
                            style.borderTopRightRadius,
                            style.borderBottomLeftRadius,
                            style.borderBottomRightRadius,
                        ],
                        shadow: style.boxShadow,
                    };
                });
                expect(frame).toEqual({
                    borders: ["0px", "0px", "0px", "0px"],
                    corners: ["0px", "0px", "0px", "0px"],
                    shadow: "none",
                });
                await expect(displayed(page)).toBeVisible();
                await expect(page.locator('[data-image-viewer-target="viewport"]')).toBeVisible();
                await dialog.screenshot({
                    path: testInfo.outputPath(`${preset}-${mobile ? "mobile" : "desktop"}-full.png`),
                });
                await page.locator('[data-slot="image-viewer-thumbnail"]').nth(1).click();
                await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("2 / 2");
                await page.keyboard.press("Escape");
                await expect(overlay(page)).toBeHidden();
                await expect(page.locator("#one")).toBeFocused();
            });
        }

        test.describe(`${preset} touch layout`, () => {
            test.use({ hasTouch: true, isMobile: true });

            for (const [orientation, size] of [
                ["portrait", { width: 390, height: 844 }],
                ["narrow portrait", { width: 360, height: 780 }],
                ["landscape", { width: 844, height: 390 }],
            ]) {
                test(`${preset} fills the ${orientation} screen and overlays hideable controls`, async ({
                    page,
                }, testInfo) => {
                    await page.setViewportSize(size);
                    await start(page, item("one", image(2400, 1600)) + item("two") + item("three"), {
                        arrows: true,
                        thumbnails: true,
                        rotate: true,
                        flip: true,
                        tools: true,
                    });
                    await page.addStyleTag({ content: css[preset] });
                    await page.locator("#one img").tap();
                    await expect(displayed(page)).toBeVisible();
                    await page.evaluate(() => window.viewer().overlay.settled);
                    const dialog = page.locator('[data-image-viewer-target="dialog"]');
                    const viewport = page.locator('[data-image-viewer-target="viewport"]');
                    expect(await dialog.boundingBox()).toEqual({ x: 0, y: 0, ...size });
                    expect(await viewport.boundingBox()).toEqual({ x: 0, y: 0, ...size });
                    await expect(dialog).toHaveCSS("border-top-width", "0px");
                    await expect(page.locator("#tools-toggle")).toBeVisible();
                    await expect(page.locator("#zoom")).toBeHidden();
                    const shot = (state) =>
                        page.screenshot({ path: testInfo.outputPath(`${preset}-${orientation}-${state}.png`) });
                    await shot("controls");
                    await page.locator("#tools-toggle").tap();
                    await expect(page.locator("#zoom")).toBeVisible();
                    // Every tool, including the zoom selector, fits one row below the primary actions.
                    const rows = await page.locator("#tools > *").evaluateAll((nodes) => [
                        ...new Set(
                            nodes
                                .filter((node) => node.getClientRects().length)
                                .map((node) => {
                                    const box = node.getBoundingClientRect();
                                    return Math.round(box.top + box.height / 2);
                                }),
                        ),
                    ]);
                    expect(rows).toHaveLength(1);
                    await expect(page.locator("#zoom-value")).toHaveText(/^\d+%$/);
                    await shot("tools");
                    const box = await viewport.boundingBox();
                    await page.touchscreen.tap(box.width / 2, box.height / 2);
                    await expect(dialog).toHaveAttribute("data-controls", "hidden");
                    await expect(page.locator('[data-slot="image-viewer-toolbar"]')).toHaveCSS("opacity", "0");
                    await shot("hidden");
                });
            }
        });

        for (const direction of ["ltr", "rtl"]) {
            test(`${preset} keeps both ends of an overflowing thumbnail strip reachable in ${direction}`, async ({
                page,
            }) => {
                await page.setViewportSize({ width: 390, height: 844 });
                await start(page, Array.from({ length: 30 }, (_, index) => item(`photo-${index}`)).join(""), {
                    thumbnails: true,
                });
                await page.locator("#gallery").evaluate((root, dir) => (root.dir = dir), direction);
                await page.addStyleTag({ content: css[preset] });
                await page.locator("#photo-0").click();
                await page.evaluate(() => window.viewer().overlay.settled);
                const thumbnails = page.locator('[data-slot="image-viewer-thumbnail"]');
                const visible = (node) => {
                    const button = node.getBoundingClientRect();
                    const strip = node.parentElement.getBoundingClientRect();
                    return button.left >= strip.left - 1 && button.right <= strip.right + 1;
                };
                await expect.poll(() => thumbnails.nth(0).evaluate(visible)).toBe(true);
                await thumbnails.nth(0).focus();
                await thumbnails.nth(0).press("End");
                await expect.poll(() => thumbnails.nth(29).evaluate(visible)).toBe(true);
                await thumbnails.nth(29).press("Home");
                await expect.poll(() => thumbnails.nth(0).evaluate(visible)).toBe(true);
            });
        }

        test(`${preset} keeps the top-layer root transparent so only the backdrop dims the unchanged page`, async ({
            page,
        }) => {
            await start(page);
            await page.addStyleTag({ content: css[preset] });
            await page.evaluate(() => {
                window.sourceBeforeOpening = document.querySelector("#sources");
            });
            await page.locator("#one").click();
            await page.evaluate(() => window.viewer().overlay.settled);
            await expect(overlay(page)).toHaveCSS("background-color", "rgba(0, 0, 0, 0)");
            const background = await page.evaluate(() => {
                const canvas = document.createElement("canvas");
                const context = canvas.getContext("2d");
                context.fillStyle = getComputedStyle(
                    document.querySelector('[data-image-viewer-target="backdrop"]'),
                ).backgroundColor;
                context.fillRect(0, 0, 1, 1);
                return {
                    alpha: context.getImageData(0, 0, 1, 1).data[3],
                    retained: document.querySelector("#sources") === window.sourceBeforeOpening,
                    display: getComputedStyle(document.querySelector("#sources")).display,
                };
            });
            expect(background.retained).toBe(true);
            expect(background.display).not.toBe("none");
            expect(background.alpha).toBeGreaterThan(0);
            expect(background.alpha).toBeLessThan(255);
        });

        test(`${preset} keeps a long caption bounded, readable and keyboard-scrollable`, async ({ page }) => {
            const longCaption = "Inspection details and credit information. ".repeat(250) + "end-of-caption";
            await start(page, item("one", image(), longCaption) + item("two"));
            await page.addStyleTag({ content: css[preset] });
            await page.locator("#one").click();
            await expect(displayed(page)).toBeVisible();
            await page.evaluate(() => window.viewer().overlay.settled);
            const caption = page.locator('[data-image-viewer-target="caption"]');
            const dimensions = await caption.evaluate((node) => ({
                height: node.clientHeight,
                content: node.scrollHeight,
                viewport: document.querySelector('[data-image-viewer-target="viewport"]').clientHeight,
            }));
            expect(dimensions.content).toBeGreaterThan(dimensions.height);
            expect(dimensions.viewport).toBeGreaterThan(0);
            await expect(caption).toHaveAttribute("tabindex", "0");
            await caption.focus();
            await caption.press("End");
            await expect.poll(() => caption.evaluate((node) => node.scrollTop)).toBeGreaterThan(0);
            await expect(page.locator('[data-image-viewer-target="counter"]')).toHaveText("1 / 2");
            await caption.press("Home");
            await expect.poll(() => caption.evaluate((node) => node.scrollTop)).toBe(0);
            await page.locator("#one img").evaluate((img) => (img.dataset.caption = "Short caption"));
            await expect(caption).toHaveText("Short caption");
            await expect(caption).not.toHaveAttribute("tabindex");
        });

        test(`${preset} animates its shell while loading the correctly measured image, then supports interrupted exit and cache`, async ({
            page,
        }) => {
            await page.emulateMedia({ reducedMotion: "no-preference" });
            await start(page);
            await page.addStyleTag({ content: css[preset] });
            await page.addStyleTag({
                content:
                    '[data-slot="image-viewer-dialog"], [data-slot="image-viewer-backdrop"] { transition-duration: 1s !important; }',
            });
            await page.evaluate(() => {
                window.pendingOpen = window.viewer().show(0);
            });
            const dialog = page.locator('[data-image-viewer-target="dialog"]');
            await expect
                .poll(() =>
                    dialog.evaluate((node) =>
                        node.getAnimations().some((animation) => animation.playState === "running"),
                    ),
                )
                .toBe(true);
            await expect
                .poll(() =>
                    page.evaluate(
                        () =>
                            window.viewer().overlay.isOpening &&
                            !document.querySelector('[data-image-viewer-target="image"]').hidden,
                    ),
                )
                .toBe(true);
            const measurements = await page.evaluate(() => ({
                measured: window.viewer().geometry.viewportWidth,
                layout: document.querySelector('[data-image-viewer-target="viewport"]').clientWidth,
            }));
            expect(measurements.measured).toBe(measurements.layout);
            await page.evaluate(() => window.pendingOpen);
            await page.evaluate(() => {
                void window.viewer().close();
            });
            await expect(overlay(page)).toHaveAttribute("inert", "");
            await expect(overlay(page)).not.toHaveAttribute("hidden", "");
            await expect(displayed(page)).toBeVisible();
            await expect
                .poll(() =>
                    dialog.evaluate((node) =>
                        node.getAnimations().some((animation) => animation.playState === "running"),
                    ),
                )
                .toBe(true);
            await page.evaluate(() => window.viewer().show(0));
            await expect(overlay(page)).toHaveAttribute("data-state", "open");
            await expect(displayed(page)).toBeVisible();
            await page.evaluate(() => {
                void window.viewer().close();
                document.dispatchEvent(new Event("turbo:before-cache"));
            });
            await expect(overlay(page)).toHaveAttribute("hidden", "");
        });

        for (const preference of ["reduced", "none"]) {
            test(`${preset} suppresses motion for ${preference}`, async ({ page }) => {
                await page.emulateMedia({ reducedMotion: preference === "reduced" ? "reduce" : "no-preference" });
                await start(page);
                await page.addStyleTag({ content: css[preset] });
                if (preference === "none") await overlay(page).evaluate((node) => (node.dataset.motion = "none"));
                await page.locator("#one").click();
                await expect(displayed(page)).toBeVisible();
                await expect(page.locator('[data-image-viewer-target="dialog"]')).toHaveCSS(
                    "transition-property",
                    "none",
                );
                await page.evaluate(() => window.viewer().close());
                await expect(overlay(page)).toHaveAttribute("hidden", "");
            });
        }
    }
});
