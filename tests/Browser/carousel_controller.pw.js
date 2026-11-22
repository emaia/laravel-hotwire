import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

// Regression: a `will-change: transform` (or any composited layer) on the
// container at init corrupts Embla's measurements when the carousel is offset on
// the page (centered in a max-width wrapper on a wide screen), breaking the loop
// so the wrapped slide overlaps the first. The structural stylesheet must not do that —
// the rendered layout must be identical whether the carousel is flush-left or
// centered, with no slide overlapping the first.

async function render(page, wrapperStyle) {
    const css = await readFile("resources/css/structural.css", "utf8");

    await page.setViewportSize({ width: 1920, height: 900 });
    await page.setContent(`
        <style>* { box-sizing: border-box; margin: 0; padding: 0; } ${css}</style>
        <div style="${wrapperStyle}">
            <div data-controller="carousel" data-carousel-axis="x" style="--carousel-slide-size:70%;--carousel-slide-spacing:1rem">
                <div data-carousel-viewport><div data-carousel-container>
                    ${Array.from({ length: 6 }, () => `<div><div style="height:200px"></div></div>`).join("")}
                </div></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/embla-carousel/embla-carousel.umd.js" });

    return page.evaluate(() => {
        const viewport = document.querySelector("[data-carousel-viewport]");
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

test("--carousel-slide-size is overridable by an app class (no default rule to fight)", async ({ page }) => {
    const css = await readFile("resources/css/structural.css", "utf8");
    await page.setViewportSize({ width: 1200, height: 800 });

    async function slideWidth(rootClass) {
        await page.setContent(`
            <style>* { box-sizing: border-box; margin: 0; padding: 0; }
            .half { --carousel-slide-size: 50%; }
            ${css}</style>
            <div data-controller="carousel" data-carousel-axis="x" class="${rootClass}">
                <div data-carousel-viewport><div data-carousel-container>
                    <div><div style="height:100px"></div></div>
                    <div><div style="height:100px"></div></div>
                </div></div>
            </div>
        `);
        return page.evaluate(() =>
            Math.round(document.querySelector("[data-carousel-container] > *").getBoundingClientRect().width),
        );
    }

    expect(await slideWidth("")).toBeGreaterThan(1000); // fallback ~100%
    expect(await slideWidth("half")).toBeLessThan(700); // class wins → ~50%
});

test("inherits RTL from computed CSS for both layout and Embla", async ({ page }) => {
    const css = await readFile("resources/css/structural.css", "utf8");
    await page.setContent(`
        <style>${css}</style>
        <main dir="ltr" style="direction: rtl">
            ${carouselMarkup()}
        </main>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/embla-carousel/embla-carousel.umd.js" });
    await page.evaluate(() => {
        const createEmbla = window.EmblaCarousel;
        window.emblaOptions = [];
        window.EmblaCarousel = (node, options, plugins) => {
            window.emblaOptions.push(options);

            return createEmbla(node, options, plugins);
        };
    });
    await page.addScriptTag({ content: await browserCarouselScript() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("carousel", window.CarouselController);
    });
    await page.waitForFunction(() => window.emblaOptions.length > 0);

    const result = await page.locator("#carousel").evaluate((root) => ({
        cssDirection: getComputedStyle(root).direction,
        emblaDirection: window.emblaOptions[0].direction,
    }));

    expect(result).toEqual({ cssDirection: "rtl", emblaDirection: "rtl" });
});

