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

for (const preset of ["nova", "bloom"]) {
    test.describe(`${preset} Sidebar composition`, () => {
        let css;

        test.beforeAll(async () => {
            css = await compileCssFixture(
                replacePresetImport(await readFile("stubs/resources/css/app.css", "utf8"), preset),
            );
        });

        test("keeps a provider in the main content independent of the shell Sidebar", async ({ page }) => {
            await page.setViewportSize({ width: 1280, height: 800 });

            for (const side of ["left", "right"]) {
                await page.setContent(`<style>${css}</style>${layout(side)}`);
                const contentGeometry = await geometry(page, "content");

                for (const [mode, width, offset] of [["icon", "48px", "0px"], ["offcanvas", "0px", "-256px"], ["", "256px", "0px"]]) {
                    await collapse(page, "shell", mode);
                    await expect(page.locator("#shell-gap")).toHaveCSS("width", width);
                    await expect(page.locator("#shell-container")).toHaveCSS(side, offset);
                    await expect(page.locator("#shell-rail")).toHaveCSS("translate", mode === "offcanvas" ? "0px" : "-50%");
                    expect(await geometry(page, "content")).toEqual(contentGeometry);
                }

                const shellGeometry = await geometry(page, "shell");
                await collapse(page, "content", "icon");
                await expect(page.locator("#content-gap")).toHaveCSS("width", "64px");
                expect(await geometry(page, "shell")).toEqual(shellGeometry);
            }
        });

        test("preserves mobile geometry and later application overrides", async ({ page }) => {
            await page.setViewportSize({ width: 767, height: 800 });
            await page.setContent(`<style>${css}</style>${layout("left")}`);

            for (const [id, side, width] of [["shell", "left", "288px"], ["content", "right", "240px"]]) {
                await collapse(page, id, "offcanvas");
                await page.locator(`#${id}`).evaluate((sidebar) => { sidebar.dataset.mobileState = "open"; });
                await expect(page.locator(`#${id}-gap`)).toBeHidden();
                await expect(page.locator(`#${id}-container`)).toHaveCSS(side, "0px");
                await expect(page.locator(`#${id}-container`)).toHaveCSS("width", width);
            }

            await page.setViewportSize({ width: 768, height: 800 });
            await expect(page.locator("#shell-container")).toHaveCSS("left", "-256px");
            await expect(page.locator("#content-container")).toHaveCSS("right", "-320px");

            await page.addStyleTag({ content: `
                @layer components {
                    [data-slot="sidebar-gap"] { width: 123px; }
                    [data-slot="sidebar-container"] { left: 42px; right: 42px; width: 111px; }
                }
            ` });

            for (const id of ["shell", "content"]) {
                await expect(page.locator(`#${id}-gap`)).toHaveCSS("width", "123px");
                await expect(page.locator(`#${id}-container`)).toHaveCSS("width", "111px");
                await expect(page.locator(`#${id}-container`)).toHaveCSS("left", "42px");
                await expect(page.locator(`#${id}-container`)).toHaveCSS("right", "42px");
            }
        });

        test("does not let a collapsed group label intercept menu button clicks", async ({ page }) => {
            await page.setViewportSize({ width: 1024, height: 800 });
            await page.setContent(`
                <style>${css}</style>
                <aside data-slot="sidebar" data-collapsible="icon" style="width: 48px">
                    <div data-slot="sidebar-group">
                        <div data-slot="sidebar-group-content">
                            <div data-slot="sidebar-menu-item">
                                <a id="menu-button" data-slot="sidebar-menu-button" data-size="default" href="#clicked">Item</a>
                            </div>
                        </div>
                    </div>
                    <div data-slot="sidebar-group">
                        <div data-slot="sidebar-group-label">Next group</div>
                        <div data-slot="sidebar-group-content"></div>
                    </div>
                </aside>
            `);

            const menuButton = page.locator("#menu-button");
            const box = await menuButton.boundingBox();

            expect(box).not.toBeNull();
            await page.mouse.click(box.x + box.width / 2, box.y + box.height - 2);

            await expect(page).toHaveURL(/#clicked$/);
        });
    });
}

function layout(side) {
    return `
        <style>
            [data-slot="sidebar-gap"], [data-slot="sidebar-container"], [data-slot="sidebar-rail"] { transition: none !important; }
        </style>
        <div data-slot="sidebar-wrapper" style="--sidebar-width:256px; --sidebar-width-icon:48px; --sidebar-width-mobile:288px">
            ${sidebar("shell", side)}
            <main data-slot="sidebar-inset">
                <div data-slot="sidebar-wrapper" style="--sidebar-width:320px; --sidebar-width-icon:64px; --sidebar-width-mobile:240px">
                    ${sidebar("content", side === "left" ? "right" : "left")}
                    <main data-slot="sidebar-inset">Workspace</main>
                </div>
            </main>
        </div>
    `;
}

function sidebar(id, side) {
    return `
        <div id="${id}" data-slot="sidebar" data-state="expanded" data-collapsible="" data-mobile-state="closed" data-side="${side}" data-variant="sidebar">
            <div id="${id}-gap" data-slot="sidebar-gap"></div>
            <div id="${id}-container" data-slot="sidebar-container" data-side="${side}">
                <aside data-slot="sidebar-inner">
                    <nav data-slot="sidebar-content">Navigation</nav>
                    <button id="${id}-rail" data-slot="sidebar-rail" type="button" tabindex="-1" aria-label="Toggle sidebar"></button>
                </aside>
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
        const style = (slot) => getComputedStyle(sidebar.querySelector(`[data-slot="${slot}"]`));
        const container = style("sidebar-container");
        const rail = style("sidebar-rail");

        return {
            gap: style("sidebar-gap").width,
            width: container.width, left: container.left, right: container.right,
            overflow: style("sidebar-content").overflowX,
            rail: { translate: rail.translate, cursor: rail.cursor },
        };
    });
}
