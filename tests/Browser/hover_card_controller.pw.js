import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens on hover, stays open over content and closes after leave", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            .t-enter, .t-leave { transition: opacity 40ms linear; }
            .op0 { opacity: 0; }
            .op100 { opacity: 1; }
        </style>
        <div data-controller="hover-card" data-hover-card-open-delay-value="30" data-hover-card-close-delay-value="30">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0" aria-expanded="false">Jane Doe</span>
            <div data-hover-card-target="content" class="hidden"
                 data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
                 data-transition-enter="t-enter" data-transition-enter-from="op0" data-transition-enter-to="op100"
                 data-transition-leave="t-leave" data-transition-leave-from="op100" data-transition-leave-to="op0">
                Profile preview
            </div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-hover-card-target="trigger"]');
    const content = page.locator('[data-hover-card-target="content"]');

    await expect(content).toBeHidden();
    await trigger.hover();
    await expect(content).toBeVisible({ timeout: 500 });
    await expect(trigger).toHaveAttribute("aria-expanded", "true");

    await content.hover();
    await page.waitForTimeout(60);
    await expect(content).toBeVisible();

    await page.mouse.move(500, 500);
    await expect(content).toBeHidden({ timeout: 500 });
});

test("opens on focus and closes on Escape with focus return", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0" aria-expanded="false">Account</span>
            <div data-hover-card-target="content" class="hidden">Account preview</div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-hover-card-target="trigger"]');
    const content = page.locator('[data-hover-card-target="content"]');

    await trigger.focus();
    await expect(content).toBeVisible();

    await page.keyboard.press("Escape");
    await expect(content).toBeHidden();
    await expect(trigger).toBeFocused();
});

test("positions content inside a Turbo Frame with Floating UI", async ({ page }) => {
    await page.setContent(`
        <style>
            .hidden { display: none; }
            body { margin: 0; }
            [data-hover-card-target="trigger"] { display: inline-block; margin-left: 120px; margin-top: 80px; width: 140px; height: 32px; }
            [data-hover-card-target="content"] { width: var(--anchor-width); min-width: 8rem; }
        </style>
        <turbo-frame id="users-frame">
            <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-side-offset-value="4">
                <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0">User</span>
                <div data-hover-card-target="content" class="hidden">Preview</div>
            </div>
        </turbo-frame>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-hover-card-target="trigger"]');
    const content = page.locator('[data-hover-card-target="content"]');

    await trigger.hover();
    await expect(content).toBeVisible();
    await expect(content).toHaveAttribute("data-side", "bottom");

    const triggerBox = await trigger.boundingBox();
    const contentBox = await content.boundingBox();

    expect(Math.round(contentBox.y)).toBeGreaterThanOrEqual(Math.round(triggerBox.y + triggerBox.height));
    await expect(content).toHaveCSS("width", "140px");
});

test("closes before Turbo cache", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0">User</span>
            <div data-hover-card-target="content" class="hidden">Preview</div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-hover-card-target="trigger"]');
    const content = page.locator('[data-hover-card-target="content"]');

    await trigger.hover();
    await expect(content).toBeVisible();

    await page.evaluate(() => document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true })));
    await expect(content).toBeHidden();
    await expect(content).toHaveAttribute("data-open", "false");
});

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/core/dist/floating-ui.core.umd.min.js" });
    await page.addScriptTag({ path: "node_modules/@floating-ui/dom/dist/floating-ui.dom.umd.min.js" });
    await page.addScriptTag({ content: await bundle() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("hover-card", window.HoverCardController);
    });
}

async function bundle() {
    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    const transition = (await readFile("resources/js/controllers/_transition.js", "utf8"))
        .replace("export function enter", "function enter")
        .replace("export function leave", "function leave")
        .replace("export function cancel", "function cancel");

    const hoverCard = (await readFile("resources/js/controllers/hover_card_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_transition\.js";\s*/, "")
        .replace("export default class extends Controller", "class HoverCardController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${floating}
        ${transition}
        ${hoverCard}
        window.HoverCardController = HoverCardController;
    `;
}
