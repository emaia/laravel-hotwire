import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens with a transition, focuses content and closes on Escape", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            .t-enter, .t-leave { transition: opacity 60ms linear; }
            .op0 { opacity: 0; }
            .op100 { opacity: 1; }
        </style>
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            <div data-popover-target="content" class="hidden" tabindex="-1"
                 data-transition-enter="t-enter" data-transition-enter-from="op0" data-transition-enter-to="op100"
                 data-transition-leave="t-leave" data-transition-leave-from="op100" data-transition-leave-to="op0">
                <input id="name" aria-label="Name">
            </div>
        </div>
    `);

    await installControllers(page, ["popover"]);

    const trigger = page.locator('[data-popover-target="trigger"]');
    const content = page.locator('[data-popover-target="content"]');

    await trigger.click();
    await expect(content).toBeVisible();
    await expect(trigger).toHaveAttribute("aria-expanded", "true");
    await expect(page.locator("#name")).toBeFocused();

    await page.keyboard.press("Escape");
    await expect(content).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute("aria-expanded", "false");
});

test("positions content inside a Turbo Frame with Floating UI", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            body { margin: 0; }
            [data-popover-target="trigger"] { margin-left: 120px; margin-top: 80px; width: 140px; height: 32px; }
            [data-popover-target="content"] { width: var(--anchor-width); min-width: 8rem; }
        </style>
        <turbo-frame id="settings-frame">
            <div data-controller="popover" data-popover-side-offset-value="4">
                <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Filters</button>
                <div data-popover-target="content" class="hidden" tabindex="-1">
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

test("closes on outside click and before Turbo cache", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="popover">
            <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open</button>
            <div data-popover-target="content" class="hidden" tabindex="-1"><input></div>
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
    await expect(content).toHaveAttribute("data-open", "false");
});

test("nested inside a modal, Escape closes the popover before the modal", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            .pointer-events-none { pointer-events: none; }
            .pointer-events-auto { pointer-events: auto; }
            .opacity-0 { opacity: 0; }
            .opacity-100 { opacity: 1; }
            .scale-80 { transform: scale(.8); }
            .scale-100 { transform: scale(1); }
        </style>
        <div data-controller="modal"
             data-modal-open-duration-value="0"
             data-modal-close-duration-value="0"
             data-modal-hidden-class="pointer-events-none"
             data-modal-visible-class="pointer-events-auto"
             data-modal-backdrop-hidden-class="opacity-0"
             data-modal-backdrop-visible-class="opacity-100"
             data-modal-dialog-hidden-class="scale-80 opacity-0"
             data-modal-dialog-visible-class="scale-100 opacity-100"
             data-modal-lock-scroll-value="false">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-open="false" hidden class="pointer-events-none" role="dialog" aria-modal="true">
                <div data-modal-target="backdrop" data-action="click->modal#clickOutside" class="opacity-0"></div>
                <div data-modal-target="dialog" class="scale-80 opacity-0">
                    <div data-controller="popover">
                        <button type="button" data-popover-target="trigger" data-action="popover#toggle" aria-expanded="false">Open popover</button>
                        <div data-popover-target="content" class="hidden" tabindex="-1"><input id="nested-input"></div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await installControllers(page, ["modal", "popover"]);

    const modal = page.locator('[data-modal-target="modal"]');
    const popover = page.locator('[data-popover-target="content"]');

    await page.locator("#modal-trigger").click();
    await expect(modal).toHaveAttribute("data-open", "true");

    await page.locator('[data-popover-target="trigger"]').click();
    await expect(popover).toBeVisible();

    await page.keyboard.press("Escape");
    await expect(popover).toBeHidden();
    await expect(modal).toHaveAttribute("data-open", "true");

    await page.keyboard.press("Escape");
    await expect(modal).toHaveAttribute("data-open", "false");
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
    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    const transition = (await readFile("resources/js/controllers/_transition.js", "utf8"))
        .replace("export function enter", "function enter")
        .replace("export function leave", "function leave")
        .replace("export function cancel", "function cancel");

    const popover = (await readFile("resources/js/controllers/popover_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_transition\.js";\s*/, "")
        .replace("export default class extends Controller", "class PopoverController extends Controller");

    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace("export class FocusTrap", "class FocusTrap");

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

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
        ${floating}
        ${transition}
        ${popover}
        ${focusTrap}
        ${overlay}
        ${frameOverlay}
        ${modal}
        window.PopoverController = PopoverController;
        window.ModalController = ModalController;
    `;
}
