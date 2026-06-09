import { expect, test } from "@playwright/test";

// HTML mirrors what <x-hwc::modal size="full"> renders in the open state.
// CSS is hand-written for the subset of Tailwind classes that affect layout —
// the goal is to verify the size=full container cascade (dialog → panel → scroll)
// fills viewport minus overlay padding, independent of the user's Tailwind build.
const SIZE_FULL_FIXTURE = `
<!DOCTYPE html>
<html>
<head>
<style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; height: 100%; }
    .overlay {
        position: fixed; inset: 0; z-index: 50;
        display: flex; flex-wrap: wrap;
        align-items: center; justify-content: center;
        padding: 40px; /* md:p-10 */
    }
    .backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); }
    .dialog { position: relative; z-10: 10; max-width: 100%; width: 100%; height: 100%; }
    .panel  { overflow: hidden; background: white; display: flex; flex-direction: column; height: 100%; }
    .scroll { width: 100%; overflow-y: auto; flex: 1 1 0%; }
    .close  { position: absolute; top: 8px; right: 8px; z-index: 10; }
</style>
</head>
<body>
    <div role="dialog" aria-modal="true" class="overlay">
        <div class="backdrop"></div>
        <div data-testid="dialog" class="dialog">
            <div data-testid="panel" class="panel">
                <div data-testid="scroll" class="scroll">
                    <p>tiny content</p>
                </div>
            </div>
            <button data-testid="close" class="close" type="button">×</button>
        </div>
    </div>
</body>
</html>
`;

test("size=full fills viewport minus overlay padding through the entire container cascade", async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.setContent(SIZE_FULL_FIXTURE);

    // p-10 = 40px each side → expected available area is 1200 × 640.
    const expectedWidth = 1280 - 80;
    const expectedHeight = 720 - 80;

    const dialog = await page.locator('[data-testid="dialog"]').boundingBox();
    expect(dialog?.width).toBe(expectedWidth);
    expect(dialog?.height).toBe(expectedHeight);

    const panel = await page.locator('[data-testid="panel"]').boundingBox();
    expect(panel?.width).toBe(expectedWidth);
    expect(panel?.height).toBe(expectedHeight);

    const scroll = await page.locator('[data-testid="scroll"]').boundingBox();
    expect(scroll?.width).toBe(expectedWidth);
    expect(scroll?.height).toBe(expectedHeight);
});

test("size=full close button stays inside the dialog bounds", async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.setContent(SIZE_FULL_FIXTURE);

    const dialog = await page.locator('[data-testid="dialog"]').boundingBox();
    const close = await page.locator('[data-testid="close"]').boundingBox();

    expect(close?.x).toBeGreaterThanOrEqual(dialog?.x ?? 0);
    expect(close?.y).toBeGreaterThanOrEqual(dialog?.y ?? 0);
    expect((close?.x ?? 0) + (close?.width ?? 0)).toBeLessThanOrEqual((dialog?.x ?? 0) + (dialog?.width ?? 0));
    expect((close?.y ?? 0) + (close?.height ?? 0)).toBeLessThanOrEqual((dialog?.y ?? 0) + (dialog?.height ?? 0));
});