test("Turbo morph restores Embla at the selected snap when its managed transform resets", async ({ page }) => {
    const css = await readFile("resources/css/structural.css", "utf8");

    await page.setViewportSize({ width: 800, height: 600 });
    await page.setContent(`
        <style>
            * { box-sizing: border-box; }
            ${css}
            #carousel { width: 300px; --carousel-slide-size: 100%; }
        </style>
        ${carouselMarkup()}
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/embla-carousel/embla-carousel.umd.js" });
    await page.addScriptTag({ content: await browserCarouselScript() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("carousel", window.CarouselController);
    });
    await page.waitForFunction(() => {
        const root = document.querySelector("#carousel");
        return window.app.getControllerForElementAndIdentifier(root, "carousel")?.embla;
    });

    const result = await page.evaluate(async (replacementMarkup) => {
        const root = document.querySelector("#carousel");
        const controller = window.app.getControllerForElementAndIdentifier(root, "carousel");
        const container = document.querySelector("#carousel-container");
        const slide = document.querySelector("#slide-2");

        controller.embla.scrollTo(1, true);
        const transformBefore = container.style.transform;

        const template = document.createElement("template");
        template.innerHTML = replacementMarkup;
        window.Turbo.morphElements(root, template.content.firstElementChild);
        await new Promise((resolve) => setTimeout(resolve, 0));

        return {
            containerPreserved: document.querySelector("#carousel-container") === container,
            slidePreserved: document.querySelector("#slide-2") === slide,
            selected: controller.embla.selectedScrollSnap(),
            transformBefore,
            transformAfter: container.style.transform,
        };
    }, carouselMarkup());

    expect(result.containerPreserved).toBe(true);
    expect(result.slidePreserved).toBe(true);
    expect(result.selected).toBe(1);
    expect(result.transformBefore).not.toBe("");
    expect(result.transformAfter).not.toBe("");
});

test("an Embla resize keeps the focused dot when pagination has not changed", async ({ page }) => {
    const css = await readFile("resources/css/structural.css", "utf8");
    await page.setContent(`
        <style>
            ${css}
            #carousel { width: 500px; --carousel-slide-size: 100%; }
            #carousel-container > div { min-height: 40px; }
        </style>
        ${carouselMarkup(`
            <div data-carousel-target="dotList"></div>
            <template data-carousel-target="dotTemplate">
                <button type="button" data-action="carousel#scrollTo">Slide</button>
            </template>
        `)}
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/embla-carousel/embla-carousel.umd.js" });
    await page.addScriptTag({ content: await browserCarouselScript() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("carousel", window.CarouselController);
    });

    const second = page.getByRole("button", { name: "Go to slide 2" });
    await second.focus();
    await page.keyboard.press("Enter");
    await expect(second).toHaveAttribute("aria-current", "true");

    const preserved = await second.evaluate(async (dot) => {
        const root = document.querySelector("#carousel");
        const embla = window.app.getControllerForElementAndIdentifier(root, "carousel").embla;
        const resized = new Promise((resolve) => embla.on("reInit", resolve));
        root.style.width = "400px";
        await resized;

        return dot.isConnected && document.activeElement === dot;
    });

    expect(preserved).toBe(true);
    await expect(second).toHaveAttribute("aria-current", "true");
    const third = page.getByRole("button", { name: "Go to slide 3" });
    await third.click();
    await expect(third).toHaveAttribute("aria-current", "true");
});

function carouselMarkup(controls = "") {
    return `
        <div id="carousel" data-controller="carousel" data-carousel-options-value='{}'>
            <div id="carousel-viewport" data-carousel-viewport>
                <div id="carousel-container" data-carousel-container>
                    <div id="slide-1">Slide 1</div>
                    <div id="slide-2">Slide 2</div>
                    <div id="slide-3">Slide 3</div>
                </div>
            </div>
            ${controls}
        </div>
    `;
}

async function browserCarouselScript() {
    const recovery = (await readFile("resources/js/controllers/_turbo_morph_recovery.js", "utf8")).replace(
        "export function attachMorphRecovery",
        "function attachMorphRecovery",
    );
    const controller = (await readFile("resources/js/controllers/carousel_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace('import EmblaCarousel from "embla-carousel";', "const EmblaCarousel = window.EmblaCarousel;")
        .replace(/import \{ attachMorphRecovery \} from "\.\/_turbo_morph_recovery\.js";\s*/, "")
        .replace("export default class extends Controller", "class CarouselController extends Controller");

    return `${recovery}\n${controller}\nwindow.CarouselController = CarouselController;`;
}
