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

function markup(items = item("one") + item("two", image(800, 1200, "blue")), thumbnails = false) {
    return `<div id="gallery" data-controller="image-viewer">
        <div id="sources" data-image-viewer-target="source">${items}</div>
        <div data-slot="image-viewer-overlay" data-image-viewer-target="overlay" hidden inert>
            <div data-slot="image-viewer-backdrop" data-image-viewer-target="backdrop"></div>
            <section data-slot="image-viewer-dialog" data-image-viewer-target="dialog" role="dialog" aria-modal="true" aria-label="Photos" tabindex="-1">
                <div data-slot="image-viewer-toolbar" role="group" aria-label="Image controls">
                    <button id="prev" data-image-viewer-target="previous" data-action="image-viewer#previous">Previous</button>
                    <button id="next" data-image-viewer-target="next" data-action="image-viewer#next">Next</button>
                    <button id="zoom" data-image-viewer-target="zoomIn" data-action="image-viewer#zoomIn">Zoom in</button>
                    <button data-image-viewer-target="zoomOut" data-action="image-viewer#zoomOut">Zoom out</button>
                    <button id="reset" data-image-viewer-target="reset" data-action="image-viewer#reset">Reset</button>
                    <span data-image-viewer-target="counter"></span><span data-image-viewer-target="zoomLabel"></span>
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
                    <a data-image-viewer-target="download" data-turbo="false" download>Download</a>
                    <button id="close" data-action="image-viewer#close">Close</button>
                </div>
                <div data-slot="image-viewer-viewport" data-image-viewer-target="viewport" role="group" tabindex="0" aria-label="Image">
                    <img data-slot="image-viewer-image" data-image-viewer-target="image" alt="" hidden>
                    <p data-image-viewer-target="loading" hidden>Loading</p>
                    <p data-image-viewer-target="error" hidden>Cannot load image</p>
                </div>
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
                <p data-slot="image-viewer-caption" data-image-viewer-target="caption"></p>
            </section>
        </div>
    </div>`;
}

async function start(page, items, { thumbnails = false } = {}) {
    await page.setContent(`<base href="https://gallery.test/"><style>${readFileSync("resources/css/structural.css", "utf8")}
        [data-slot="image-viewer-toolbar"] { display:flex; gap:8px; padding:8px }
        [data-slot="image-viewer-dialog"] { background:Canvas; color:CanvasText }
        #sources img { width:80px; height:60px }
        [data-slot="image-viewer-thumbnail"] { flex-shrink:0; width:64px; height:48px }
    </style>${markup(items, thumbnails)}`);
    await page.addScriptTag({ content: bundle });
    await page.waitForFunction(() => window.viewer()?.connected);
}

const overlay = (page) => page.locator('[data-image-viewer-target="overlay"]');
const displayed = (page) => page.locator('[data-image-viewer-target="image"]');

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
    await expect(viewport).toHaveAttribute("aria-busy", "true");
    await page.evaluate((html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
    }, markup(sources));
    await expect(loading).toBeVisible();
    await expect(error).toBeHidden();
    await expect(viewport).toHaveAttribute("aria-busy", "true");
    await expect(displayed(page)).toBeHidden();
    expect(requests).toBe(1);
    release();
    await expect(displayed(page)).toBeVisible();
    await expect(loading).toBeHidden();
    await expect(error).toBeHidden();
    await expect(viewport).not.toHaveAttribute("aria-busy");
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
    expect(requests).toBe(1);
    expect(
        await page.evaluate(() => {
            const copy = document.body.cloneNode(true);
            window.normalizeSnapshot(copy);
            return {
                loading: copy.querySelector('[data-image-viewer-target="loading"]').hidden,
                error: copy.querySelector('[data-image-viewer-target="error"]').hidden,
                busy: copy.querySelector('[data-image-viewer-target="viewport"]').hasAttribute("aria-busy"),
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
