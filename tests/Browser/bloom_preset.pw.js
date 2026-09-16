import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture, replacePresetImport } from "../../scripts/css_build_contract.js";

let bloomCss;

test.beforeAll(async () => {
    const source = replacePresetImport(await readFile("stubs/resources/css/app.css", "utf8"), "bloom");

    bloomCss = await compileCssFixture(source);
});

test("gives every control a visible immediate focus indicator", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <input id="input" data-slot="input">
        <select id="select" data-slot="select"></select>
        <textarea id="textarea" data-slot="textarea"></textarea>
        <input id="file" data-slot="file-input" type="file">
        <input id="checkbox" data-slot="checkbox" data-checkable="true" type="checkbox">
        <input id="radio" data-slot="radio-group-input" data-checkable="true" type="radio">
        <input id="switch" data-slot="switch" data-size="default" type="checkbox" role="switch">
        <input id="slider" data-slot="slider" data-orientation="horizontal" type="range">
        <input id="sidebar-input" data-slot="sidebar-input">
        <button id="multi-select-trigger" data-slot="multi-select-trigger"></button>
        <input id="multi-select-search" data-slot="multi-select-search">
        <div id="dropzone" data-slot="file-upload-dropzone" tabindex="0"></div>
        <button id="button" data-slot="button" data-variant="default" data-size="default">Button</button>
        <button id="toggle" data-slot="toggle" data-variant="outline" data-size="default" data-state="off">Toggle</button>
    `);

    const fields = [
        "#input",
        "#select",
        "#textarea",
        "#file",
        "#checkbox",
        "#radio",
        "#switch",
        "#slider",
        "#sidebar-input",
        "#multi-select-trigger",
        "#multi-select-search",
        "#dropzone",
        "#button",
        "#toggle",
    ];

    for (const field of fields) {
        await expect(page.locator(field), `${field} shows an outline before focus`).toHaveCSS("outline-style", "none");

        // "all" with a zero duration is the CSS initial value, not an animation.
        const animatesOutline = await page.locator(field).evaluate((element) => {
            const style = getComputedStyle(element);

            return (
                /\boutline|\ball\b/.test(style.transitionProperty) &&
                style.transitionDuration.split(",").some((duration) => parseFloat(duration) > 0)
            );
        });

        expect(animatesOutline, `${field} animates its focus outline into place`).toBe(false);
    }

    for (const field of fields) {
        await page.locator(field).focus();

        const outline = await page.locator(field).evaluate((element) => {
            const style = getComputedStyle(element);

            return {
                style: style.outlineStyle,
                width: parseFloat(style.outlineWidth),
                offset: parseFloat(style.outlineOffset),
            };
        });

        expect(outline.style, `${field} focus outline style`).not.toBe("none");
        expect(outline.width, `${field} focus outline width`).toBeGreaterThan(0);
        expect(outline.offset, `${field} focus outline offset`).toBeGreaterThan(0);
    }
});

test("focuses a rich text field through its editor", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div id="rich-text" data-slot="rich-text">
            <div data-slot="rich-text-editor"><div class="ProseMirror" contenteditable="true"></div></div>
        </div>
    `);

    await expect(page.locator("#rich-text")).toHaveCSS("outline-style", "none");

    await page.locator("#rich-text .ProseMirror").focus();

    const outline = await page.locator("#rich-text").evaluate((element) => {
        const style = getComputedStyle(element);

        return { style: style.outlineStyle, width: parseFloat(style.outlineWidth) };
    });

    expect(outline.style).not.toBe("none");
    expect(outline.width).toBeGreaterThan(0);
});

