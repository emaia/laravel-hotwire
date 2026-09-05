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

function modalContent({ footerLast = true, long = false } = {}) {
    const footer = '<div data-slot="modal-footer"><button type="button">Save</button></div>';
    const afterFooter = footerLast ? "" : '<div data-testid="after-footer">After footer</div>';

    return `
        <div data-slot="modal-header"><div data-slot="modal-title">Title</div></div>
        <div data-testid="modal-body" style="height: ${long ? 900 : 40}px">Body</div>
        ${footer}
        ${afterFooter}
    `;
}

test("size=full fills the padded viewport on desktop and mobile", async ({ page }) => {
    for (const viewport of [
        { width: 1280, height: 720, padding: 40 },
        { width: 390, height: 640, padding: 8 },
    ]) {
        await page.setViewportSize(viewport);
        await page.setContent(modalFixture(modalContent()));

        for (const slot of ["modal-positioner", "modal-panel", "modal-content"]) {
            const box = await page.locator(`[data-slot="${slot}"]`).boundingBox();

            expect(box?.width, `${slot} width at ${viewport.width}px`).toBe(viewport.width - viewport.padding * 2);
            expect(box?.height, `${slot} height at ${viewport.width}px`).toBe(viewport.height - viewport.padding * 2);
        }
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
    await page.setContent(modalFixture(modalContent(), { frame: true, size: "md" }));

    const gaps = await page.locator("#modal-frame").evaluate((frame) => {
        const header = frame.querySelector('[data-slot="modal-header"]').getBoundingClientRect();
        const body = frame.querySelector('[data-testid="modal-body"]').getBoundingClientRect();
        const footer = frame.querySelector('[data-slot="modal-footer"]').getBoundingClientRect();

        return [body.top - header.bottom, footer.top - body.bottom];
    });

    expect(gaps).toEqual([16, 16]);
});

test("only a terminal footer receives the edge-to-edge surface", async ({ page }) => {
    await page.setContent(modalFixture(modalContent({ footerLast: false }), { size: "md" }));

    const nonFinal = await page.locator('[data-slot="modal-footer"]').evaluate((footer) => {
        const footerBox = footer.getBoundingClientRect();
        const siblingBox = footer.nextElementSibling.getBoundingClientRect();
        const style = getComputedStyle(footer);

        return {
            gap: siblingBox.top - footerBox.bottom,
            marginBottom: style.marginBottom,
            marginInlineStart: style.marginInlineStart,
            radius: style.borderBottomLeftRadius,
            borderTop: style.borderTopWidth,
            background: style.backgroundColor,
        };
    });

    expect(nonFinal).toEqual({
        gap: 16,
        marginBottom: "0px",
        marginInlineStart: "0px",
        radius: "0px",
        borderTop: "0px",
        background: "rgba(0, 0, 0, 0)",
    });

    await page.setContent(modalFixture(modalContent(), { size: "md" }));
    const terminal = await page.locator('[data-slot="modal-footer"]').evaluate((footer) => {
        const style = getComputedStyle(footer);

        return {
            marginBottom: style.marginBottom,
            marginInlineStart: style.marginInlineStart,
            radius: style.borderBottomLeftRadius,
            borderTop: style.borderTopWidth,
            background: style.backgroundColor,
        };
    });

    expect(terminal.marginBottom).toBe("-16px");
    expect(terminal.marginInlineStart).toBe("-16px");
    expect(terminal.radius).not.toBe("0px");
    expect(terminal.borderTop).toBe("1px");
    expect(terminal.background).not.toBe("rgba(0, 0, 0, 0)");
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
