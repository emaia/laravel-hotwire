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
        <div id="alert" popover="manual" data-hotwire-top-layer data-slot="alert-dialog-overlay"></div>
        <div id="dropdown" popover="manual" data-hotwire-top-layer data-slot="dropdown-menu"></div>
        <div id="sticky" data-slot="sticky"></div>
    `);

    await page.locator("#alert").evaluate((element) => element.showPopover());
    await page.locator("#dropdown").evaluate((element) => element.showPopover());

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

test("Side Panel mechanics yield to application overrides", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="side-panel"]::before { inline-size: 3px; }
            }
        </style>
        <div data-slot="side-panel" data-state="expanded" data-side="left">
            <button data-slot="side-panel-trigger">Toggle</button>
        </div>
    `);

    const railWidth = await page
        .locator('[data-slot="side-panel"]')
        .evaluate((element) => getComputedStyle(element, "::before").inlineSize);

    expect(railWidth).toBe("3px");
});

test("compiled structural motion inherits application timing from visual roots", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="side-panel"] {
                    --side-panel-motion-duration: 900ms;
                    --side-panel-content-motion-duration: 700ms;
                    --side-panel-motion-easing: linear;
                }
                [data-slot="read-more"] {
                    --read-more-motion-duration: 1200ms;
                    --read-more-motion-easing: ease-in;
                }
            }
        </style>
        ${motionFixture()}
    `);

    const timing = await page.locator("body").evaluate(() => {
        const sidePanel = document.querySelector('[data-slot="side-panel"]');
        const styles = [
            getComputedStyle(sidePanel, "::before"),
            getComputedStyle(document.querySelector('[data-slot="side-panel-panel"]')),
            getComputedStyle(document.querySelector('[data-slot="side-panel-panel-content"]')),
            getComputedStyle(document.querySelector('[data-slot="side-panel-trigger"]')),
            getComputedStyle(document.querySelector('[data-slot="side-panel-trigger-icon"]')),
            getComputedStyle(document.querySelector('[data-slot="read-more-viewport"]')),
        ];

        return styles.map((style) => [style.transitionDuration, style.transitionTimingFunction]);
    });

    expect(timing).toEqual([
        ["0.9s", "linear"],
        ["0.9s", "linear"],
        ["0.7s", "linear"],
        ["0.9s", "linear"],
        ["0.9s", "linear"],
        ["1.2s", "ease-in"],
    ]);
});

test("compiled reduced-motion rules disable the complete structural motion system", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            [data-slot="side-panel"]::before,
            [data-slot="side-panel-panel"],
            [data-slot="side-panel-panel-content"],
            [data-slot="side-panel-trigger"],
            [data-slot="side-panel-trigger-icon"],
            [data-slot="read-more-viewport"] {
                transition-duration: 900ms;
            }
        </style>
        ${motionFixture()}
    `);

    const durations = await page.locator("body").evaluate(() => {
        const sidePanel = document.querySelector('[data-slot="side-panel"]');

        return [
            getComputedStyle(sidePanel, "::before"),
            getComputedStyle(document.querySelector('[data-slot="side-panel-panel"]')),
            getComputedStyle(document.querySelector('[data-slot="side-panel-panel-content"]')),
            getComputedStyle(document.querySelector('[data-slot="side-panel-trigger"]')),
            getComputedStyle(document.querySelector('[data-slot="side-panel-trigger-icon"]')),
            getComputedStyle(document.querySelector('[data-slot="read-more-viewport"]')),
            getComputedStyle(document.querySelector('[data-slot="read-more-trigger-icon"]')),
        ].map((style) => style.transitionDuration);
    });

    expect(durations).toEqual(["0s", "0s", "0s", "0s", "0s", "0s", "0s"]);
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

function motionFixture() {
    return `
        <div data-slot="side-panel" data-state="expanded" data-side="left">
            <aside data-slot="side-panel-panel">
                <div data-slot="side-panel-panel-content">Panel</div>
            </aside>
            <button data-slot="side-panel-trigger">
                <span data-slot="side-panel-trigger-icon"></span>
            </button>
        </div>
        <section data-slot="read-more" data-state="collapsed">
            <div data-slot="read-more-viewport"></div>
            <span data-slot="read-more-trigger-icon"></span>
        </section>
    `;
}
