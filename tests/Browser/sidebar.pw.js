import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("icon mode keeps overflowing content vertically reachable", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");
    const items = Array.from(
        { length: 20 },
        (_, index) => `<a id="item-${index + 1}" href="#${index + 1}">Item ${index + 1}</a>`,
    ).join("");

    await page.setViewportSize({ width: 1024, height: 480 });
    await page.setContent(`
        <style>
            ${structural}
            [data-slot="sidebar-inner"] { display: flex; flex-direction: column; height: 100vh; }
            [data-slot="sidebar-header"] { flex: none; height: 48px; }
            [data-slot="sidebar-content"] > a { display: block; flex: none; height: 48px; }
        </style>
        <aside data-slot="sidebar" data-collapsible="icon">
            <div data-slot="sidebar-inner">
                <header data-slot="sidebar-header">Brand</header>
                <nav data-slot="sidebar-content">${items}</nav>
            </div>
        </aside>
    `);

    const content = page.locator('[data-slot="sidebar-content"]');
    const metrics = await content.evaluate((element) => ({
        clientHeight: element.clientHeight,
        overflowX: getComputedStyle(element).overflowX,
        overflowY: getComputedStyle(element).overflowY,
        scrollHeight: element.scrollHeight,
    }));

    expect(metrics.scrollHeight).toBeGreaterThan(metrics.clientHeight);
    expect(metrics.overflowX).toBe("hidden");
    expect(metrics.overflowY).toBe("auto");

    await content.evaluate((element) => (element.scrollTop = element.scrollHeight));
    await expect(page.locator("#item-20")).toBeInViewport();
});

test("icon mode styles stop at a nested sidebar provider", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");

    await page.setViewportSize({ width: 1024, height: 768 });
    await page.setContent(`
        <style>${structural}</style>
        <div data-slot="sidebar-wrapper">
            <aside data-slot="sidebar" data-collapsible="icon">
                <nav id="outer-content" data-slot="sidebar-content">
                    <div data-slot="sidebar-wrapper">
                        <aside data-slot="sidebar" data-collapsible="">
                            <nav id="nested-content" data-slot="sidebar-content"></nav>
                        </aside>
                    </div>
                </nav>
            </aside>
            <main data-slot="sidebar-inset">
                <div data-slot="sidebar-wrapper">
                    <aside data-slot="sidebar" data-collapsible="">
                        <nav id="inset-content" data-slot="sidebar-content"></nav>
                    </aside>
                </div>
            </main>
        </div>
    `);

    await expect(page.locator("#outer-content")).toHaveCSS("overflow-x", "hidden");
    await expect(page.locator("#nested-content")).toHaveCSS("overflow-x", "auto");
    await expect(page.locator("#inset-content")).toHaveCSS("overflow-x", "auto");
});
