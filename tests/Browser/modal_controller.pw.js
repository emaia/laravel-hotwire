import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens when dynamic content is inserted and closes cleanly through the public API", async ({ page }) => {
    await page.setContent(`
        <div data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
            <div
                data-modal-target="modal"
                data-modal-hidden-class="hidden"
                data-modal-visible-class="visible"
                data-modal-backdrop-hidden-class="backdrop-hidden"
                data-modal-backdrop-visible-class="backdrop-visible"
                data-modal-dialog-hidden-class="dialog-hidden"
                data-modal-dialog-visible-class="dialog-visible"
                data-modal-lock-scroll-class="overflow-hidden"
                hidden
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <turbo-frame id="modal-frame" data-modal-target="dynamicContent"></turbo-frame>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    const frame = page.locator("#modal-frame");
    const modal = page.locator('[data-modal-target="modal"]');

    await frame.evaluate((element) => {
        const content = document.createElement("div");
        content.textContent = "Loaded content";
        element.appendChild(content);
    });

    await expect(modal).toHaveAttribute("data-open", "true");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(frame).toContainText("Loaded content");

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");

        controller.close();
    });

    await expect(modal).toHaveAttribute("data-open", "false");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect(frame).toBeEmpty();
});

test("tabs from the modal close button into native accordion summaries", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
            <button id="open-modal" data-action="modal#open">Open modal</button>
            <div
                data-modal-target="modal"
                data-modal-hidden-class="hidden"
                data-modal-visible-class="visible"
                data-modal-backdrop-hidden-class="backdrop-hidden"
                data-modal-backdrop-visible-class="backdrop-visible"
                data-modal-dialog-hidden-class="dialog-hidden"
                data-modal-dialog-visible-class="dialog-visible"
                data-modal-lock-scroll-class="overflow-hidden"
                hidden
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="close-modal" type="button" data-action="modal#close">Close</button>
                    <section data-controller="accordion" data-accordion-type-value="single">
                        <details data-accordion-target="item" data-value="billing">
                            <summary id="billing-summary">Billing</summary>
                            <section>Billing answers.</section>
                        </details>
                    </section>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-modal").click();
    await expect(page.locator("#close-modal")).toBeFocused();

    await page.keyboard.press("Tab");
    await expect(page.locator("#billing-summary")).toBeFocused();
});

test("nested modal overlay enters the browser top layer", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
            [data-hotwire-top-layer][popover][data-slot="modal-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-backdrop"] { position: absolute; inset: 0; }
            [data-slot="modal-positioner"] { position: relative; z-index: 1; }
            .hidden { pointer-events: none; }
            .visible { pointer-events: auto; }
            .dialog-hidden { opacity: 0; transform: scale(.8); }
            .dialog-visible { opacity: 1; transform: scale(1); }
        </style>
        <div id="outer" data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-modal-hidden-class="hidden" data-modal-visible-class="visible" data-modal-backdrop-hidden-class="hidden" data-modal-backdrop-visible-class="visible" data-modal-dialog-hidden-class="dialog-hidden" data-modal-dialog-visible-class="dialog-visible" data-modal-lock-scroll-class="overflow-hidden" hidden>
                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <section style="width: 260px; height: 140px; overflow: hidden; transform: scale(.95); border-radius: 16px; background: white;">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div id="inner" data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
                            <div data-slot="modal-overlay" data-modal-target="modal" data-modal-hidden-class="hidden" data-modal-visible-class="visible" data-modal-backdrop-hidden-class="hidden" data-modal-backdrop-visible-class="visible" data-modal-dialog-hidden-class="dialog-hidden" data-modal-dialog-visible-class="dialog-visible" data-modal-lock-scroll-class="overflow-hidden" hidden>
                                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                                <div data-slot="modal-positioner" data-modal-target="dialog">
                                    <button id="inner-close" data-action="modal#close">Close inner</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-outer").click();
    await page.evaluate(() => {
        const root = document.querySelector("#inner");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");

        controller.open({ target: document.querySelector("#open-inner") });
    });

    const innerOverlay = page.locator("#inner [data-modal-target='modal']");

    await expect(innerOverlay).toHaveAttribute("popover", "manual");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => innerOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect.poll(async () => innerOverlay.evaluate((element) => {
        const rect = element.getBoundingClientRect();

        return { width: Math.round(rect.width), height: Math.round(rect.height) };
    })).toEqual({ width: 1280, height: 720 });
});

test("nested modal and alert dialog close one layer at a time with Escape", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
            [data-hotwire-top-layer][popover]:is([data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"]) { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-backdrop"], [data-slot="alert-dialog-backdrop"] { position: absolute; inset: 0; }
            [data-slot="modal-positioner"], [data-slot="alert-dialog-panel"] { position: relative; z-index: 1; background: white; }
            .hidden { pointer-events: none; }
            .visible { pointer-events: auto; }
            .dialog-hidden { opacity: 0; transform: scale(.8); }
            .dialog-visible { opacity: 1; transform: scale(1); }
        </style>
        <div id="outer" data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-modal-hidden-class="hidden" data-modal-visible-class="visible" data-modal-backdrop-hidden-class="hidden" data-modal-backdrop-visible-class="visible" data-modal-dialog-hidden-class="dialog-hidden" data-modal-dialog-visible-class="dialog-visible" data-modal-lock-scroll-class="overflow-hidden" hidden>
                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <button id="outer-close" data-action="modal#close">Close outer</button>
                    <div id="inner" data-controller="modal" data-modal-open-duration-value="0" data-modal-close-duration-value="0">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div data-slot="modal-overlay" data-modal-target="modal" data-modal-hidden-class="hidden" data-modal-visible-class="visible" data-modal-backdrop-hidden-class="hidden" data-modal-backdrop-visible-class="visible" data-modal-dialog-hidden-class="dialog-hidden" data-modal-dialog-visible-class="dialog-visible" data-modal-lock-scroll-class="overflow-hidden" hidden>
                            <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                            <div data-slot="modal-positioner" data-modal-target="dialog">
                                <button id="inner-close" data-action="modal#close">Close inner</button>
                                <div id="confirm" data-controller="alert-dialog" data-alert-dialog-open-duration-value="0" data-alert-dialog-close-duration-value="0" data-alert-dialog-hidden-class="hidden" data-alert-dialog-visible-class="visible" data-alert-dialog-backdrop-hidden-class="hidden" data-alert-dialog-backdrop-visible-class="visible" data-alert-dialog-dialog-hidden-class="dialog-hidden" data-alert-dialog-dialog-visible-class="dialog-visible" data-alert-dialog-lock-scroll-class="overflow-hidden">
                                    <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                                    <div data-slot="alert-dialog-overlay" data-alert-dialog-target="modal" data-open="false" data-action="click->alert-dialog#clickOutside" hidden>
                                        <div data-slot="alert-dialog-backdrop" data-alert-dialog-target="backdrop"></div>
                                        <div data-slot="alert-dialog-panel" data-alert-dialog-target="dialog">
                                            <button id="cancel" data-action="alert-dialog#cancel">Cancel</button>
                                            <button id="confirm-action" data-action="alert-dialog#confirm">Confirm</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    await page.locator("#open-outer").click();
    await page.locator("#open-inner").click();
    await page.locator("#delete").click();

    const outerOverlay = page.locator("#outer > [data-modal-target='modal']");
    const innerOverlay = page.locator("#inner > [data-modal-target='modal']");
    const alertOverlay = page.locator("#confirm [data-alert-dialog-target='modal']");

    await expect(alertOverlay).not.toHaveAttribute("hidden", "");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await page.keyboard.press("Escape");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");

    await page.keyboard.press("Escape");
    await expect(innerOverlay).toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");

    await page.keyboard.press("Escape");
    await expect(outerOverlay).toHaveAttribute("hidden", "");
});

async function browserControllerScript(path) {
    // Inline helper modules alongside the controller — ES `import` is not valid
    // inside a regular <script>, so the harness concatenates the source instead.
    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace("export class FocusTrap", "class FocusTrap");

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function isTopOverlay", "function isTopOverlay");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8"))
        .replace("export function createTopLayer", "function createTopLayer");

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_stack\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

    const frameOverlay = (await readFile("resources/js/controllers/_frame_overlay.js", "utf8"))
        .replace("export function createFrameOverlay", "function createFrameOverlay");

    const source = (await readFile(path, "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace("export default class ModalController extends Controller", "class ModalController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${overlay}
        ${frameOverlay}
        ${source}
        window.ModalController = ModalController;
    `;
}

async function browserOverlayControllerScript() {
    const base = await browserControllerScript("resources/js/controllers/modal_controller.js");
    const alertDialog = (await readFile("resources/js/controllers/alert_dialog_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace("export default class AlertDialogController extends Controller", "class AlertDialogController extends Controller");

    return `
        ${base.replace("window.ModalController = ModalController;", "")}
        ${alertDialog}
        window.ModalController = ModalController;
        window.AlertDialogController = AlertDialogController;
    `;
}
