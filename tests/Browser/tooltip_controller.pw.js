import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("shows a basic tooltip on hover", async ({ page }) => {
    await page.setContent(`
        <button data-controller="tooltip" data-tooltip-content-value="Hello tooltip">
            Hover me
        </button>
    `);

    await installControllers(page);

    await page.locator('[data-controller="tooltip"]').hover();

    await expect(page.locator('[data-slot="tooltip"]')).toContainText("Hello tooltip");
    await expect(page.locator('[data-slot="tooltip"]')).toHaveAttribute("role", "tooltip");
});

test("opens on focus and closes on Escape", async ({ page }) => {
    await page.setContent(`
        <button data-controller="tooltip" data-tooltip-content-value="Focused tooltip">
            Focus me
        </button>
    `);

    await installControllers(page);

    const button = page.locator('[data-controller="tooltip"]');

    await button.focus();
    await expect(page.locator('[data-slot="tooltip"]')).toContainText("Focused tooltip");
    await expect(button).toHaveAttribute("aria-describedby", /hw-tooltip-/);

    await page.keyboard.press("Escape");
    await expect(page.locator('[data-slot="tooltip"]')).toHaveCount(0);
    await expect(button).not.toHaveAttribute("aria-describedby", /hw-tooltip-/);
});

test("shows sidebar icon tooltips only after the sidebar collapses", async ({ page }) => {
    await page.setContent(`
        <div data-controller="sidebar" data-sidebar-open-value="true" data-state="expanded">
            <button data-slot="sidebar-trigger" data-action="click->sidebar#toggle">Toggle</button>
            <div
                data-slot="sidebar"
                data-sidebar-collapsible="icon"
                data-state="expanded"
                data-collapsible=""
            >
                <a
                    href="/components/map"
                    data-slot="sidebar-menu-button"
                    data-controller="tooltip"
                    data-tooltip-content-value="Map"
                    data-tooltip-side-value="right"
                    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
                >
                    <svg></svg>
                    <span>Map</span>
                </a>
            </div>
        </div>
    `);

    await installControllers(page);

    const button = page.locator('[data-slot="sidebar-menu-button"]');

    await button.hover();
    await expect(page.locator('[data-slot="tooltip"]')).toHaveCount(0);

    await page.locator('[data-slot="sidebar-trigger"]').click();
    await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute("data-collapsible", "icon");

    await button.hover();
    await expect(page.locator('[data-slot="tooltip"]')).toContainText("Map");
    await expect(page.locator('[data-slot="tooltip"]')).toHaveAttribute("data-side", "right");
});

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await browserControllersScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("sidebar", window.SidebarController);
        window.StimulusApplication.register("tooltip", window.TooltipController);
    });
}

async function browserControllersScript() {
    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_stack\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function isTopOverlay", "function isTopOverlay");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8"))
        .replace("export function createTopLayer", "function createTopLayer");

    const sidebar = (await readFile("resources/js/controllers/sidebar_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace("export default class extends Controller", "class SidebarController extends Controller");

    const tooltip = (await readFile("resources/js/controllers/tooltip_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace("export default class extends Controller", "class TooltipController extends Controller");

    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${overlayStack}
        ${topLayer}
        ${overlay}
        ${floating}
        ${sidebar}
        ${tooltip}
        window.SidebarController = SidebarController;
        window.TooltipController = TooltipController;
    `;
}
