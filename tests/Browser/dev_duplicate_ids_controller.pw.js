import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("warns when a stream-like insertion duplicates a model-derived id", async ({ page }) => {
    const warnings = [];
    page.on("console", (message) => {
        if (message.type() === "warning") warnings.push(message.text());
    });

    await page.setContent(`
        <main data-controller="dev--duplicate-ids">
            <div id="dropdown_task_42"></div>
        </main>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("dev--duplicate-ids", window.DevDuplicateIdsController);
    });

    await page.locator("main").evaluate((root) => {
        root.insertAdjacentHTML("beforeend", '<div id="dropdown_task_42"></div>');
    });

    await expect.poll(() => warnings).toContainEqual(
        expect.stringContaining('Duplicate DOM id "dropdown_task_42"'),
    );
});

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/dev/duplicate_ids_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class DevDuplicateIdsController extends Controller")
        .concat("\nwindow.DevDuplicateIdsController = DevDuplicateIdsController;\n");
}
