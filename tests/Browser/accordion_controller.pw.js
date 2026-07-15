import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("coordinates native details toggles in the browser", async ({ page }) => {
    await page.setContent(`
        <section data-controller="accordion" data-accordion-type-value="single">
            <details data-accordion-target="item" data-value="shipping">
                <summary id="shipping-summary">Shipping</summary>
                <section>Shipping answers.</section>
            </details>
            <details data-accordion-target="item" data-value="billing">
                <summary id="billing-summary">Billing</summary>
                <section>Billing answers.</section>
            </details>
            <details data-accordion-target="item" data-value="enterprise" aria-disabled="true">
                <summary id="enterprise-summary">Enterprise</summary>
                <section>Enterprise answers.</section>
            </details>
        </section>
    `);

    await installController(page);

    const shipping = page.locator('details[data-value="shipping"]');
    const billing = page.locator('details[data-value="billing"]');
    const enterprise = page.locator('details[data-value="enterprise"]');

    await page.locator("#shipping-summary").click();
    await expect(shipping).toHaveJSProperty("open", true);
    await expect(billing).toHaveJSProperty("open", false);

    await page.locator("#billing-summary").click();
    await expect(shipping).toHaveJSProperty("open", false);
    await expect(billing).toHaveJSProperty("open", true);

    await page.locator("#enterprise-summary").click();
    await expect(enterprise).toHaveJSProperty("open", false);
});

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await bundle() });

    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("accordion", window.AccordionController);
    });
}

async function bundle() {
    const controller = (await readFile("resources/js/controllers/accordion_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace("export default class extends Controller", "class AccordionController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${controller}
        window.AccordionController = AccordionController;
    `;
}
