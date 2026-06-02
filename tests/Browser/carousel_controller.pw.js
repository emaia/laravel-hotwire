import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

// Regression: a `will-change: transform` (or any composited layer) on the
// container at init corrupts Embla's measurements when the carousel is offset on
// the page (centered in a max-width wrapper on a wide screen), breaking the loop
// so the wrapped slide overlaps the first. The carousel.css must not do that —
// the rendered layout must be identical whether the carousel is flush-left or
// centered, with no slide overlapping the first.

async function render(page, wrapperStyle) {
    const css = await readFile("resources/js/controllers/carousel.css", "utf8");

    await page.setViewportSize({ width: 1920, height: 900 });
    await page.setContent(`
        <style>* { box-sizing: border-box; margin: 0; padding: 0; } ${css}</style>
        <div style="${wrapperStyle}">
            <div data-controller="carousel" data-carousel-axis="x" style="--carousel-slide-size:70%;--carousel-slide-spacing:1rem">
                <div data-carousel-target="viewport"><div data-carousel-target="container">
                    ${Array.from({ length: 6 }, () => `<div><div style="height:200px"></div></div>`).join("")}
                </div></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/embla-carousel/embla-carousel.umd.js" });

    return page.evaluate(() => {
        const viewport = document.querySelector('[data-carousel-target="viewport"]');
        const embla = window.EmblaCarousel(viewport, { loop: true, align: "center", axis: "x" });
        const v = viewport.getBoundingClientRect();
        const slides = embla.slideNodes();
        const first = slides[0].getBoundingClientRect();
        // Largest right edge among slides sitting to the left of the first slide.
        let leftNeighborRight = null;
        for (const s of slides) {
            const r = s.getBoundingClientRect();
            if (r.right <= first.left + 1 && (leftNeighborRight == null || r.right > leftNeighborRight)) {
                leftNeighborRight = r.right;
            }
        }
        return {
            slide0Left: Math.round(first.left - v.left),
            overlap: leftNeighborRight == null ? null : Math.round(leftNeighborRight - first.left),
        };
    });
}

test("loop layout is offset-independent and does not overlap when centered", async ({ page }) => {
    const flush = await render(page, "max-width:1600px");
    const centered = await render(page, "max-width:1600px; margin:0 auto");

    // The selected slide sits in the same spot relative to its viewport either way.
    expect(centered.slide0Left).toBe(flush.slide0Left);

    // A wrapped slide fills the left side without overlapping the first slide.
    expect(centered.overlap).not.toBeNull();
    expect(centered.overlap).toBeLessThanOrEqual(1);
});
