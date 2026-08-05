import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("native slider interactions keep the visual fill synchronized", async ({ page }) => {
    await page.setContent(`
        <style>
            input[type="range"] { width: 200px; }
            #vertical { width: 20px; height: 160px; writing-mode: vertical-lr; direction: rtl; }
        </style>
        <form id="sliders">
            <input
                id="horizontal"
                type="range"
                min="0"
                max="100"
                value="25"
                data-slot="slider"
                data-orientation="horizontal"
                data-controller="slider"
                data-action="input->slider#update"
            >
            <input
                id="rtl"
                type="range"
                min="0"
                max="100"
                value="25"
                dir="rtl"
                data-slot="slider"
                data-orientation="horizontal"
                data-controller="slider"
                data-action="input->slider#update"
            >
            <input
                id="vertical"
                type="range"
                min="0"
                max="100"
                value="50"
                data-slot="slider"
                data-orientation="vertical"
                data-controller="slider"
                data-action="input->slider#update"
            >
        </form>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("slider", window.SliderController);
    });

    const horizontal = page.locator("#horizontal");
    const rtl = page.locator("#rtl");
    const vertical = page.locator("#vertical");
    await expect.poll(() => fill(horizontal)).toBe(25);
    await expect.poll(() => fill(rtl)).toBe(25);
    await expect.poll(() => fill(vertical)).toBe(50);

    await horizontal.focus();
    await page.keyboard.press("ArrowRight");
    await expect(horizontal).toHaveValue("26");
    expect(await fill(horizontal)).toBe(26);

    await rtl.focus();
    await page.keyboard.press("ArrowRight");
    expect(await fill(rtl)).toBe(Number(await rtl.inputValue()));

    await vertical.focus();
    await page.keyboard.press("ArrowUp");
    await expect(vertical).toHaveValue("51");
    expect(await fill(vertical)).toBe(51);

    const box = await horizontal.boundingBox();
    await page.mouse.click(box.x + box.width * 0.75, box.y + box.height / 2);
    expect(Number(await horizontal.inputValue())).toBeGreaterThan(70);
    expect(await fill(horizontal)).toBe(Number(await horizontal.inputValue()));

    await page.locator("#sliders").evaluate((form) => form.reset());
    await expect(horizontal).toHaveValue("25");
    await expect.poll(() => fill(horizontal)).toBe(25);

    await horizontal.evaluate((element) => {
        window.sliderNode = element;
        element.value = "80";
        element.dispatchEvent(new Event("input", { bubbles: true }));

        const replacement = element.cloneNode();
        replacement.removeAttribute("style");
        replacement.setAttribute("value", "60");
        window.Turbo.morphElements(element, replacement);
    });

    expect(await horizontal.evaluate((element) => element === window.sliderNode)).toBe(true);
    await expect.poll(() => fill(horizontal)).toBe(Number(await horizontal.inputValue()));
});

async function fill(locator) {
    return locator.evaluate((element) =>
        Number.parseFloat(element.style.getPropertyValue("--slider-value")),
    );
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/slider_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class SliderController extends Controller")
        .concat("\nwindow.SliderController = SliderController;\n");
}
