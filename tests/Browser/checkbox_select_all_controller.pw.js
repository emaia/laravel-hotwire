import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("updates native indeterminate state when the value attribute changes", async ({ page }) => {
    await mount(page, `
        <div id="group" data-controller="checkbox-select-all">
            <input type="checkbox" data-checkbox-select-all-target="checkboxAll">
            <input type="checkbox" data-checkbox-select-all-target="checkbox" checked>
            <input type="checkbox" data-checkbox-select-all-target="checkbox">
        </div>
    `);

    const group = page.locator("#group");
    const master = group.locator('[data-checkbox-select-all-target="checkboxAll"]');
    await expect(master).toHaveJSProperty("indeterminate", true);

    await group.evaluate((element) => {
        element.setAttribute("data-checkbox-select-all-disable-indeterminate-value", "true");
    });
    await expect(master).toHaveJSProperty("indeterminate", false);

    await group.evaluate((element) => {
        element.removeAttribute("data-checkbox-select-all-disable-indeterminate-value");
    });
    await expect(master).toHaveJSProperty("indeterminate", true);
});

test("refreshes after native reset and an owning Turbo Frame render", async ({ page }) => {
    await mount(page, `
        <turbo-frame id="checkbox-frame">
            <div id="group" data-controller="checkbox-select-all">
                <form id="checkbox-form">
                    <input type="checkbox" data-checkbox-select-all-target="checkboxAll">
                    <input type="checkbox" data-checkbox-select-all-target="checkbox" checked>
                    <input type="checkbox" data-checkbox-select-all-target="checkbox">
                </form>
            </div>
        </turbo-frame>
    `);

    const frame = page.locator("#checkbox-frame");
    const form = page.locator("#checkbox-form");
    const master = page.locator('[data-checkbox-select-all-target="checkboxAll"]');
    const items = page.locator('[data-checkbox-select-all-target="checkbox"]');
    await expect(master).toHaveJSProperty("indeterminate", true);

    await items.nth(1).check();
    await expect(master).toBeChecked();
    await form.evaluate((element) => element.reset());
    await expect(master).not.toBeChecked();
    await expect(master).toHaveJSProperty("indeterminate", true);

    await items.nth(1).evaluate((checkbox) => (checkbox.checked = true));
    await frame.evaluate((element) => {
        element.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    });
    await expect(master).toBeChecked();
    await expect(master).toHaveJSProperty("indeterminate", false);
});

test("rebinds reset handling when a wrapped form is replaced", async ({ page }) => {
    await mount(page, `
        <div id="group" data-controller="checkbox-select-all">
            <form>
                <input type="checkbox" data-checkbox-select-all-target="checkboxAll">
                <input type="checkbox" data-checkbox-select-all-target="checkbox">
            </form>
        </div>
    `);

    // Rebinding depends on Stimulus target callbacks firing for the replaced subtree, which only a
    // real MutationObserver delivers dependably. The Bun test drives syncForm() directly instead.
    await page.locator("#group").evaluate((element) => {
        element.innerHTML = `
            <form id="replacement-form">
                <input type="checkbox" data-checkbox-select-all-target="checkboxAll">
                <input type="checkbox" data-checkbox-select-all-target="checkbox" checked>
                <input type="checkbox" data-checkbox-select-all-target="checkbox">
            </form>
        `;
    });

    const form = page.locator("#replacement-form");
    const master = page.locator('[data-checkbox-select-all-target="checkboxAll"]');
    const items = page.locator('[data-checkbox-select-all-target="checkbox"]');
    await expect(master).toHaveJSProperty("indeterminate", true);

    await items.nth(1).check();
    await expect(master).toBeChecked();

    await form.evaluate((element) => element.reset());
    await expect(master).not.toBeChecked();
    await expect(master).toHaveJSProperty("indeterminate", true);
});

async function mount(page, html) {
    await page.setContent(html);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("checkbox-select-all", window.CheckboxSelectAllController);
    });
}

async function browserControllerScript() {
    const frameEvents = (await readFile("resources/js/controllers/_frame_events.js", "utf8"))
        .replaceAll("export function ", "function ");
    const controller = (await readFile("resources/js/controllers/checkbox_select_all_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_frame_events\.js";\s*/, "")
        .replace("export default class extends Controller", "class CheckboxSelectAllController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${frameEvents}
        ${controller}
        window.CheckboxSelectAllController = CheckboxSelectAllController;
    `;
}