test("gives every toast type its own surface", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div data-slot="toast" data-type="default"></div>
        <div data-slot="toast" data-type="success"></div>
        <div data-slot="toast" data-type="error"></div>
        <div data-slot="toast" data-type="warning"></div>
        <div data-slot="toast" data-type="info"></div>
    `);

    const types = ["default", "success", "error", "warning", "info"];
    const surfaces = {};

    for (const type of types) {
        surfaces[type] = await page.locator(`[data-slot="toast"][data-type="${type}"]`).evaluate((element) => {
            const style = getComputedStyle(element);

            return [style.backgroundColor, style.borderColor].join(" ");
        });
    }

    expect(new Set(Object.values(surfaces)).size, `toast surfaces: ${JSON.stringify(surfaces)}`).toBe(types.length);
});

test("keeps collapsed sidebar controls inside the icon rail", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div data-slot="sidebar-wrapper" style="--sidebar-width-icon: 3rem">
            <div data-slot="sidebar" data-collapsible="icon">
                <button id="collapsed" data-slot="sidebar-menu-button" data-size="default">Item</button>
            </div>
        </div>
    `);

    // The collapsed rail is sized by a shared PHP default, so the icon row must still fit inside it.
    const collapsed = await page.locator("#collapsed").evaluate((element) => {
        const rail = parseFloat(
            getComputedStyle(document.querySelector('[data-slot="sidebar-wrapper"]')).getPropertyValue(
                "--sidebar-width-icon",
            ),
        );
        const box = element.getBoundingClientRect();

        return { width: box.width, rail: rail * 16 };
    });

    expect(collapsed.width, "collapsed row overflows the icon rail").toBeLessThanOrEqual(collapsed.rail);
});

test("keeps the sidebar scrollbar operable and system-owned under forced colors", async ({ page }) => {
    const sidebar = `
        <style>${bloomCss}</style>
        <div data-slot="sidebar-wrapper">
            <div data-slot="sidebar" data-collapsible="offcanvas" style="width: 240px">
                <div id="content" data-slot="sidebar-content" style="height: 120px">
                    <div style="height: 400px; flex: none"></div>
                </div>
            </div>
        </div>
    `;

    await page.setContent(sidebar);

    const scrollbar = () =>
        page.locator("#content").evaluate((element) => {
            const style = getComputedStyle(element);

            return { color: style.scrollbarColor, overflow: style.overflow };
        });

    const styled = await scrollbar();

    // The scroll mechanic is structural; the preset only dresses the bar.
    expect(styled.overflow, "sidebar content stopped scrolling").toBe("auto");
    expect(styled.color, "the thumb must stay visible at rest").not.toBe("auto");
    expect(styled.color, "a transparent thumb removes the scroll affordance").not.toMatch(
        /^(?:rgba\(0, 0, 0, 0\)|transparent)\s/,
    );

    await page.locator("#content").hover();

    const hovered = await scrollbar();

    expect(hovered.color, "hovering the sidebar gives no scrollbar feedback").not.toBe(styled.color);

    await page.emulateMedia({ forcedColors: "active" });
    await page.setContent(sidebar);

    const forced = await scrollbar();

    expect(forced.color, "forced colors must restore the system scrollbar").toBe("auto");
    expect(forced.overflow, "sidebar content stopped scrolling under forced colors").toBe("auto");
});

test("ships a chromatic palette instead of the neutral token defaults", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div id="light" data-theme="light"></div>
        <div id="dark" data-theme="dark"></div>
    `);

    const chroma = (selector, token) =>
        page.locator(selector).evaluate((element, name) => {
            const canvas = document.createElement("canvas");
            canvas.width = 1;
            canvas.height = 1;
            const context = canvas.getContext("2d", { colorSpace: "srgb" });
            context.fillStyle = getComputedStyle(element).getPropertyValue(name).trim();
            context.fillRect(0, 0, 1, 1);
            const [red, green, blue] = [...context.getImageData(0, 0, 1, 1).data];

            return Math.max(red, green, blue) - Math.min(red, green, blue);
        }, token);

    for (const scope of ["#light", "#dark"]) {
        for (const token of ["--primary", "--accent", "--ring", "--background"]) {
            expect(await chroma(scope, token), `${scope} ${token} is achromatic`).toBeGreaterThan(3);
        }
    }
});

test("gives Reveal an authored Bloom motion profile", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <section id="reveal" data-slot="reveal"></section>
        <aside id="sidebar" data-slot="sidebar" data-controller="reveal"></aside>
    `);

    const profile = (selector) =>
        page.locator(selector).evaluate((element) => {
            const style = getComputedStyle(element);
            const duration = style.getPropertyValue("--reveal-duration").trim();

            return {
                blur: style.getPropertyValue("--reveal-blur").trim(),
                shift: style.getPropertyValue("--reveal-shift").trim(),
                durationMs: parseFloat(duration) * (duration.endsWith("ms") ? 1 : 1000),
                stagger: style.getPropertyValue("--reveal-stagger").trim(),
                easing: style.getPropertyValue("--reveal-easing").trim(),
            };
        });

    const publicProfile = {
        blur: "12px",
        shift: "1.25rem",
        durationMs: 680,
        stagger: "90ms",
    };
    const revealProfile = await profile("#reveal");

    expect(revealProfile).toMatchObject(publicProfile);
    expect(await profile("#sidebar")).toEqual(revealProfile);
});

