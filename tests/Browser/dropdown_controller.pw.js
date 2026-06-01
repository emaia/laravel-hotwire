import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens with a transition, closes on Escape and restores focus", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            .t-enter, .t-leave { transition: opacity 60ms linear; }
            .op0 { opacity: 0; }
            .op100 { opacity: 1; }
        </style>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" class="hidden"
                 data-transition-enter="t-enter" data-transition-enter-from="op0" data-transition-enter-to="op100"
                 data-transition-leave="t-leave" data-transition-leave-from="op100" data-transition-leave-to="op0">
                <a href="#item">Item</a>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("dropdown", window.DropdownController);
    });

    const trigger = page.locator('[data-dropdown-target="trigger"]');
    const menu = page.locator('[data-dropdown-target="menu"]');

    // Assert only stable end states — the transient enter/leave class choreography
    // is covered deterministically by the _transition.js unit tests.
    await trigger.click();
    await expect(menu).toBeVisible(); // hidden removed → display restored
    await expect(trigger).toHaveAttribute("aria-expanded", "true");

    await page.keyboard.press("Escape");
    await expect(menu).toBeHidden(); // leave transition completes and re-adds hidden
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute("aria-expanded", "false");
});

test("closes when clicking outside", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" class="hidden"><a href="#item">Item</a></div>
        </div>
        <button id="outside">Outside</button>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate(() => {
        window.Stimulus.Application.start().register("dropdown", window.DropdownController);
    });

    const menu = page.locator('[data-dropdown-target="menu"]');

    await page.locator('[data-dropdown-target="trigger"]').click();
    await expect(menu).toBeVisible();

    await page.locator("#outside").click();
    await expect(menu).toBeHidden();
});

async function bundle() {
    const transition = (await readFile("resources/js/controllers/_transition.js", "utf8")).replaceAll(
        "export function",
        "function",
    );
    const controller = (await readFile("resources/js/controllers/dropdown_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_transition\.js";/, "")
        .replace("export default class extends Controller", "class DropdownController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${transition}
        ${controller}
        window.DropdownController = DropdownController;
    `;
}
