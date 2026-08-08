import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("inert keeps the hidden button out of focus order and restores interaction when visible", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <button id="before">Before</button>
        <button
            id="back-to-top"
            type="button"
            style="position: fixed; right: 1rem; bottom: 1rem"
            data-controller="back-to-top"
            data-action="back-to-top#scrollToTop"
            data-back-to-top-threshold-value="200"
            data-visible="false"
            inert
        >Back to top</button>
        <button id="after">After</button>
        <div style="height: 2000px"></div>
    `);

    await installController(page);

    const button = page.locator("#back-to-top");
    await expect(button).toHaveAttribute("data-visible", "false");
    expect(await button.evaluate((element) => element.hasAttribute("inert"))).toBe(true);

    await page.locator("#before").focus();
    await page.keyboard.press("Tab");
    await expect(page.locator("#after")).toBeFocused();

    await page.evaluate(() => window.scrollTo(0, 500));
    await expect(button).toHaveAttribute("data-visible", "true");
    expect(await button.evaluate((element) => element.hasAttribute("inert"))).toBe(false);

    await button.focus();
    await expect(button).toBeFocused();
    await button.click();

    await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(0);
    await expect(button).toHaveAttribute("data-visible", "false");
    expect(await button.evaluate((element) => element.hasAttribute("inert"))).toBe(true);
});

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });

    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("back-to-top", window.BackToTopController);
    });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/back_to_top_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class BackToTopController extends Controller")
        .concat("\nwindow.BackToTopController = BackToTopController;\n");
}
