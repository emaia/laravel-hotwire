import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture } from "../../scripts/css_build_contract.js";

let presetCss;

test.beforeAll(async () => {
    presetCss = await compileCssFixture(await readFile("stubs/resources/css/app.css", "utf8"));
});

test("structural top-layer resets yield to preset and application styles", async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="sticky"] { z-index: 99; }
                [data-slot="dropdown-menu"] { border: 5px solid red; }
            }
        </style>
        <div id="modal" popover="manual" data-hotwire-top-layer data-slot="modal-overlay"></div>
        <div id="alert" popover="manual" data-hotwire-top-layer data-slot="alert-dialog-overlay"></div>
        <div id="dropdown" popover="manual" data-hotwire-top-layer data-slot="dropdown-menu"></div>
        <div id="sticky" data-slot="sticky"></div>
    `);

    await page.locator("#modal").evaluate((element) => element.showPopover());
    await page.locator("#alert").evaluate((element) => element.showPopover());
    await page.locator("#dropdown").evaluate((element) => element.showPopover());

    await expect(page.locator("#modal")).toHaveCSS("padding", "40px");
    await expect(page.locator("#alert")).toHaveCSS("padding", "16px");
    await expect(page.locator("#alert")).toHaveCSS("overflow-y", "auto");
    await expect(page.locator("#dropdown")).toHaveCSS("border-left-width", "5px");
    await expect(page.locator("#sticky")).toHaveCSS("z-index", "99");
});

test("centered toasts stay centered when their visual width changes", async ({ page }) => {
    await page.setViewportSize({ width: 1000, height: 720 });
    await page.setContent(`
        <style>${presetCss}</style>
        <div id="toast" data-slot="toast" data-position="top-center">Toast</div>
    `);

    const box = await page.locator("#toast").boundingBox();

    expect(box.x + box.width / 2).toBe(500);
});

test("Side Panel mechanics preserve the preset transition contract", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="side-panel"]::before { inline-size: 3px; }
            }
        </style>
        <div data-slot="side-panel" data-state="expanded" data-side="left">
            <button id="trigger" data-slot="side-panel-trigger">Toggle</button>
        </div>
    `);

    await expect(page.locator("#trigger")).toHaveCSS(
        "transition-property",
        "left, right, color, background-color, box-shadow",
    );

    const railWidth = await page
        .locator('[data-slot="side-panel"]')
        .evaluate((element) => getComputedStyle(element, "::before").inlineSize);

    expect(railWidth).toBe("3px");
});

test("Accordion mechanics yield to later preset timing", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="accordion-item"]::details-content { transition-duration: 2s; }
            }
        </style>
        <details id="item" data-slot="accordion-item">
            <summary>Question</summary>
            Answer
        </details>
    `);

    const duration = await page
        .locator("#item")
        .evaluate((element) => getComputedStyle(element, "::details-content").transitionDuration);

    expect(duration).toBe("2s");
});

test("Accordion reduced motion overrides layered preset timing", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="accordion-item"]::details-content { transition-duration: 2s; }
            }
        </style>
        <details id="item" data-slot="accordion-item">
            <summary>Question</summary>
            Answer
        </details>
    `);

    const duration = await page
        .locator("#item")
        .evaluate((element) => getComputedStyle(element, "::details-content").transitionDuration);

    expect(duration).toBe("0s");
});

test("native Select options do not mark a Field card as selected", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <label id="select-card" data-slot="field-label">
            <span data-slot="field">
                <select data-slot="select"><option selected>Pro</option></select>
            </span>
        </label>
        <label id="text-card" data-slot="field-label">
            <span data-slot="field"><input data-slot="input"></span>
        </label>
        <label id="checked-card" data-slot="field-label">
            <span data-slot="field"><input type="checkbox" checked></span>
        </label>
    `);

    const colors = await page.locator("#select-card, #text-card, #checked-card").evaluateAll((cards) =>
        cards.map((card) => {
            const style = getComputedStyle(card);

            return [style.backgroundColor, style.borderColor];
        }),
    );

    expect(colors[0]).toEqual(colors[1]);
    expect(colors[2]).not.toEqual(colors[1]);
});

test("Reveal fallback keyframes yield to later preset definitions", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                @keyframes hotwire-reveal-rise {
                    from { opacity: 0.4; }
                    to { opacity: 0.4; }
                }
            }

            [data-reveal-item] { animation-play-state: paused; }
        </style>
        <div id="item" data-reveal-item>Content</div>
    `);

    const opacityKeyframes = await page.locator("#item").evaluate((element) =>
        element
            .getAnimations()[0]
            .effect.getKeyframes()
            .map((keyframe) => keyframe.opacity),
    );

    expect(opacityKeyframes).toEqual(["0.4", "0.4"]);
});
