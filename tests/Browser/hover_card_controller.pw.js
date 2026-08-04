import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens on hover, stays open over content and closes after leave", async ({ page }) => {
    await page.setContent(`
        <style>
            [data-hover-card-target="content"] { transition: opacity 40ms linear, scale 40ms linear; }
            [data-hover-card-target="content"][data-state="closed"] { opacity: 0; pointer-events: none; scale: .95; }
            [data-hover-card-target="content"][data-state="open"] { opacity: 1; scale: 1; }
            [data-hover-card-target="content"][data-presence="instant"] { transition: none; }
            [data-hotwire-top-layer][popover] { border: 0; inset: auto; margin: 0; }
        </style>
        <div data-controller="hover-card" data-hover-card-open-delay-value="30" data-hover-card-close-delay-value="30">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0" aria-expanded="false">Jane Doe</span>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert
                 data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">
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
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0" aria-expanded="false">Account</span>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>Account preview</div>
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

test("keeps focus inside content when close delay is zero", async ({ page }) => {
    await page.setContent(`
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
            <button data-hover-card-target="trigger"
                    data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
                    aria-expanded="false">Account</button>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert
                 data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">
                <a id="profile-link" href="#profile">Profile</a>
            </div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-hover-card-target="trigger"]');
    const content = page.locator('[data-hover-card-target="content"]');
    const link = page.locator("#profile-link");

    await trigger.focus();
    await expect(content).toBeVisible();
    await expect(content).toHaveAttribute("data-state", "open");
    await page.keyboard.press("Tab");

    await expect(link).toBeFocused();
    await expect(content).toBeVisible();
    await expect(trigger).toHaveAttribute("aria-expanded", "true");
});

test("stays open when its active trigger is removed while content is hovered", async ({ page }) => {
    await page.setContent(`
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="20">
            <button id="active-trigger" data-hover-card-target="trigger"
                    data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">Active</button>
            <button id="fallback-trigger" data-hover-card-target="trigger"
                    data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">Fallback</button>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert
                 data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">
                Preview
            </div>
        </div>
    `);

    await installControllers(page);

    const content = page.locator('[data-hover-card-target="content"]');
    await page.locator("#active-trigger").hover();
    await expect(content).toBeVisible();
    await content.hover();

    await page.locator("#active-trigger").evaluate((trigger) => trigger.remove());
    await page.waitForTimeout(50);

    await expect(content).toBeVisible();
    await expect(page.locator("#fallback-trigger")).toHaveAttribute("aria-expanded", "true");
});

test("restores focus when the active trigger is morphed", async ({ page }) => {
    await page.setContent(`
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
            <button id="first-trigger" data-hover-card-target="trigger"
                    data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">First</button>
            <button id="morphed-trigger" data-hover-card-target="trigger"
                    data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut">Account</button>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>Preview</div>
        </div>
    `);
    await installControllers(page);

    const content = page.locator('[data-hover-card-target="content"]');
    await page.locator("#morphed-trigger").focus();
    await expect(content).toBeVisible();

    await page.locator("#morphed-trigger").evaluate((trigger) => {
        const root = trigger.parentElement;
        const content = root.querySelector('[data-hover-card-target="content"]');
        const replacements = [...root.querySelectorAll('[data-hover-card-target="trigger"]')]
            .map((candidate) => candidate.cloneNode(true));
        const inserted = document.createElement("button");
        inserted.id = "inserted-trigger";
        inserted.dataset.hoverCardTarget = "trigger";
        inserted.dataset.action = "mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut";
        inserted.textContent = "Inserted";
        root.replaceChildren(inserted, ...replacements, content);
    });

    await expect(page.locator("#morphed-trigger")).toBeFocused();
    await expect(content).toBeVisible();
});

test("positions content inside a Turbo Frame with Floating UI", async ({ page }) => {
    await page.setContent(`
        <style>
            body { margin: 0; }
            [data-hover-card-target="trigger"] { display: inline-block; margin-left: 120px; margin-top: 80px; width: 140px; height: 32px; }
            [data-hover-card-target="content"] { width: var(--anchor-width); min-width: 8rem; }
        </style>
        <turbo-frame id="users-frame">
            <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-side-offset-value="4">
                <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0">User</span>
                <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>Preview</div>
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
        <div data-controller="hover-card" data-hover-card-open-delay-value="0" data-hover-card-close-delay-value="0">
            <span data-hover-card-target="trigger" data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut" tabindex="0">User</span>
            <div data-hover-card-target="content" data-state="closed" data-motion="default" hidden inert>Preview</div>
        </div>
    `);

    await installControllers(page);

    const trigger = page.locator('[data-hover-card-target="trigger"]');
    const content = page.locator('[data-hover-card-target="content"]');

    await trigger.hover();
    await expect(content).toBeVisible();

    await page.evaluate(() => document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true })));
    await expect(content).toBeHidden();
    await expect(content).toHaveAttribute("data-state", "closed");
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
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");
    const floating = (await readFile("resources/js/controllers/_floating.js", "utf8"))
        .replace(/import \{[^}]*\} from "@floating-ui\/dom";\s*/, "")
        .replace("export function createFloating", "function createFloating");

    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8"))
        .replace("export function createPresence", "function createPresence");

    const hoverCard = (await readFile("resources/js/controllers/hover_card_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_floating\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export default class extends Controller", "class HoverCardController extends Controller");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8"))
        .replace("export function createTopLayer", "function createTopLayer");

    return `
        const { Controller } = window.Stimulus;
        const { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } = window.FloatingUIDOM;
        ${composition}
        ${floating}
        ${presence}
        ${topLayer}
        ${hoverCard}
        window.HoverCardController = HoverCardController;
    `;
}
