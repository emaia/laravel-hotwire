import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

// happy-dom does not deliver MutationObserver callbacks reliably, so the Bun
// suite drives handleCompositionMutations() directly. This covers the wiring
// that delivers it: the observer registered in connect() must notice a
// composing field leaving the DOM and release the save it was blocking.
test("resumes a blocked save when a composing field leaves the DOM", async ({ page }) => {
    await page.setContent(`
        <form data-controller="auto-save" data-auto-save-delay-value="20">
            <input name="title" value="Original">
            <input name="editor" value="">
        </form>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.autoSaveSubmits = 0;
        document.querySelector("form").requestSubmit = () => {
            window.autoSaveSubmits++;
        };

        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("auto-save", window.AutoSaveController);
    });
    await expect.poll(() => page.evaluate(() => Boolean(
        window.StimulusApplication.getControllerForElementAndIdentifier(document.querySelector("form"), "auto-save"),
    ))).toBe(true);

    const editor = page.locator('[name="editor"]');

    // One task in the page: the debounce armed by the edit cannot fire before
    // composition cancels it. Split across two Playwright commands, the round
    // trip between them can outlast the debounce on a loaded machine.
    await page.evaluate(() => {
        const title = document.querySelector('[name="title"]');

        title.value = "Updated";
        title.dispatchEvent(new Event("input", { bubbles: true }));
        document.querySelector('[name="editor"]').dispatchEvent(new Event("compositionstart", { bubbles: true }));
    });

    // Composition holds the pending save past its debounce.
    await page.waitForTimeout(120);
    expect(await page.evaluate(() => window.autoSaveSubmits)).toBe(0);

    // The field never emits compositionend, so only the observer can release it.
    await editor.evaluate((element) => element.remove());

    await expect.poll(() => page.evaluate(() => window.autoSaveSubmits)).toBe(1);
});

async function browserControllerScript() {
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");
    const autoSave = (await readFile("resources/js/controllers/auto_save_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export default class extends Controller", "class AutoSaveController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${composition}
        ${autoSave}
        window.AutoSaveController = AutoSaveController;
    `;
}
