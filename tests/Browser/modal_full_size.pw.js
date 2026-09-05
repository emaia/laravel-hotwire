import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture } from "../../scripts/css_build_contract.js";

let presetCss;

test.beforeAll(async () => {
    presetCss = await compileCssFixture(await readFile("stubs/resources/css/app.css", "utf8"));
});

function modalFixture(content, { frame = false, size = "full" } = {}) {
    return `
        <style>${presetCss}</style>
        <div data-slot="modal-overlay" data-state="open">
            <div data-slot="modal-backdrop"></div>
            <div data-slot="modal-positioner" data-size="${size}">
                <div data-slot="modal-panel" data-size="${size}">
                    <div data-slot="modal-content" data-size="${size}">
                        ${frame ? `<turbo-frame id="modal-frame" data-modal-frame-owner="modal">${content}</turbo-frame>` : content}
                    </div>
                    <button data-slot="modal-close-icon" type="button" aria-label="Close modal">Close</button>
                </div>
            </div>
        </div>
    `;
}

function modalContent({ long = false } = {}) {
    const footer = '<div data-slot="modal-footer"><button type="button">Save</button></div>';

    return `
        <div data-slot="modal-header"><div data-slot="modal-title">Title</div></div>
        <div data-testid="modal-body" style="height: ${long ? 900 : 40}px">Body</div>
        ${footer}
    `;
}

test("size=full keeps the modal stack aligned within desktop and mobile viewports", async ({ page }) => {
    for (const viewport of [
        { width: 1280, height: 720 },
        { width: 390, height: 640 },
    ]) {
        await page.setViewportSize(viewport);
        await page.setContent(modalFixture(modalContent()));

        const { innerBounds, positioner, panel, content } = await page
            .locator('[data-slot="modal-overlay"]')
            .evaluate((overlay) => {
                const overlayBox = overlay.getBoundingClientRect();
                const style = getComputedStyle(overlay);
                const paddingLeft = Number.parseFloat(style.paddingLeft);
                const paddingRight = Number.parseFloat(style.paddingRight);
                const paddingTop = Number.parseFloat(style.paddingTop);
                const paddingBottom = Number.parseFloat(style.paddingBottom);
                const box = (selector) => overlay.querySelector(selector).getBoundingClientRect().toJSON();

                return {
                    innerBounds: {
                        x: overlayBox.left + paddingLeft,
                        y: overlayBox.top + paddingTop,
                        width: overlayBox.width - paddingLeft - paddingRight,
                        height: overlayBox.height - paddingTop - paddingBottom,
                    },
                    positioner: box('[data-slot="modal-positioner"]'),
                    panel: box('[data-slot="modal-panel"]'),
                    content: box('[data-slot="modal-content"]'),
                };
            });

        expect(positioner, `positioner at ${viewport.width}px`).toMatchObject(innerBounds);
        expect(panel, `panel at ${viewport.width}px`).toEqual(positioner);
        expect(content, `content at ${viewport.width}px`).toEqual(positioner);
    }
});

test("size=full keeps direct and frame-backed footer rows at their natural height", async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.setContent(modalFixture(modalContent()));
    const directHeight = (await page.locator('[data-slot="modal-footer"]').boundingBox())?.height;

    await page.setContent(modalFixture(modalContent(), { frame: true }));
    const frameHeight = (await page.locator('[data-slot="modal-footer"]').boundingBox())?.height;

    expect(directHeight).toBe(frameHeight);
});

test("frame-backed content keeps the same slot gaps as direct content", async ({ page }) => {
    await page.setContent(modalFixture(modalContent(), { size: "md" }));
    const directGaps = await page.locator('[data-slot="modal-content"]').evaluate(slotGaps);

    await page.setContent(modalFixture(modalContent(), { frame: true, size: "md" }));
    const frameGaps = await page.locator("#modal-frame").evaluate(slotGaps);

    expect(frameGaps).toEqual(directGaps);
});

test("the footer after long content stays in the scroll flow", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 640 });
    await page.setContent(modalFixture(modalContent({ long: true })));

    const content = page.locator('[data-slot="modal-content"]');
    const footer = page.locator('[data-slot="modal-footer"]');
    const contentBox = await content.evaluate((element) => element.getBoundingClientRect().toJSON());
    const footerBefore = await footer.evaluate((element) => element.getBoundingClientRect().toJSON());

    expect(footerBefore.top).toBeGreaterThanOrEqual(contentBox.bottom);

    await content.evaluate((element) => {
        element.scrollTop = element.scrollHeight;
    });

    const footerAfter = await footer.evaluate((element) => element.getBoundingClientRect().toJSON());
    expect(await content.evaluate((element) => element.scrollTop)).toBeGreaterThan(0);
    expect(footerAfter.bottom).toBeLessThanOrEqual(contentBox.bottom);
});

test("the close button stays inside the full dialog bounds", async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.setContent(modalFixture(modalContent()));

    const panel = await page.locator('[data-slot="modal-panel"]').boundingBox();
    const close = await page.locator('[data-slot="modal-close-icon"]').boundingBox();

    expect(close?.x).toBeGreaterThanOrEqual(panel?.x ?? 0);
    expect(close?.y).toBeGreaterThanOrEqual(panel?.y ?? 0);
    expect((close?.x ?? 0) + (close?.width ?? 0)).toBeLessThanOrEqual((panel?.x ?? 0) + (panel?.width ?? 0));
    expect((close?.y ?? 0) + (close?.height ?? 0)).toBeLessThanOrEqual((panel?.y ?? 0) + (panel?.height ?? 0));
});

function slotGaps(container) {
    const header = container.querySelector('[data-slot="modal-header"]').getBoundingClientRect();
    const body = container.querySelector('[data-testid="modal-body"]').getBoundingClientRect();
    const footer = container.querySelector('[data-slot="modal-footer"]').getBoundingClientRect();

    return [body.top - header.bottom, footer.top - body.bottom];
}
