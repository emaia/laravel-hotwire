import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens with a transition, closes on Escape and restores focus", async ({ page }) => {
    await page.setContent(`
        <style>
            [data-dropdown-target="menu"] { transition: opacity 60ms linear, scale 60ms linear; }
            [data-dropdown-target="menu"][data-state="closed"] { opacity: 0; pointer-events: none; scale: .95; }
            [data-dropdown-target="menu"][data-state="open"] { opacity: 1; scale: 1; }
            [data-dropdown-target="menu"][data-presence="instant"] { transition: none; }
            [data-hotwire-top-layer][popover] { border: 0; inset: auto; margin: 0; }
        </style>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                <a href="#item">Item</a>
            </div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-dropdown-target="trigger"]');
    const menu = page.locator('[data-dropdown-target="menu"]');

    await trigger.click();
    await expect(menu).toBeVisible();
    await expect(menu).toHaveAttribute("data-state", "open");
    await expect(trigger).toHaveAttribute("aria-expanded", "true");

    const duringExit = await page.evaluate(() => {
        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
        const menu = document.querySelector('[data-dropdown-target="menu"]');

        return {
            hidden: menu.hidden,
            inert: menu.hasAttribute("inert"),
            state: menu.dataset.state,
            topLayer: menu.matches(":popover-open"),
        };
    });

    expect(duringExit).toEqual({ hidden: false, inert: true, state: "closed", topLayer: true });
    await expect(menu).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute("aria-expanded", "false");
    expect(await menu.evaluate((element) => element.matches(":popover-open"))).toBe(false);
    await expect(menu).not.toHaveAttribute("data-hotwire-top-layer", "");
    await expect(menu).not.toHaveAttribute("popover", "manual");
});

test("closes when clicking outside", async ({ page }) => {
    await page.setContent(`
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#item">Item</a></div>
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

test("reopening during exit cancels stale hiding and top-layer teardown", async ({ page }) => {
    await page.setContent(`
        <style>
            [data-dropdown-target="menu"] { transition: opacity 120ms linear; }
            [data-dropdown-target="menu"][data-state="closed"] { opacity: 0; pointer-events: none; }
            [data-dropdown-target="menu"][data-state="open"] { opacity: 1; }
            [data-hotwire-top-layer][popover] { border: 0; inset: auto; margin: 0; }
        </style>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#item">Item</a></div>
        </div>
    `);
    await installControllers(page);

    const trigger = page.locator('[data-dropdown-target="trigger"]');
    const menu = page.locator('[data-dropdown-target="menu"]');

    await trigger.click();
    await expect(menu).toHaveAttribute("data-state", "open");
    await trigger.click();
    await expect(menu).toHaveAttribute("data-state", "closed");
    await page.waitForTimeout(20);
    await trigger.click();
    await page.waitForTimeout(150);

    await expect(menu).toBeVisible();
    await expect(menu).toHaveAttribute("data-state", "open");
    expect(await menu.evaluate((element) => element.matches(":popover-open"))).toBe(true);
});

test("motion none suppresses custom CSS transitions without preset rules", async ({ page }) => {
    await page.setContent(`
        <style>
            @keyframes dropdown-fade { from { opacity: .25; } to { opacity: 1; } }
            [data-dropdown-target="menu"] { opacity: 1; transition: opacity 1000ms linear; }
            [data-dropdown-target="menu"][data-state="closed"] { opacity: 0; }
            [data-dropdown-target="menu"][data-state="open"] { animation: dropdown-fade 1000ms linear; }
        </style>
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle">Menu</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="none" hidden inert>Menu</div>
        </div>
    `);
    await installControllers(page);

    const trigger = page.locator('[data-dropdown-target="trigger"]');
    const menu = page.locator('[data-dropdown-target="menu"]');
    await trigger.click();
    await expect(menu).toHaveAttribute("data-state", "open");

    expect(await menu.evaluate((element) => element.getAnimations().length)).toBe(0);
    await expect(menu).toHaveCSS("opacity", "1");

    await trigger.click();
    await expect(menu).toBeHidden();
});

test("positions the menu with Floating UI", async ({ page }) => {
    await page.setContent(`
        <style>
            body { margin: 0; }
            [data-dropdown-target="trigger"] { margin-left: 120px; margin-top: 80px; width: 160px; height: 32px; }
            [data-dropdown-target="menu"] { width: var(--anchor-width); min-width: 8rem; }
        </style>
        <div data-controller="dropdown" data-dropdown-side-offset-value="4">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert><a href="#item">Item</a></div>
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

test("keeps a morphed secondary trigger as the active anchor", async ({ page }) => {
    await page.setContent(`
        <div data-controller="dropdown">
            <button id="first-trigger" data-dropdown-target="trigger" data-action="dropdown#toggle">First</button>
            <button id="active-trigger" data-dropdown-target="trigger" data-action="dropdown#toggle">Active</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>Menu</div>
        </div>
    `);
    await installControllers(page);

    await page.locator("#active-trigger").click();
    await expect(page.locator('[data-dropdown-target="menu"]')).toBeVisible();
    await page.locator("#active-trigger").evaluate((trigger) => {
        const root = trigger.parentElement;
        const menu = root.querySelector('[data-dropdown-target="menu"]');
        const replacements = [...root.querySelectorAll('[data-dropdown-target="trigger"]')]
            .map((candidate) => candidate.cloneNode(true));
        const inserted = document.createElement("button");
        inserted.id = "inserted-trigger";
        inserted.dataset.dropdownTarget = "trigger";
        inserted.dataset.action = "dropdown#toggle";
        inserted.textContent = "Inserted";
        root.replaceChildren(inserted, ...replacements, menu);
    });
    await expect(page.locator("#active-trigger")).toHaveAttribute("data-dropdown-state", "open");
    await expect(page.locator("#active-trigger")).toBeFocused();

    await page.keyboard.press("Escape");
    await expect(page.locator("#active-trigger")).toBeFocused();
});

test("rebinds behavior when an open menu is morphed", async ({ page }) => {
    await page.setContent(`
        <div data-controller="dropdown">
            <button data-dropdown-target="trigger" data-action="dropdown#toggle">Menu</button>
            <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                <button id="menu-action" type="button">Action</button>
            </div>
        </div>
    `);
    await installControllers(page);

    const menu = page.locator('[data-dropdown-target="menu"]');
    await page.locator('[data-dropdown-target="trigger"]').click();
    await expect(menu).toBeVisible();

    await menu.evaluate((element) => element.replaceWith(element.cloneNode(true)));
    await expect(menu).toBeVisible();
    await page.locator("#menu-action").click();

    await expect(menu).toBeHidden();
});

test("mobile positioning overrides a persisted collapsed sidebar profile", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.setContent(`
        <div data-slot="sidebar" data-state="collapsed" data-collapsible="icon">
            <div data-controller="dropdown">
                <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
                <div data-dropdown-target="menu"
                     data-state="closed"
                     data-motion="default"
                     data-dropdown-side-value="top"
                     data-dropdown-align-value="start"
                     data-dropdown-mobile-side-value="bottom"
                     data-dropdown-collapsed-side-value="right"
                     data-dropdown-collapsed-align-value="end"
                     hidden inert>Content</div>
            </div>
        </div>
    `);
    await installControllers(page);

    const menu = page.locator('[data-dropdown-target="menu"]');
    await page.locator('[data-dropdown-target="trigger"]').click();

    await expect(menu).toHaveAttribute("data-dropdown-effective-side", "bottom");
    await expect(menu).toHaveAttribute("data-dropdown-effective-align", "start");
});

test("native Tab navigation and nested Escape close before the parent drawer", async ({ page }) => {
    await page.setContent(`
        <style>
            .pointer-events-none { pointer-events: none; }
            .pointer-events-auto { pointer-events: auto; }
            .opacity-0 { opacity: 0; }
            .opacity-100 { opacity: 1; }
            .translate-x-full { transform: translateX(100%); }
            .translate-x-0 { transform: translateX(0); }
        </style>
        <div data-controller="drawer"
             data-drawer-open-duration-value="0"
             data-drawer-close-duration-value="0"
             data-drawer-hidden-class="pointer-events-none"
             data-drawer-visible-class="pointer-events-auto"
             data-drawer-backdrop-hidden-class="opacity-0"
             data-drawer-backdrop-visible-class="opacity-100"
             data-drawer-dialog-hidden-class="translate-x-full"
             data-drawer-dialog-visible-class="translate-x-0"
             data-drawer-lock-scroll-class="overflow-hidden">
            <button id="drawer-trigger" data-drawer-target="trigger" data-action="drawer#toggle">Open drawer</button>
            <div data-drawer-target="modal" data-open="false" hidden class="pointer-events-none">
                <div data-drawer-target="backdrop" data-action="click->drawer#clickOutside" class="opacity-0"></div>
                <div data-drawer-target="dialog" class="translate-x-full">
                    <div data-controller="dropdown">
                        <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Menu</button>
                        <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
                            <a id="first" href="#first">First</a>
                            <button id="disabled" type="button" disabled>Disabled</button>
                            <button id="second" type="button">Second</button>
                            <a id="third" href="#third">Third</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await installControllers(page);

    const modal = page.locator('[data-drawer-target="modal"]');
    const trigger = page.locator('[data-dropdown-target="trigger"]');
    const menu = page.locator('[data-dropdown-target="menu"]');
    const first = page.locator("#first");
    const second = page.locator("#second");

    await page.locator("#drawer-trigger").click();
    await expect(modal).toHaveAttribute("data-open", "true");

    await trigger.focus();
    await page.keyboard.press("ArrowDown");
    await expect(menu).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.click();
    await expect(menu).toBeVisible();

    await page.keyboard.press("Tab");
    await expect(first).toBeFocused();

    await page.keyboard.press("Tab");
    await expect(second).toBeFocused();

    await page.keyboard.press("ArrowDown");
    await expect(second).toBeFocused();

    await page.keyboard.press("Escape");
    await expect(menu).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(modal).toHaveAttribute("data-open", "true");

    await page.keyboard.press("Escape");
    await expect(modal).toHaveAttribute("data-open", "false");
});

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("dropdown", window.DropdownController);
        window.app.register("drawer", window.DrawerController);
    });
}

async function bundle() {
    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace("export class FocusTrap", "class FocusTrap");

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

    const frameOverlay = (await readFile("resources/js/controllers/_frame_overlay.js", "utf8"))
        .replace("export function createFrameOverlay", "function createFrameOverlay");

    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");
    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8"))
        .replace("export function createPresence", "function createPresence");
    const controller = (await readFile("resources/js/controllers/dropdown_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export default class extends Controller", "class DropdownController extends Controller");

    const drawer = (await readFile("resources/js/controllers/drawer_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace("export default class DrawerController extends Controller", "class DrawerController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${overlay}
        ${frameOverlay}
        ${floating}
        ${presence}
        ${controller}
        ${drawer}
        window.DropdownController = DropdownController;
        window.DrawerController = DrawerController;
    `;
}
