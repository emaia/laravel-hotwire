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

    await installControllers(page);

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

    await installControllers(page);

    const menu = page.locator('[data-dropdown-target="menu"]');

    await page.locator('[data-dropdown-target="trigger"]').click();
    await expect(menu).toBeVisible();

    await page.locator("#outside").click();
    await expect(menu).toBeHidden();
});

test("positions the menu with Floating UI", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            body { margin: 0; }
            [data-dropdown-target="trigger"] { margin-left: 120px; margin-top: 80px; width: 160px; height: 32px; }
            [data-dropdown-target="menu"] { width: var(--anchor-width); min-width: 8rem; }
        </style>
        <div data-controller="dropdown" data-dropdown-side-offset-value="4">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" class="hidden"><a href="#item">Item</a></div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-dropdown-target="trigger"]');
    const menu = page.locator('[data-dropdown-target="menu"]');

    await trigger.click();
    await expect(menu).toBeVisible();
    await expect(menu).toHaveAttribute("data-side", "bottom");

    const triggerBox = await trigger.boundingBox();
    const menuBox = await menu.boundingBox();

    expect(Math.round(menuBox.y)).toBeGreaterThanOrEqual(Math.round(triggerBox.y + triggerBox.height));
    await expect(menu).toHaveCSS("width", "160px");
});

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("dropdown", window.DropdownController);
    });
}

async function bundle() {
    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");
    const transition = (await readFile("resources/js/controllers/_transition.js", "utf8")).replaceAll(
        "export function",
        "function",
    );
    const controller = (await readFile("resources/js/controllers/dropdown_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_transition\.js";/, "")
        .replace("export default class extends Controller", "class DropdownController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { autoUpdate, computePosition, flip, offset, shift, size } = window.FloatingUIDOM;
        ${floating}
        ${transition}
        ${controller}
        window.DropdownController = DropdownController;
    `;
}
