import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture } from "../../scripts/css_build_contract.js";

let bloomCss;

test.beforeAll(async () => {
    const source = (await readFile("stubs/resources/css/app.css", "utf8")).replace(
        "presets/nova.css",
        "presets/bloom.css",
    );

    bloomCss = await compileCssFixture(source);
});

test("gives every field the same offset focus outline", async ({ page }) => {
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
    ];

    const resting = {};

    for (const field of fields) {
        await expect(page.locator(field), `${field} shows an outline before focus`).toHaveCSS("outline-style", "none");
        resting[field] = await page.locator(field).evaluate((element) => getComputedStyle(element).boxShadow);

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

        await expect(page.locator(field), `${field} focus outline style`).toHaveCSS("outline-style", "solid");
        await expect(page.locator(field), `${field} focus outline width`).toHaveCSS("outline-width", "2px");
        await expect(page.locator(field), `${field} focus outline offset`).toHaveCSS("outline-offset", "2px");

        // The outline is the whole focus indicator: focusing must not also grow a ring.
        await expect(page.locator(field), `${field} draws a focus halo behind the outline`).toHaveCSS(
            "box-shadow",
            resting[field],
        );
    }
});

test("focuses a rich text field through its editor", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <input id="input" data-slot="input">
        <div id="rich-text" data-slot="rich-text">
            <div data-slot="rich-text-editor"><div class="ProseMirror" contenteditable="true"></div></div>
        </div>
    `);

    const background = await page.locator("#input").evaluate((element) => getComputedStyle(element).backgroundColor);

    await expect(page.locator("#rich-text")).toHaveCSS("background-color", background);
    await expect(page.locator("#rich-text")).toHaveCSS("outline-style", "none");

    await page.locator("#rich-text .ProseMirror").focus();

    await expect(page.locator("#rich-text")).toHaveCSS("outline-style", "solid");
    await expect(page.locator("#rich-text")).toHaveCSS("outline-width", "2px");
    await expect(page.locator("#rich-text")).toHaveCSS("outline-offset", "2px");
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

test("keeps toggles on the same control metric as buttons", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        ${["default", "sm", "lg"]
            .map(
                (size) => `
                    <button data-slot="button" data-variant="outline" data-size="${size}">Button ${size}</button>
                    <button data-slot="toggle" data-variant="outline" data-size="${size}">Toggle ${size}</button>
                `,
            )
            .join("")}
    `);

    const box = (selector) =>
        page.locator(selector).evaluate((element) => Math.round(element.getBoundingClientRect().height));

    for (const size of ["default", "sm", "lg"]) {
        expect(
            await box(`[data-slot="toggle"][data-size="${size}"]`),
            `toggle ${size} leaves the button control metric`,
        ).toBe(await box(`[data-slot="button"][data-size="${size}"]`));
    }
});

test("keeps sibling affordances on one tier", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <button id="modal-close" data-slot="modal-close-icon"></button>
        <button id="sheet-close" data-slot="sheet-close-icon"></button>
        <button id="toast-close" data-slot="toast-close"></button>
        <span id="badge" data-slot="badge" data-variant="default">Badge</span>
        <kbd id="kbd" data-slot="kbd">K</kbd>
        <div id="progress-track" data-slot="progress-track"></div>
        <div id="carousel-progress" data-slot="carousel-progress-wrapper"></div>
        <div id="slider-track" data-slot="scroll-progress"></div>
        <input id="slider" data-slot="slider" data-orientation="horizontal" type="range">
    `);

    const height = (selector) =>
        page.locator(selector).evaluate((element) => Math.round(element.getBoundingClientRect().height));
    const track = (selector) => page.locator(selector).evaluate((element) => getComputedStyle(element).height);

    // Close affordances read as one control, wherever the overlay comes from.
    expect(await height("#sheet-close"), "sheet close").toBe(await height("#modal-close"));
    expect(await height("#toast-close"), "toast close").toBe(await height("#modal-close"));

    // Badge and Kbd are the same inline chip tier.
    expect(await height("#kbd"), "kbd chip").toBe(await height("#badge"));

    // Both component progress bars share a thickness.
    expect(await track("#carousel-progress"), "carousel progress bar").toBe(await track("#progress-track"));

    // The thin viewport indicator follows the slider track, not the progress bar.
    const sliderTrack = await page.locator("#slider").evaluate((element) => {
        const probe = document.createElement("div");
        probe.style.height = getComputedStyle(element).getPropertyValue("--slider-track-height");
        document.body.append(probe);
        const height = getComputedStyle(probe).height;
        probe.remove();

        return height;
    });

    expect(await track("#slider-track"), "scroll progress bar").toBe(sliderTrack);
});

test("keeps sidebar rows on one navigation tier without widening the icon rail", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <span id="badge" data-slot="badge" data-variant="default">Badge</span>
        <div data-slot="sidebar-wrapper" style="--sidebar-width-icon: 3rem">
            <div data-slot="sidebar" data-collapsible="offcanvas">
                <div id="brand" data-slot="sidebar-brand">Brand</div>
                <div id="group-label" data-slot="sidebar-group-label">Group</div>
                <button id="menu-button" data-slot="sidebar-menu-button" data-size="default">Item</button>
                <div id="skeleton" data-slot="sidebar-menu-skeleton"></div>
                <span id="menu-badge" data-slot="sidebar-menu-badge">3</span>
            </div>
            <div data-slot="sidebar" data-collapsible="icon">
                <button id="collapsed" data-slot="sidebar-menu-button" data-size="default">Item</button>
            </div>
        </div>
    `);

    const height = (selector) =>
        page.locator(selector).evaluate((element) => Math.round(element.getBoundingClientRect().height));

    const row = await height("#menu-button");

    expect(await height("#group-label"), "group label").toBe(row);
    expect(await height("#skeleton"), "menu skeleton").toBe(row);
    expect(await height("#brand"), "brand").toBe(row);
    expect(await height("#menu-badge"), "menu badge").toBe(await height("#badge"));

    // The collapsed rail is sized by a shared PHP default, so the icon row must still fit inside it.
    const collapsed = await page.locator("#collapsed").evaluate((element) => {
        const rail = parseFloat(
            getComputedStyle(document.querySelector('[data-slot="sidebar-wrapper"]')).getPropertyValue(
                "--sidebar-width-icon",
            ),
        );
        const box = element.getBoundingClientRect();

        return { width: box.width, height: box.height, rail: rail * 16 };
    });

    expect(collapsed.width).toBe(collapsed.height);
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

            return { width: style.scrollbarWidth, color: style.scrollbarColor, overflow: style.overflow };
        });

    const styled = await scrollbar();

    // The scroll mechanic is structural; the preset only dresses the bar.
    expect(styled.overflow, "sidebar content stopped scrolling").toBe("auto");
    expect(styled.width).toBe("thin");
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

