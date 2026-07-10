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

async function browserControllerScript(path) {
    // Inline helper modules alongside the controller — ES `import` is not valid
    // inside a regular <script>, so the harness concatenates the source instead.
    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace("export class FocusTrap", "class FocusTrap");

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
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
        ${overlay}
        ${frameOverlay}
        ${source}
        window.ModalController = ModalController;
    `;
}
