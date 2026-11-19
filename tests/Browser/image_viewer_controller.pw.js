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

function markup(items = item("one") + item("two", image(800, 1200, "blue"))) {
    return `<div id="gallery" data-controller="image-viewer">
        <div id="sources" data-image-viewer-target="source">${items}</div>
        <div data-slot="image-viewer-overlay" data-image-viewer-target="overlay" hidden inert>
            <div data-slot="image-viewer-backdrop" data-image-viewer-target="backdrop"></div>
            <section data-slot="image-viewer-dialog" data-image-viewer-target="dialog" role="dialog" aria-modal="true" aria-label="Photos" tabindex="-1">
                <div data-slot="image-viewer-toolbar">
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
                <div data-slot="image-viewer-viewport" data-image-viewer-target="viewport" tabindex="0" aria-label="Image">
                    <img data-slot="image-viewer-image" data-image-viewer-target="image" alt="" hidden>
                    <p data-image-viewer-target="loading">Loading</p>
                    <p data-image-viewer-target="error" hidden>Cannot load image</p>
                </div>
                <p data-slot="image-viewer-caption" data-image-viewer-target="caption"></p>
            </section>
        </div>
    </div>`;
}

async function start(page, items) {
    await page.setContent(`<base href="https://gallery.test/"><style>${readFileSync("resources/css/structural.css", "utf8")}
        [data-slot="image-viewer-toolbar"] { display:flex; gap:8px; padding:8px }
        [data-slot="image-viewer-dialog"] { background:Canvas; color:CanvasText }
        #sources img { width:80px; height:60px }
    </style>${markup(items)}`);
    await page.addScriptTag({ content: bundle });
    await page.waitForFunction(() => window.viewer()?.connected);
}

const overlay = (page) => page.locator('[data-image-viewer-target="overlay"]');
const displayed = (page) => page.locator('[data-image-viewer-target="image"]');

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
    const html = markup();
    await page.evaluate((html) => {
        const template = document.createElement("template");
        template.innerHTML = html;
        window.Turbo.morphElements(document.querySelector("#gallery"), template.content.firstElementChild);
    }, html);
    await expect(displayed(page)).toHaveAttribute("src", image(800, 1200, "blue"));
    await expect(displayed(page)).toBeVisible();
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
        test(`${preset} keeps the top-layer root transparent so only the backdrop dims the unchanged page`, async ({ page }) => {
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
                context.fillStyle = getComputedStyle(document.querySelector('[data-image-viewer-target="backdrop"]')).backgroundColor;
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
