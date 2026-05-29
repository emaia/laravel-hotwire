import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("moves focus with arrow keys and activates the focused tab", async ({ page }) => {
    await page.setContent(`
        <div data-controller="tabs">
            <div role="tablist"
                 data-action="click->tabs#select keydown->tabs#navigate">
                <button role="tab" id="t1" aria-controls="p1" data-tabs-target="tab">One</button>
                <button role="tab" id="t2" aria-controls="p2" data-tabs-target="tab">Two</button>
                <button role="tab" id="t3" aria-controls="p3" data-tabs-target="tab">Three</button>
            </div>
            <div role="tabpanel" id="p1" data-tabs-target="panel" tabindex="0">P1</div>
            <div role="tabpanel" id="p2" data-tabs-target="panel" tabindex="0">P2</div>
            <div role="tabpanel" id="p3" data-tabs-target="panel" tabindex="0">P3</div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/tabs_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("tabs", window.TabsController);
    });

    const tabs = page.locator('[role="tab"]');
    const panels = page.locator('[role="tabpanel"]');

    await tabs.nth(0).focus();
    await page.keyboard.press("ArrowRight");

    await expect(tabs.nth(1)).toBeFocused();
    await expect(tabs.nth(1)).toHaveAttribute("aria-selected", "true");
    await expect(panels.nth(1)).toBeVisible();
    await expect(panels.nth(0)).toBeHidden();

    await page.keyboard.press("End");
    await expect(tabs.nth(2)).toBeFocused();
    await expect(panels.nth(2)).toBeVisible();

    await page.keyboard.press("Home");
    await expect(tabs.nth(0)).toBeFocused();
    await expect(panels.nth(0)).toBeVisible();
});

async function browserControllerScript(path) {
    const source = await readFile(path, "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class TabsController extends Controller")
        .concat("\nwindow.TabsController = TabsController;\n");
}
