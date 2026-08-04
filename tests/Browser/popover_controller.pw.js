import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens with a transition, focuses content and closes on Escape", async ({ page }) => {
    await page.setContent(`
        <style>
            [data-popover-target="content"] { transition: opacity 300ms linear, scale 300ms linear; }
            [data-popover-target="content"][data-state="closed"] { opacity: 0; pointer-events: none; scale: .95; }
            [data-popover-target="content"][data-state="open"] { opacity: 1; scale: 1; }
            [data-popover-target="content"][data-presence="instant"] { transition: none; }
            [data-hotwire-top-layer][popover] { border: 0; inset: auto; margin: 0; }
        </style>
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            <div data-popover-target="content" data-state="closed" data-motion="default" hidden inert tabindex="-1">
                <input id="name" aria-label="Name">
                <button id="action" type="button">Action</button>
            </div>
        </div>
    `);

    await installControllers(page, ["popover"]);

    const trigger = page.locator('[data-popover-target="trigger"]');
    const content = page.locator('[data-popover-target="content"]');

    await trigger.click();
    await expect(content).toBeVisible();
    await expect(trigger).toHaveAttribute("aria-expanded", "true");
    await expect(page.locator("#name")).toBeFocused({ timeout: 200 });

    await page.locator("#action").focus();
    await page.waitForTimeout(350);
    await expect(page.locator("#action")).toBeFocused();

    await page.keyboard.press("Escape");
    await expect(content).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute("aria-expanded", "false");
});

test("positions content inside a Turbo Frame with Floating UI", async ({ page }) => {
    await page.setContent(`
        <style>
            body { margin: 0; }
            [data-popover-target="trigger"] { margin-left: 120px; margin-top: 80px; width: 140px; height: 32px; }
            [data-popover-target="content"] { width: var(--anchor-width); min-width: 8rem; }
        </style>
        <turbo-frame id="settings-frame">
            <div data-controller="popover" data-popover-side-offset-value="4">
                <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Filters</button>
                <div data-popover-target="content" data-state="closed" data-motion="default" hidden inert tabindex="-1">
                    <input aria-label="Filter">
                </div>
            </div>
        </turbo-frame>
    `);

    await installControllers(page, ["popover"]);

    const trigger = page.locator('[data-popover-target="trigger"]');
    const content = page.locator('[data-popover-target="content"]');

    await trigger.click();
    await expect(content).toBeVisible();
    await expect(content).toHaveAttribute("data-side", "bottom");

    const triggerBox = await trigger.boundingBox();
    const contentBox = await content.boundingBox();

    expect(Math.round(contentBox.y)).toBeGreaterThanOrEqual(Math.round(triggerBox.y + triggerBox.height));
    await expect(content).toHaveCSS("width", "140px");
});

test("preserves managed focus when open content is morphed", async ({ page }) => {
    await page.setContent(`
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle">Open</button>
            <div data-popover-target="content" data-state="closed" data-motion="default" hidden inert>
                <input id="morphed-input">
            </div>
        </div>
    `);
    await installControllers(page, ["popover"]);

    const content = page.locator('[data-popover-target="content"]');
    await page.locator('[data-popover-target="trigger"]').click();
    await expect(page.locator("#morphed-input")).toBeFocused();

    await content.evaluate((element) => element.replaceWith(element.cloneNode(true)));

    await expect(content).toBeVisible();
    await expect(page.locator("#morphed-input")).toBeFocused();

    await page.locator('[data-popover-target="trigger"]').focus();
    await content.evaluate((element) => element.replaceWith(element.cloneNode(true)));

    await expect(content).toBeVisible();
    await expect(page.locator('[data-popover-target="trigger"]')).toBeFocused();
});

test("correlates the active trigger across a batched morph", async ({ page }) => {
    await page.setContent(`
        <div data-controller="popover">
            <button id="first-trigger" data-popover-target="trigger" data-action="popover#toggle">First</button>
            <button id="active-trigger" data-popover-target="trigger" data-action="popover#toggle">Active</button>
            <div data-popover-target="content" data-state="closed" data-motion="default" hidden inert><input></div>
        </div>
    `);
    await installControllers(page, ["popover"]);

    await page.locator("#active-trigger").click();
    await expect(page.locator('[data-popover-target="content"]')).toBeVisible();
    await page.locator("#active-trigger").evaluate((trigger) => {
        const root = trigger.parentElement;
        const content = root.querySelector('[data-popover-target="content"]');
        const replacements = [...root.querySelectorAll('[data-popover-target="trigger"]')]
            .map((candidate) => candidate.cloneNode(true));
        const inserted = document.createElement("button");
        inserted.id = "inserted-trigger";
        inserted.dataset.popoverTarget = "trigger";
        inserted.dataset.action = "popover#toggle";
        inserted.textContent = "Inserted";
        root.replaceChildren(inserted, ...replacements, content);
    });

    await page.keyboard.press("Escape");
    await expect(page.locator("#active-trigger")).toBeFocused();
});

