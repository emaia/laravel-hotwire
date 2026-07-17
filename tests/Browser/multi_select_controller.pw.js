import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("search clear button is tabbable and Space toggles focused options", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            [data-multi-select-target="trigger"] { width: 180px; }
            [data-multi-select-target="content"] { width: var(--anchor-width); }
        </style>
        <div data-controller="multi-select" data-multi-select-select-all-value="true">
            <select data-multi-select-target="select" name="status[]" multiple hidden>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
            </select>

            <button type="button" data-multi-select-target="trigger" data-action="multi-select#toggle" aria-expanded="false">
                <span data-multi-select-target="value">Select options</span>
            </button>

            <div data-multi-select-target="content" data-open="false" class="hidden">
                <span data-controller="clear-input">
                    <input data-slot="multi-select-search" data-multi-select-target="search" data-clear-input-target="input" type="text">
                    <button type="button" class="hidden" data-slot="clear-input-button" data-clear-input-target="clearButton">Clear</button>
                </span>
                <button type="button" data-multi-select-target="selectAll" aria-pressed="false">Select all</button>
                <div role="listbox" aria-multiselectable="true">
                    <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
                    <div data-slot="multi-select-option" data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
                </div>
                <div data-slot="multi-select-empty" data-multi-select-target="empty" hidden>No options found.</div>
            </div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-multi-select-target="trigger"]');
    const content = page.locator('[data-multi-select-target="content"]');
    const search = page.locator('[data-multi-select-target="search"]');
    const clearButton = page.locator('[data-clear-input-target="clearButton"]');
    const selectAll = page.locator('[data-multi-select-target="selectAll"]');
    const empty = page.locator('[data-multi-select-target="empty"]');
    const active = page.locator('[data-value="active"]');
    const paused = page.locator('[data-value="paused"]');
    const select = page.locator('select[name="status[]"]');

    await trigger.click();
    await expect(content).toBeVisible();

    await search.fill("missing");
    await expect(empty).toBeVisible();
    await expect(empty).toHaveText("No options found.");
    await expect(selectAll).toBeHidden();
    await expect(selectAll).toHaveAttribute("aria-pressed", "false");

    await search.fill("act");
    await expect(empty).toBeHidden();
    await expect(selectAll).toBeVisible();
    await expect(paused).toBeHidden();
    await expect(clearButton).toBeVisible();

    await page.keyboard.press("Tab");
    await expect(clearButton).toBeFocused();
    await expect(content).toBeVisible();

    await page.keyboard.press("Enter");
    await expect(search).toHaveValue("");
    await expect(paused).toBeVisible();

    await page.keyboard.press("ArrowDown");
    await expect(selectAll).toBeFocused();

    await page.keyboard.press("ArrowUp");
    await expect(search).toBeFocused();

    await page.keyboard.press("ArrowDown");
    await expect(selectAll).toBeFocused();

    await page.keyboard.press("ArrowDown");
    await expect(active).toBeFocused();

    await page.keyboard.press("Space");
    await expect(active).toHaveAttribute("aria-selected", "true");
    await expect(select).toHaveJSProperty("value", "active");

    await page.keyboard.press("Space");
    await expect(active).toHaveAttribute("aria-selected", "false");
    await expect(select).toHaveJSProperty("value", "");
});

test("fixed strategy lets the panel cross drawer clipping boundaries", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            body { margin: 0; }
            [data-slot="drawer-popup"] {
                position: fixed;
                top: 40px;
                right: 0;
                width: 260px;
                height: 320px;
                border: 1px solid black;
                will-change: transform;
            }
            [data-slot="drawer-content"] { height: 100%; overflow: hidden; }
            [data-slot="drawer-body"] { height: 100%; overflow-y: auto; padding: 24px; }
            [data-controller="multi-select"] { display: block; width: 180px; margin-left: 24px; margin-top: 80px; }
            [data-multi-select-target="trigger"] { width: 180px; height: 32px; }
            [data-multi-select-target="content"] { width: var(--anchor-width); min-width: 120px; background: white; border: 1px solid black; }
        </style>
        <div data-slot="drawer-popup">
            <div data-slot="drawer-content">
                <div data-slot="drawer-body">
                    <div data-controller="multi-select"
                         data-multi-select-side-value="left"
                         data-multi-select-align-value="start">
                        <select data-multi-select-target="select" name="status[]" multiple hidden>
                            <option value="active">Active</option>
                        </select>
                        <button type="button" data-multi-select-target="trigger" data-action="multi-select#toggle" aria-expanded="false">
                            <span data-multi-select-target="value">Select options</span>
                        </button>
                        <div data-multi-select-target="content" data-open="false" class="hidden">
                            <input data-multi-select-target="search" type="text">
                            <div role="listbox" aria-multiselectable="true">
                                <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await installControllers(page);

    const drawer = page.locator('[data-slot="drawer-popup"]');
    const content = page.locator('[data-multi-select-target="content"]');

    await page.locator('[data-multi-select-target="trigger"]').click();
    await expect(content).toBeVisible();

    const drawerBox = await drawer.boundingBox();
    const contentBox = await content.boundingBox();
    expect(contentBox.x).toBeLessThan(drawerBox.x);

    const hit = await page.evaluate(() => {
        const panel = document.querySelector('[data-multi-select-target="content"]');
        const box = panel.getBoundingClientRect();
        return document.elementFromPoint(box.left + 8, box.top + 8)?.closest('[data-multi-select-target="content"]') === panel;
    });

    expect(hit).toBe(true);
});

