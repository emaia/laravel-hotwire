import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture, packageSourceEntrypoint } from "../../scripts/css_build_contract.js";

let foundationCss;
let presetCss;

test.beforeAll(async () => {
    [foundationCss, presetCss] = await Promise.all([
        compileCssFixture(
            packageSourceEntrypoint("../../vendor/emaia/laravel-hotwire/resources/css/foundation.css"),
        ),
        compileCssFixture(await readFile("stubs/resources/css/app.css", "utf8")),
    ]);
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

test("Back to Top mechanics yield to application overrides", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @layer components {
                [data-slot="back-to-top"][data-visible="true"] {
                    position: sticky;
                    opacity: 0.5;
                }
            }
        </style>
        <button id="back-to-top" data-slot="back-to-top" data-visible="true">Top</button>
    `);

    await expect(page.locator("#back-to-top")).toHaveCSS("position", "sticky");
    await expect(page.locator("#back-to-top")).toHaveCSS("opacity", "0.5");
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

test("Presence opt-outs override later visual motion", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <style>
            @keyframes test-motion { from { opacity: 0 } to { opacity: 1 } }
            [data-slot="tooltip"], [data-slot="modal-positioner"] {
                transition: opacity 2s;
                animation: test-motion 2s;
            }
        </style>
        <div id="tooltip" data-slot="tooltip" data-motion="none"></div>
        <div data-slot="modal-overlay" data-presence="instant">
            <div id="modal" data-slot="modal-positioner"></div>
        </div>
    `);

    for (const selector of ["#tooltip", "#modal"]) {
        await expect(page.locator(selector)).toHaveCSS("transition-duration", "0s");
        await expect(page.locator(selector)).toHaveCSS("animation-name", "none");
    }
});

test("reduced motion removes component motion and keeps shimmer text legible", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <style>${presetCss}</style>
        <div id="back-to-top" data-slot="back-to-top" data-visible="true"></div>
        <div id="toast" data-slot="toast"><div id="toast-content" data-slot="toast-content"></div></div>
        <span id="shimmer" data-shimmer="true">Processing</span>
    `);

    await expect(page.locator("#back-to-top")).toHaveCSS("transition-duration", "0s");
    await expect(page.locator("#toast")).toHaveCSS("transition-duration", "0s");
    await expect(page.locator("#toast-content")).toHaveCSS("transition-duration", "0s");
    await expect(page.locator("#shimmer")).toHaveCSS("animation-name", "none");
    await expect(page.locator("#shimmer")).toHaveCSS("background-image", "none");
    await expect(page.locator("#shimmer")).not.toHaveCSS("color", "rgba(0, 0, 0, 0)");
});

test("structural component selectors work before their controllers connect", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <button id="scheme" data-slot="color-scheme-toggle" data-color-scheme-modes-value="light dark system">
            <span id="light-icon" data-slot="color-scheme-icon" data-scheme-icon="light"></span>
            <span id="dark-icon" data-slot="color-scheme-icon" data-scheme-icon="dark"></span>
            <span id="system-icon" data-slot="color-scheme-icon" data-mode-icon="system"></span>
        </button>
        <span id="input-wrapper" data-slot="input-wrapper" data-clearable="true">
            <input id="clearable" class="clear-input--touched" data-slot="input" data-clear-input-target="input" value="Search">
            <button id="clear" class="hidden" data-slot="clear-input-button" data-clear-input-target="clearButton">Clear</button>
        </span>
        <div id="embed" data-slot="oembed"><iframe id="frame" data-slot="oembed-frame"></iframe></div>
    `);

    await page.locator("html").evaluate((element) => {
        element.dataset.colorSchemeMode = "dark";
    });
    await expect(page.locator("#light-icon")).toHaveCSS("display", "none");
    await expect(page.locator("#dark-icon")).toHaveCSS("display", "block");
    await expect(page.locator("#system-icon")).toHaveCSS("display", "none");

    await expect(page.locator("#input-wrapper")).toHaveCSS("position", "relative");
    await page.locator("#clearable").hover();
    await expect(page.locator("#clear")).toBeVisible();

    const clearInputCenters = await page.locator("#input-wrapper").evaluate((wrapper) => {
        const input = wrapper.querySelector('[data-slot="input"]').getBoundingClientRect();
        const button = wrapper.querySelector('[data-slot="clear-input-button"]').getBoundingClientRect();

        return [input.y + input.height / 2, button.y + button.height / 2];
    });

    expect(Math.abs(clearInputCenters[0] - clearInputCenters[1])).toBeLessThanOrEqual(1);

    const geometry = await page.locator("#embed").evaluate((embed) => {
        const frame = embed.querySelector("iframe");

        return {
            ratio: getComputedStyle(embed).aspectRatio,
            clipped: getComputedStyle(embed).overflow,
            sameWidth: frame.getBoundingClientRect().width === embed.getBoundingClientRect().width,
            sameHeight: frame.getBoundingClientRect().height === embed.getBoundingClientRect().height,
        };
    });

    expect(geometry).toEqual({ ratio: "16 / 9", clipped: "hidden", sameWidth: true, sameHeight: true });
});

test("OEmbed inherits an application aspect ratio without losing structural geometry", async ({ page }) => {
    await page.setContent(`
        <style>${presetCss}</style>
        <div style="--oembed-aspect-ratio: 4 / 3">
            <div id="embed" data-slot="oembed"><iframe data-slot="oembed-frame"></iframe></div>
        </div>
    `);

    const geometry = await page.locator("#embed").evaluate((embed) => {
        const frame = embed.querySelector("iframe");

        return {
            ratio: getComputedStyle(embed).aspectRatio,
            clipped: getComputedStyle(embed).overflow,
            sameWidth: frame.getBoundingClientRect().width === embed.getBoundingClientRect().width,
            sameHeight: frame.getBoundingClientRect().height === embed.getBoundingClientRect().height,
        };
    });

    expect(geometry).toEqual({ ratio: "4 / 3", clipped: "hidden", sameWidth: true, sameHeight: true });
});

test("clear input visibility supports structural-only slot and target hooks", async ({ page }) => {
    await page.setContent(`
        <style>${foundationCss}</style>
        <span>
            <input id="slot-input" class="clear-input--touched" value="Slot">
            <button id="slot-clear" class="hidden" data-slot="clear-input-button">Clear slot</button>
        </span>
        <span>
            <input id="target-input" class="clear-input--touched" value="Target">
            <button id="target-clear" class="hidden" data-clear-input-target="clearButton">Clear target</button>
        </span>
        <span style="--clear-input-button-display: grid">
            <input id="custom-input" class="clear-input--touched" value="Custom">
            <button id="custom-clear" class="hidden" data-slot="clear-input-button">Clear custom</button>
        </span>
    `);

    for (const hook of ["slot", "target", "custom"]) {
        const button = page.locator(`#${hook}-clear`);

        await expect(button).toBeHidden();
        await page.locator(`#${hook}-input`).hover();
        await expect(button).toBeVisible();
    }

    await expect(page.locator("#slot-clear")).toHaveCSS("--clear-input-button-display", "");
    await expect(page.locator("#custom-clear")).toHaveCSS("display", "grid");
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