test("keeps incremental pagination available while surfacing its loading control", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <nav id="pagination" data-slot="pagination">
            <a id="next" data-slot="pagination-next" data-size="default">
                <span data-slot="pagination-next-content">Load more</span>
                <span data-slot="pagination-next-loading-content">
                    <span data-slot="pagination-next-spinner"></span>
                    Loading more
                </span>
            </a>
        </nav>
    `);

    const surface = () =>
        page.locator("#next").evaluate((element) => {
            const style = getComputedStyle(element);

            return [style.backgroundColor, style.borderColor, style.color, style.boxShadow];
        });

    const idle = await surface();
    await page.locator("#pagination").evaluate((element) => element.setAttribute("data-state", "loading"));

    await expect(page.locator("#pagination")).toHaveCSS("opacity", "1");
    expect(await surface()).not.toEqual(idle);
    await expect(page.locator('[data-slot="pagination-next-spinner"]')).not.toHaveCSS("animation-name", "none");
});

test("keeps high-variance selection and progress states observable", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div data-slot="tabs" data-orientation="horizontal">
            <div data-slot="tabs-list" data-variant="default">
                <button id="tab-idle" data-slot="tabs-trigger" data-state="inactive">Idle</button>
                <button id="tab-active" data-slot="tabs-trigger" data-state="active">Active</button>
            </div>
        </div>
        <button id="dropdown-item" data-slot="dropdown-item">Focusable item</button>
        <button id="dropdown-disabled" data-slot="dropdown-item" disabled>Disabled item</button>
        <div id="progress-track" data-slot="progress-track" style="width: 200px">
            <span id="progress-indicator" data-slot="progress-indicator" style="--progress-value: 37%"></span>
        </div>
    `);

    const surface = (selector) =>
        page.locator(selector).evaluate((element) => {
            const style = getComputedStyle(element);

            return [style.backgroundColor, style.color, style.boxShadow, style.opacity];
        });

    expect(await surface("#tab-active")).not.toEqual(await surface("#tab-idle"));

    const dropdownIdle = await surface("#dropdown-item");
    await page.locator("#dropdown-item").focus();
    expect(await surface("#dropdown-item")).not.toEqual(dropdownIdle);
    expect(await surface("#dropdown-disabled")).not.toEqual(dropdownIdle);

    const progress = await page.locator("#progress-indicator").boundingBox();
    expect(progress.width).toBeCloseTo(74, 1);
});

test("stops and reapplies dark surface adjustments at nearest theme boundaries", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <button id="light" data-slot="button" data-variant="outline">Light</button>
        <div data-theme="dark">
            <button id="dark" data-slot="button" data-variant="outline">Dark</button>
            <span id="dark-input" style="color: var(--input)"></span>
            <div data-theme="light">
                <button id="nested-light" data-slot="button" data-variant="outline">Light</button>
                <div data-theme="dark">
                    <button id="nested-dark" data-slot="button" data-variant="outline">Dark</button>
                </div>
            </div>
        </div>
    `);

    const border = (selector) => page.locator(selector).evaluate((element) => getComputedStyle(element).borderColor);
    const darkInput = await page.locator("#dark-input").evaluate((element) => getComputedStyle(element).color);

    expect(await border("#dark")).toBe(darkInput);
    expect(await border("#nested-light")).toBe(await border("#light"));
    expect(await border("#nested-dark")).toBe(darkInput);
});

test("keeps focus, invalid, disabled, checked and indeterminate states observable", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <input id="normal" data-slot="input">
        <input id="invalid" data-slot="input" aria-invalid="true">
        <input id="disabled" data-slot="input" disabled>
        <input id="off" data-slot="checkbox" data-checkable="true" type="checkbox">
        <input id="on" data-slot="checkbox" data-checkable="true" type="checkbox" checked>
        <input id="mixed" data-slot="checkbox" data-checkable="true" type="checkbox">
    `);
    await page.locator("#mixed").evaluate((element) => {
        element.indeterminate = true;
    });
    const state = (selector) =>
        page.locator(selector).evaluate((element) => {
            const style = getComputedStyle(element);
            const before = getComputedStyle(element, "::before");

            return [
                style.backgroundColor,
                style.borderColor,
                style.boxShadow,
                style.opacity,
                before.opacity,
                before.transform,
            ];
        });

    const normal = await state("#normal");

    await page.locator("#normal").focus();
    await expect(page.locator("#normal")).toHaveCSS("outline-style", "solid");
    expect(await state("#normal")).not.toEqual(normal);
    expect(await state("#invalid")).not.toEqual(normal);
    expect(await state("#disabled")).not.toEqual(normal);
    expect(await state("#on")).not.toEqual(await state("#off"));
    expect(await state("#mixed")).not.toEqual(await state("#off"));
});

