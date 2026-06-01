import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("keeps the caret in place while sanitizing mid-string edits", async ({ page }) => {
    await page.setContent(`
        <div data-controller="slug">
            <input name="title" data-slug-target="source" />
            <input name="slug" data-slug-target="slug" />
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/slug_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("slug", window.SlugController);
    });

    const slug = page.locator('[data-slug-target="slug"]');

    await slug.focus();
    await page.keyboard.type("foobar");

    // Move the caret to just after "foo" and insert a space.
    await page.keyboard.press("ArrowLeft");
    await page.keyboard.press("ArrowLeft");
    await page.keyboard.press("ArrowLeft");
    await page.keyboard.type(" ");

    await expect(slug).toHaveValue("foo-bar");
    expect(await slug.evaluate((el) => el.selectionStart)).toBe(4);

    // Typing continues from the caret, not from the end of the field.
    await page.keyboard.type("X");
    await expect(slug).toHaveValue("foo-xbar");
});

async function browserControllerScript(path) {
    const source = await readFile(path, "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class SlugController extends Controller")
        .concat("\nwindow.SlugController = SlugController;\n");
}
