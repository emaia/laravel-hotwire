import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

const FIXTURE_CSS = `
    body { margin: 0; }
    #dialog { display: inline-block; }
    #panel { overflow: hidden; background: #ddd; }
    #scroll { overflow-x: hidden; overflow-y: auto; }
    .small { width: 100px; height: 100px; background: #eee; }
    .large { width: 300px; height: 200px; background: #ccc; }
`;

function buildFixture({ animateResize, resizeDuration = 200 }) {
    return `
        <style>${FIXTURE_CSS}</style>
        <div
            data-controller="modal"
            data-modal-open-duration-value="0"
            data-modal-close-duration-value="0"
            data-modal-animate-resize-value="${animateResize}"
            data-modal-resize-duration-value="${resizeDuration}"
        >
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
                <div id="dialog" data-modal-target="dialog">
                    <div id="panel" data-modal-target="panel">
                        <div id="scroll">
                            <turbo-frame id="modal-frame" data-modal-target="dynamicContent"></turbo-frame>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

async function mountModal(page, fixtureHtml) {
    await page.setContent(fixtureHtml);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });
}

test("dialog and panel animate between old and new size in sync when animate-resize is on", async ({ page }) => {
    await mountModal(page, buildFixture({ animateResize: true, resizeDuration: 500 }));

    const dialog = page.locator("#dialog");
    const panel = page.locator("#panel");
    const frame = page.locator("#modal-frame");
    const modal = page.locator('[data-modal-target="modal"]');

    await frame.evaluate((element) => {
        const small = document.createElement("div");
        small.className = "small";
        element.appendChild(small);
    });

    await expect(modal).toHaveAttribute("data-open", "true");
    await page.waitForTimeout(20);

    const dialogBefore = await dialog.boundingBox();
    const panelBefore = await panel.boundingBox();
    expect(dialogBefore?.width).toBe(100);
    expect(dialogBefore?.height).toBe(100);
    expect(panelBefore?.height).toBe(100);

    await frame.evaluate((element) => {
        element.innerHTML = '<div class="large"></div>';
    });

    // Mid-animation: BOTH dialog and panel are between old and new size.
    // The panel must not snap to its natural new height — that was the visible
    // bug where the white box jumped tall while only width animated.
    await page.waitForTimeout(150);
    const dialogMid = await dialog.boundingBox();
    const panelMid = await panel.boundingBox();
    expect(dialogMid?.width).toBeGreaterThan(100);
    expect(dialogMid?.width).toBeLessThan(300);
    expect(dialogMid?.height).toBeGreaterThan(100);
    expect(dialogMid?.height).toBeLessThan(200);
    expect(panelMid?.height).toBeGreaterThan(100);
    expect(panelMid?.height).toBeLessThan(200);
    expect(Math.abs((panelMid?.height ?? 0) - (dialogMid?.height ?? 0))).toBeLessThan(15);

    const midTransition = await dialog.evaluate((el) => el.style.transition);
    expect(midTransition).toContain("width 500ms");
    const panelMidTransition = await panel.evaluate((el) => el.style.transition);
    expect(panelMidTransition).toContain("height 500ms");

    // After the animation completes, inline locks are cleared on both elements
    // and they return to their natural new size.
    await page.waitForTimeout(500);
    const dialogAfter = await dialog.boundingBox();
    const panelAfter = await panel.boundingBox();
    expect(dialogAfter?.width).toBe(300);
    expect(dialogAfter?.height).toBe(200);
    expect(panelAfter?.height).toBe(200);

    const finalDialogWidth = await dialog.evaluate((el) => el.style.width);
    const finalDialogHeight = await dialog.evaluate((el) => el.style.height);
    const finalPanelHeight = await panel.evaluate((el) => el.style.height);
    expect(finalDialogWidth).toBe("");
    expect(finalDialogHeight).toBe("");
    expect(finalPanelHeight).toBe("");
});

test("dialog snaps to new size with no inline style when animate-resize is off", async ({ page }) => {
    await mountModal(page, buildFixture({ animateResize: false }));

    const dialog = page.locator("#dialog");
    const frame = page.locator("#modal-frame");
    const modal = page.locator('[data-modal-target="modal"]');

    await frame.evaluate((element) => {
        const small = document.createElement("div");
        small.className = "small";
        element.appendChild(small);
    });

    await expect(modal).toHaveAttribute("data-open", "true");
    await page.waitForTimeout(20);

    await frame.evaluate((element) => {
        element.innerHTML = '<div class="large"></div>';
    });

    // No FLIP — dialog jumps to natural size, no inline width/height applied.
    const width = await dialog.evaluate((el) => el.style.width);
    const height = await dialog.evaluate((el) => el.style.height);
    expect(width).toBe("");
    expect(height).toBe("");

    const box = await dialog.boundingBox();
    expect(box?.width).toBe(300);
    expect(box?.height).toBe(200);
});

async function browserControllerScript(path) {
    const source = await readFile(path, "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class ModalController extends Controller", "class ModalController extends Controller")
        .concat("\nwindow.ModalController = ModalController;\n");
}