test("keeps grouped controls on the preset's own outer geometry", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <button id="solo" data-slot="button" data-size="default" data-variant="outline">Solo</button>
        <div data-slot="button-group" data-orientation="horizontal">
            <button id="button-first" data-slot="button" data-size="default" data-variant="outline">A</button>
            <button id="button-last" data-slot="button" data-size="default" data-variant="outline">B</button>
        </div>
        <button id="toggle-solo" data-slot="toggle-group-item" data-size="default">Solo</button>
        <div data-slot="toggle-group" data-connected="true" data-orientation="horizontal">
            <button id="toggle-first" data-slot="toggle-group-item" data-size="default">A</button>
            <button id="toggle-last" data-slot="toggle-group-item" data-size="default">B</button>
        </div>
    `);

    const corners = (selector) =>
        page.locator(selector).evaluate((element) => {
            const style = getComputedStyle(element);

            return [style.borderTopLeftRadius, style.borderTopRightRadius];
        });

    for (const family of ["button", "toggle"]) {
        const solo = family === "button" ? "#solo" : "#toggle-solo";
        const [outer] = await corners(solo);

        expect(await corners(`#${family}-first`), `${family} group leading corner`).toEqual([outer, "0px"]);
        expect(await corners(`#${family}-last`), `${family} group trailing corner`).toEqual(["0px", outer]);
    }
});

test("keeps every text control on one surface metric", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <input id="input" data-slot="input">
        <select id="select" data-slot="select"></select>
        <input id="file" data-slot="file-input" type="file">
        <div id="group" data-slot="input-group"></div>
    `);

    const surface = (selector) =>
        page.locator(selector).evaluate((element) => {
            const style = getComputedStyle(element);

            return [
                Math.round(element.getBoundingClientRect().height),
                style.borderTopLeftRadius,
                style.backgroundColor,
            ];
        });

    const input = await surface("#input");

    for (const selector of ["#select", "#file", "#group"]) {
        expect(await surface(selector), `${selector} leaves the shared control metric`).toEqual(input);
    }
});

test("drops the surface treatment from tab lists that render no surface", async ({ page }) => {
    await page.setContent(`
        <style>${bloomCss}</style>
        <div data-slot="tabs" data-orientation="horizontal">
            <div id="line" data-slot="tabs-list" data-variant="line"></div>
            <div id="surface" data-slot="tabs-list" data-variant="default"></div>
        </div>
        <div id="bare"></div>
    `);

    const surface = (selector) =>
        page.locator(selector).evaluate((element) => {
            const style = getComputedStyle(element);
            const transparent = /rgba\(\s*0,\s*0,\s*0,\s*0\s*\)/;
            const shadows = style.boxShadow === "none" ? [] : style.boxShadow.split(/,(?![^(]*\))/);

            return {
                fill: !transparent.test(style.backgroundColor),
                shadow: shadows.some((shadow) => !transparent.test(shadow)),
            };
        });

    expect(await surface("#line")).toEqual({ fill: false, shadow: false });
    expect(await surface("#surface")).toEqual({ fill: true, shadow: true });
    expect(await surface("#bare")).toEqual({ fill: false, shadow: false });
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

test("disables decorative preset motion when reduced motion is requested", async ({ page }) => {
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
    await expect(page.locator("#spinner")).toHaveCSS("animation-name", "none");
    await expect(page.locator("#pagination-spinner")).toHaveCSS("animation-name", "none");
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
    test(`preserves inline grouping and physical overlays in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(`
            <style>${bloomCss}</style>
            <div dir="${direction}" data-slot="button-group" data-orientation="horizontal">
                <button id="first" data-slot="button" data-size="default" data-variant="outline">First</button>
                <button id="last" data-slot="button" data-size="default" data-variant="outline">Last</button>
            </div>
            <div dir="${direction}" data-slot="sheet-overlay" data-state="open">
                <section id="sheet" data-slot="sheet-content" data-side="right" style="--sheet-width: 200px"></section>
            </div>
        `);

        const first = await page.locator("#first").evaluate((element) => getComputedStyle(element));
        const last = await page.locator("#last").evaluate((element) => getComputedStyle(element));
        const sheet = await page.locator("#sheet").boundingBox();
        const viewport = await page.evaluate(() => document.documentElement.clientWidth);

        if (direction === "ltr") {
            expect(first.borderTopRightRadius).toBe("0px");
            expect(last.borderTopLeftRadius).toBe("0px");
        } else {
            expect(first.borderTopLeftRadius).toBe("0px");
            expect(last.borderTopRightRadius).toBe("0px");
        }
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