test("list-all summaries are capped before they can size the trigger", async ({ page }) => {
    await page.setContent(`
        <style>
            [data-controller="multi-select"] { width: 220px; }
            [data-multi-select-target="trigger"] {
                align-items: center;
                border: 1px solid black;
                box-sizing: border-box;
                display: inline-flex;
                gap: 8px;
                justify-content: space-between;
                overflow: hidden;
                padding: 4px 8px;
                width: 220px;
            }
            [data-slot="multi-select-value"] {
                flex: 1 1 0%;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            [data-slot="multi-select-trigger-icon"] { flex-shrink: 0; width: 16px; }
        </style>
        <div data-controller="multi-select" data-multi-select-list-all-value="true">
            <select data-multi-select-target="select" name="countries[]" multiple hidden>
                <option value="AR">Argentina</option>
                <option value="AU">Australia</option>
                <option value="BR">Brazil</option>
                <option value="CA">Canada</option>
                <option value="CL">Chile</option>
                <option value="CO">Colombia</option>
                <option value="DE">Germany</option>
                <option value="ES">Spain</option>
                <option value="FR">France</option>
                <option value="GB">United Kingdom</option>
            </select>
            <button type="button" data-multi-select-target="trigger" data-action="multi-select#toggle" aria-expanded="false">
                <span data-slot="multi-select-value" data-multi-select-target="value">Select countries</span>
                <span data-slot="multi-select-trigger-icon" aria-hidden="true">v</span>
            </button>
            <div data-multi-select-target="content" data-open="false" hidden>
                <input data-multi-select-target="search" type="text">
                <div role="listbox" aria-multiselectable="true">
                    ${["AR", "AU", "BR", "CA", "CL", "CO", "DE", "ES", "FR", "GB"].map((value) => `
                        <div data-slot="multi-select-option" data-multi-select-target="option" data-value="${value}" data-selected="false" role="option" aria-selected="false" tabindex="-1">${countryName(value)}</div>
                    `).join("")}
                </div>
            </div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-multi-select-target="trigger"]');
    const value = page.locator('[data-multi-select-target="value"]');

    await page.locator('[data-multi-select-target="option"]').evaluateAll((options) => {
        options.forEach((option) => option.click());
    });

    await expect(value).toHaveText("Argentina, Australia, Brazil, +7 more");
    await expect(value).toHaveAttribute("title", "Argentina, Australia, Brazil, Canada, Chile, Colombia, Germany, Spain, France, United Kingdom");
    expect(Math.round((await trigger.boundingBox()).width)).toBe(220);
});

test("sort-selected keeps the menu open after selecting an option", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="multi-select" data-multi-select-sort-selected-value="true">
            <select data-multi-select-target="select" name="status[]" multiple hidden>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="archived">Archived</option>
            </select>
            <button type="button" data-multi-select-target="trigger" data-action="multi-select#toggle" aria-expanded="false">
                <span data-multi-select-target="value">Select status</span>
            </button>
            <div data-multi-select-target="content" data-open="false" class="hidden">
                <input data-multi-select-target="search" type="text">
                <div data-multi-select-target="list" role="listbox" aria-multiselectable="true">
                    <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
                    <div data-slot="multi-select-option" data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
                    <div data-slot="multi-select-option" data-multi-select-target="option" data-value="archived" data-selected="false" role="option" aria-selected="false" tabindex="-1">Archived</div>
                </div>
            </div>
        </div>
    `);

    await installControllers(page);

    const content = page.locator('[data-multi-select-target="content"]');
    const archived = page.locator('[data-value="archived"]');

    await page.locator('[data-multi-select-target="trigger"]').click();
    await archived.click();

    await expect(content).toBeVisible();
    await expect(archived).toBeFocused();
    await expect(page.locator('[data-multi-select-target="option"]').first()).toHaveAttribute("data-value", "archived");
});

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("clear-input", window.ClearInputController);
        window.app.register("multi-select", window.MultiSelectController);
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
    const clearInput = (await readFile("resources/js/controllers/clear_input_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace("export default class extends Controller", "class ClearInputController extends Controller");
    const multiSelect = (await readFile("resources/js/controllers/multi_select_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_transition\.js";/, "")
        .replace("export default class extends Controller", "class MultiSelectController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${floating}
        ${transition}
        ${clearInput}
        ${multiSelect}
        window.ClearInputController = ClearInputController;
        window.MultiSelectController = MultiSelectController;
    `;
}

function countryName(value) {
    return {
        AR: "Argentina",
        AU: "Australia",
        BR: "Brazil",
        CA: "Canada",
        CL: "Chile",
        CO: "Colombia",
        DE: "Germany",
        ES: "Spain",
        FR: "France",
        GB: "United Kingdom",
    }[value];
}