test("closes on outside click and before Turbo cache", async ({ page }) => {
    await page.setContent(`
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            <div data-popover-target="content" data-state="closed" data-motion="default" hidden inert tabindex="-1"><input></div>
        </div>
        <button id="outside">Outside</button>
    `);

    await installControllers(page, ["popover"]);

    const trigger = page.locator('[data-popover-target="trigger"]');
    const content = page.locator('[data-popover-target="content"]');

    await trigger.click();
    await expect(content).toBeVisible();

    await page.mouse.click(500, 500);
    await expect(content).toBeHidden();

    await trigger.click();
    await expect(content).toBeVisible();

    await page.evaluate(() => document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true })));
    await expect(content).toBeHidden();
    await expect(content).toHaveAttribute("data-state", "closed");
});

test("nested inside a modal, Escape closes the popover before the modal", async ({ page }) => {
    await page.setContent(`
        <style>
            .pointer-events-none { pointer-events: none; }
            .pointer-events-auto { pointer-events: auto; }
            .opacity-0 { opacity: 0; }
            .opacity-100 { opacity: 1; }
            .scale-80 { transform: scale(.8); }
            .scale-100 { transform: scale(1); }
        </style>
        <div data-controller="modal"
             data-modal-lock-scroll-value="false">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert role="dialog" aria-modal="true">
                <div data-modal-target="backdrop" data-action="click->modal#clickOutside" class="opacity-0"></div>
                <div data-modal-target="dialog" class="scale-80 opacity-0">
                    <div data-controller="popover">
                        <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open popover</button>
                        <div data-popover-target="content" data-state="closed" data-motion="default" hidden inert tabindex="-1"><input id="nested-input"></div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await installControllers(page, ["modal", "popover"]);

    const modal = page.locator('[data-modal-target="modal"]');
    const popover = page.locator('[data-popover-target="content"]');

    await page.locator("#modal-trigger").click();
    await expect(modal).toHaveAttribute("data-state", "open");

    await page.locator('[data-popover-target="trigger"]').click();
    await expect(popover).toBeVisible();

    await modal.locator(':scope > [data-modal-target="backdrop"]').evaluate((element) => {
        element.replaceWith(element.cloneNode(true));
    });
    await page.waitForTimeout(0);
    await expect.poll(async () => popover.evaluate((element) => {
        const rect = element.getBoundingClientRect();
        const target = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);

        return target?.closest('[data-popover-target="content"]') === element;
    })).toBe(true);

    await page.keyboard.press("Escape");
    await expect(popover).toBeHidden();
    await expect(modal).toHaveAttribute("data-state", "open");

    await page.keyboard.press("Escape");
    await expect(modal).toHaveAttribute("data-state", "closed");
});

async function installControllers(page, controllers) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate((names) => {
        window.app = window.Stimulus.Application.start();
        if (names.includes("modal")) window.app.register("modal", window.ModalController);
        if (names.includes("popover")) window.app.register("popover", window.PopoverController);
    }, controllers);
}

async function bundle() {
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");
    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8"))
        .replace("export function createPresence", "function createPresence");

    const popover = (await readFile("resources/js/controllers/popover_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export default class extends Controller", "class PopoverController extends Controller");

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
        .replace("export function isTopOverlay", "function isTopOverlay")
        .replace("export function overlayPosition", "function overlayPosition");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8"))
        .replace("export function createTopLayer", "function createTopLayer");

    const frameOverlay = (await readFile("resources/js/controllers/_frame_overlay.js", "utf8"))
        .replace("export function createFrameOverlay", "function createFrameOverlay");

    const modal = (await readFile("resources/js/controllers/modal_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace("export default class ModalController extends Controller", "class ModalController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${composition}
        ${floating}
        ${presence}
        ${topLayer}
        ${popover}
        ${focusTrap}
        ${overlayStack}
        ${overlay}
        ${frameOverlay}
        ${modal}
        window.PopoverController = PopoverController;
        window.ModalController = ModalController;
    `;
}