test("disables decorative motion while retaining reduced-motion loading feedback", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <style>${bloomCss}</style>
        <div id="menu" data-slot="dropdown-menu" data-state="closed" data-side="bottom"></div>
        <div data-slot="modal-overlay" data-state="open">
            <div data-slot="modal-backdrop"></div>
            <div id="modal" data-slot="modal-positioner"></div>
        </div>
        <div id="skeleton" data-slot="skeleton"></div>
        <span id="spinner" data-slot="spinner"></span>
        <span id="pagination-spinner" data-slot="pagination-next-spinner"></span>
        <span id="progress" data-slot="progress-indicator"></span>
        <span id="carousel-progress" data-slot="carousel-progress"></span>
    `);

    await expect(page.locator("#menu")).toHaveCSS("transition-duration", "0s");
    await expect(page.locator("#modal")).toHaveCSS("transition-duration", "0s");
    await expect(page.locator("#skeleton")).toHaveCSS("animation-name", "none");
    await expect(page.locator("#spinner")).toHaveCSS("animation-name", "hotwire-status-pulse");
    await expect(page.locator("#pagination-spinner")).toHaveCSS("animation-name", "hotwire-status-pulse");
    await expect(page.locator("#progress")).toHaveCSS("transition-duration", "0s");
    await expect(page.locator("#carousel-progress")).toHaveCSS("transition-duration", "0s");
});

test("preserves forced-colors and print state fallbacks", async ({ page }) => {
    await page.emulateMedia({ forcedColors: "active" });
    await page.setContent(`
        <style>${bloomCss}</style>
        <input id="checkbox" data-slot="checkbox" data-checkable="true" type="checkbox" checked>
        <input id="switch" data-slot="switch" data-size="default" type="checkbox" role="switch" checked>
        <span id="shimmer" data-shimmer="true">Processing</span>
    `);

    await expect(page.locator("#checkbox")).toHaveCSS("appearance", "auto");
    await expect(page.locator("#switch")).toHaveCSS("appearance", "auto");

    await page.emulateMedia({ media: "print", forcedColors: "none" });
    await expect(page.locator("#checkbox")).toHaveCSS("appearance", "auto");
    await expect(page.locator("#switch")).toHaveCSS("appearance", "auto");
    await expect(page.locator("#shimmer")).toHaveCSS("animation-name", "none");
    await expect(page.locator("#shimmer")).toHaveCSS("background-image", "none");
    await expect(page.locator("#shimmer")).not.toHaveCSS("color", "rgba(0, 0, 0, 0)");
});

for (const direction of ["ltr", "rtl"]) {
    test(`keeps right sheets physically right in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(`
            <style>${bloomCss}</style>
            <div dir="${direction}" data-slot="sheet-overlay" data-state="open">
                <section id="sheet" data-slot="sheet-content" data-side="right" style="--sheet-width: 200px"></section>
            </div>
        `);

        const sheet = await page.locator("#sheet").boundingBox();
        const viewport = await page.evaluate(() => document.documentElement.clientWidth);

        expect(sheet.x + sheet.width).toBeCloseTo(viewport, 5);
    });
}

test("isolates nested overlay motion and lays out Turbo Frame content", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div data-slot="modal-overlay" data-state="open">
            <div data-slot="modal-backdrop"></div>
            <div id="outer" data-slot="modal-positioner">
                <section data-slot="modal-panel">
                    <div data-slot="modal-content">
                        <turbo-frame id="frame" data-modal-frame-owner>
                            <header data-slot="modal-header">Frame content</header>
                        </turbo-frame>
                        <div data-slot="modal-overlay" data-state="closed">
                            <div id="inner" data-slot="modal-positioner"></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    `);

    await expect(page.locator("#frame")).toHaveCSS("display", "grid");
    await expect(page.locator("#outer")).toHaveCSS("opacity", "1");
    await expect(page.locator("#inner")).toHaveCSS("opacity", "0");
});
