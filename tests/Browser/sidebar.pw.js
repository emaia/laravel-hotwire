import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture, replacePresetImport } from "../../scripts/css_build_contract.js";

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

for (const preset of ["nova", "bloom"]) {
    test.describe(`${preset} offcanvas geometry`, () => {
        let css;

        test.beforeAll(async () => {
            css = await compileCssFixture(
                replacePresetImport(await readFile("stubs/resources/css/app.css", "utf8"), preset),
            );
        });

        test("collapsing a parent preserves its nested provider's geometry", async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });

            for (const outerSide of ["left", "right"]) {
                for (const innerSide of ["left", "right"]) {
                    for (const variant of ["sidebar", "floating", "inset"]) {
                        await page.setContent(`<style>${css}</style>${nestedSidebars(outerSide, innerSide, variant)}`);

                        for (const mode of ["", "icon", "offcanvas"]) {
                            await test.step(`${outerSide} parent, ${innerSide} ${variant} child ${mode || "expanded"}`, async () => {
                                await collapse(page, "outer", "");
                                await collapse(page, "inner", mode);
                                const before = await geometry(page, "inner");

                                await expect(page.locator("#outer-gap")).toHaveCSS("width", "256px");
                                await expect(page.locator("#outer-container")).toHaveCSS(outerSide, "0px");

                                await collapse(page, "outer", "offcanvas");

                                await expect(page.locator("#outer-gap")).toHaveCSS("width", "0px");
                                await expect(page.locator("#outer-container")).toHaveCSS(outerSide, "-256px");
                                expect(await geometry(page, "inner")).toEqual(before);

                                await collapse(page, "outer", "");
                                expect(await geometry(page, "inner")).toEqual(before);
                            });
                        }
                    }
                }
            }
        });

        test("keeps nested mobile panels and application overrides independent", async ({ page }) => {
            await page.setViewportSize({ width: 767, height: 800 });

            for (const side of ["left", "right"]) {
                const innerSide = side === "left" ? "right" : "left";
                await page.setContent(`<style>${css}</style>${nestedSidebars(side, innerSide, "floating")}`);
                await collapse(page, "outer", "offcanvas");
                await collapse(page, "inner", "offcanvas");

                await page.locator("#outer, #inner").evaluateAll((sidebars) => {
                    for (const sidebar of sidebars) sidebar.dataset.mobileState = "open";
                });

                for (const [id, panelSide, width] of [["outer", side, "288px"], ["inner", innerSide, "240px"]]) {
                    await expect(page.locator(`#${id}-gap`)).toBeHidden();
                    await expect(page.locator(`#${id}-container`)).toHaveCSS(panelSide, "0px");
                    await expect(page.locator(`#${id}-container`)).toHaveCSS("width", width);
                    await expect(page.locator(`#${id}-container`)).toHaveCSS("translate", "0px");
                }

                await page.setViewportSize({ width: 768, height: 800 });
                await expect(page.locator("#outer-container")).toHaveCSS(side, "-256px");
                await expect(page.locator("#inner-container")).toHaveCSS(innerSide, "-320px");

                await page.addStyleTag({ content: `
                    @layer components {
                        [data-slot="sidebar-gap"] { width: 123px; }
                        [data-slot="sidebar-container"] { left: 42px; right: 42px; width: 111px; }
                    }
                ` });

                for (const id of ["outer", "inner"]) {
                    await expect(page.locator(`#${id}-gap`)).toHaveCSS("width", "123px");
                    await expect(page.locator(`#${id}-container`)).toHaveCSS("width", "111px");
                    await expect(page.locator(`#${id}-container`)).toHaveCSS("left", "42px");
                    await expect(page.locator(`#${id}-container`)).toHaveCSS("right", "42px");
                }

                await page.setViewportSize({ width: 767, height: 800 });
            }
        });
    });
}

function nestedSidebars(outerSide, innerSide, variant) {
    return `
        <style>[data-slot="sidebar-gap"], [data-slot="sidebar-container"] { transition: none !important; }</style>
        <div data-slot="sidebar-wrapper" style="--sidebar-width:256px; --sidebar-width-icon:48px; --sidebar-width-mobile:288px">
            <div id="outer" data-slot="sidebar" data-state="expanded" data-collapsible="" data-mobile-state="closed" data-side="${outerSide}" data-variant="sidebar">
                <div id="outer-gap" data-slot="sidebar-gap"></div>
                <div id="outer-container" data-slot="sidebar-container" data-side="${outerSide}">
                    <aside data-slot="sidebar-inner">
                        <div data-slot="sidebar-wrapper" style="--sidebar-width:320px; --sidebar-width-icon:64px; --sidebar-width-mobile:240px">
                            <div id="inner" data-slot="sidebar" data-state="expanded" data-collapsible="" data-mobile-state="closed" data-side="${innerSide}" data-variant="${variant}">
                                <div id="inner-gap" data-slot="sidebar-gap"></div>
                                <div id="inner-container" data-slot="sidebar-container" data-side="${innerSide}">
                                    <aside data-slot="sidebar-inner">Nested navigation</aside>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    `;
}

async function collapse(page, id, mode) {
    await page.locator(`#${id}`).evaluate((sidebar, value) => {
        sidebar.dataset.collapsible = value;
        sidebar.dataset.state = value === "" ? "expanded" : "collapsed";
    }, mode);
}

async function geometry(page, id) {
    return page.locator(`#${id}`).evaluate((sidebar) => {
        const gap = getComputedStyle(sidebar.querySelector(":scope > [data-slot=sidebar-gap]"));
        const container = getComputedStyle(sidebar.querySelector(":scope > [data-slot=sidebar-container]"));

        return { gap: gap.width, width: container.width, left: container.left, right: container.right };
    });
}
