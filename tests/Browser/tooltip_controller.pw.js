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
                    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
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

test("does not show an icon-rail tooltip while the mobile sidebar is open", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.setContent(`
        <div data-slot="sidebar" data-collapsible="icon" data-mobile-state="open">
            <button
                data-controller="tooltip"
                data-tooltip-content-value="Map"
                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
            >
                Map
            </button>
        </div>
    `);
    await installControllers(page);

    await page.locator('[data-controller="tooltip"]').hover();

    await expect(page.locator('[data-slot="tooltip"]')).toHaveCount(0);
});

test("mobile sidebar preserves desktop state and closes synchronously for Turbo cache", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="sidebar-container"] { opacity: 0; transition: opacity 10s linear; }
            [data-slot="sidebar"][data-mobile-state="open"] > [data-slot="sidebar-container"] { opacity: 1; }
        </style>
        <div
            data-controller="sidebar"
            data-sidebar-open-value="true"
            data-sidebar-persist-value="false"
            data-sidebar-lock-scroll-class="overflow-hidden"
            data-action="turbo:before-cache@window->sidebar#closeForCache"
            data-state="expanded"
        >
            <button id="sidebar-trigger" data-slot="sidebar-trigger" data-action="sidebar#toggle" aria-expanded="false">Toggle</button>
            <div
                data-slot="sidebar"
                data-sidebar-target="modal"
                data-sidebar-collapsible="offcanvas"
                data-state="expanded"
                data-mobile-state="closed"
                data-motion="none"
                hidden inert
            >
                <div data-slot="sidebar-backdrop" data-sidebar-target="backdrop" data-action="click->sidebar#clickOutside"></div>
                <div data-slot="sidebar-container" data-sidebar-target="dialog"><a href="#inside">Inside</a></div>
            </div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator("#sidebar-trigger");
    const sidebar = page.locator('[data-sidebar-target="modal"]');

    await trigger.click();
    await expect(sidebar).toHaveAttribute("data-mobile-state", "open");
    await expect(sidebar).toHaveAttribute("data-state", "expanded");
    await expect(sidebar).not.toHaveAttribute("hidden", "");
    await expect(sidebar).not.toHaveAttribute("inert", "");
    await expect(trigger).toHaveAttribute("aria-expanded", "true");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.keyboard.press("Escape");
    await expect(sidebar).toHaveAttribute("data-mobile-state", "closed");
    await expect(sidebar).toHaveAttribute("data-state", "expanded");
    await expect(sidebar).toHaveAttribute("hidden", "");
    await expect(trigger).toBeFocused();

    await sidebar.evaluate((element) => {
        element.dataset.motion = "default";
    });
    await trigger.click();
    await expect(sidebar).toHaveAttribute("data-mobile-state", "open");
    await expect.poll(async () => sidebar.locator('[data-sidebar-target="dialog"]').evaluate((element) => element.getAnimations().some((animation) => (animation.currentTime ?? 0) > 100))).toBe(true);

    const cachedState = await page.evaluate(() => {
        window.dispatchEvent(new CustomEvent("turbo:before-cache"));
        const element = document.querySelector('[data-sidebar-target="modal"]');

        return {
            mobileState: element.dataset.mobileState,
            desktopState: element.dataset.state,
            hidden: element.hidden,
            inert: element.hasAttribute("inert"),
            scrollLocked: document.body.classList.contains("overflow-hidden"),
        };
    });

    expect(cachedState).toEqual({
        mobileState: "closed",
        desktopState: "expanded",
        hidden: true,
        inert: true,
        scrollLocked: false,
    });
});

test("desktop sidebar stays visible with closed mobile presence through a Turbo morph", async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.setContent(`
        <div
            id="sidebar-root"
            data-controller="sidebar"
            data-sidebar-open-value="true"
            data-sidebar-persist-value="false"
            data-state="expanded"
        >
            <button data-slot="sidebar-trigger" data-action="sidebar#toggle">Toggle</button>
            <div
                id="sidebar-surface"
                data-slot="sidebar"
                data-sidebar-target="modal"
                data-sidebar-collapsible="offcanvas"
                data-state="expanded"
                data-mobile-state="closed"
                data-motion="none"
                hidden inert
            >
                <div data-slot="sidebar-backdrop" data-sidebar-target="backdrop"></div>
                <div data-slot="sidebar-container" data-sidebar-target="dialog">
                    <p id="sidebar-content">Initial sidebar</p>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await installControllers(page);

    const sidebar = page.locator("#sidebar-surface");
    await expect(sidebar).toHaveAttribute("data-state", "expanded");
    await expect(sidebar).toHaveAttribute("data-mobile-state", "closed");
    await expect(sidebar).not.toHaveAttribute("hidden", "");
    await expect(sidebar).not.toHaveAttribute("inert", "");

    await page.evaluate(() => {
        const replacement = document.createElement("div");
        replacement.id = "sidebar-surface";
        replacement.setAttribute("data-slot", "sidebar");
        replacement.setAttribute("data-sidebar-target", "modal");
        replacement.setAttribute("data-sidebar-collapsible", "offcanvas");
        replacement.setAttribute("data-state", "expanded");
        replacement.setAttribute("data-mobile-state", "open");
        replacement.setAttribute("data-presence", "leaving");
        replacement.setAttribute("data-motion", "default");
        replacement.setAttribute("hidden", "");
        replacement.setAttribute("inert", "");
        replacement.innerHTML = `
            <div data-slot="sidebar-backdrop" data-sidebar-target="backdrop"></div>
            <div data-slot="sidebar-container" data-sidebar-target="dialog">
                <p id="sidebar-content">Morphed sidebar</p>
            </div>
        `;

        window.Turbo.morphElements(document.querySelector("#sidebar-surface"), replacement);
    });

    await expect(sidebar).toHaveAttribute("data-state", "expanded");
    await expect(sidebar).toHaveAttribute("data-mobile-state", "closed");
    await expect(sidebar).toHaveAttribute("data-motion", "default");
    expect(await sidebar.getAttribute("data-presence")).toBeNull();
    await expect(sidebar).not.toHaveAttribute("hidden", "");
    await expect(sidebar).not.toHaveAttribute("inert", "");
    await expect(sidebar).toContainText("Morphed sidebar");
});

async function installControllers(page) {
    await page.addStyleTag({ content: '[data-hotwire-top-layer][popover] { border: 0; inset: auto; margin: 0; }' });
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
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");
    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export class FocusTrap", "class FocusTrap");

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_stack\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function activateTopOverlay", "function activateTopOverlay")
        .replace("export function isTopOverlay", "function isTopOverlay")
        .replace("export function overlayPosition", "function overlayPosition");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8"))
        .replace("export function createTopLayer", "function createTopLayer");

    const sidebar = (await readFile("resources/js/controllers/sidebar_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace("export default class extends Controller", "class SidebarController extends Controller");

    const tooltip = (await readFile("resources/js/controllers/tooltip_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export default class extends Controller", "class TooltipController extends Controller");

    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8"))
        .replace("export function createPresence", "function createPresence");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${composition}
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${presence}
        ${overlay}
        ${floating}
        ${sidebar}
        ${tooltip}
        window.SidebarController = SidebarController;
        window.TooltipController = TooltipController;
    `;
}
