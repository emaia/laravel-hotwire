import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test.use({ reducedMotion: "no-preference" });

test("server-rendered collapsed state applies before the controller installs", async ({ page }) => {
    await page.setContent(await fixture(false, false));

    const content = page.locator("#project-panel-content");
    await expect
        .poll(() => page.locator("#project-panel").evaluate((element) => element.getBoundingClientRect().width))
        .toBe(28);
    await expect(content).toHaveCSS("opacity", "0");
    await expect(page.locator("#project-panel")).toHaveAttribute("inert", "");
});

test("structural CSS collapses the panel and grows the inset", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);

    const root = page.locator("#layout");
    const panel = page.locator("#project-panel");
    const inset = page.locator("#project-inset");
    const trigger = page.locator("#project-trigger");
    const content = page.locator("#project-panel-content");

    await expect.poll(() => panel.evaluate((element) => element.getBoundingClientRect().width)).toBe(240);
    await expect.poll(() => inset.evaluate((element) => element.getBoundingClientRect().width)).toBe(560);
    await expect(content).toHaveCSS("opacity", "1");

    await trigger.click();

    await expect(root).toHaveAttribute("data-state", "collapsed");
    await expect(panel).toHaveAttribute("inert", "");
    await expect(trigger).toHaveAttribute("aria-expanded", "false");
    await page.waitForTimeout(80);
    const movingWidth = await panel.evaluate((element) => element.getBoundingClientRect().width);
    expect(movingWidth).toBeGreaterThan(0);
    expect(movingWidth).toBeLessThan(240);
    await expect.poll(() => panel.evaluate((element) => element.getBoundingClientRect().width)).toBe(28);
    await expect.poll(() => inset.evaluate((element) => element.getBoundingClientRect().width)).toBe(772);
    await expect(content).toHaveCSS("opacity", "0");
    const collapsedBounds = await page.locator("#layout").evaluate((root) => {
        const rootBox = root.getBoundingClientRect();
        const panelBox = root.querySelector("#project-panel").getBoundingClientRect();
        const triggerBox = root.querySelector("#project-trigger").getBoundingClientRect();
        const railPosition = Number.parseFloat(getComputedStyle(root, "::before").left);

        return {
            panelRight: panelBox.right,
            railX: rootBox.left + railPosition,
            rootLeft: rootBox.left,
            rootRight: rootBox.right,
            triggerCenter: triggerBox.left + triggerBox.width / 2,
            triggerLeft: triggerBox.left,
            triggerRight: triggerBox.right,
        };
    });
    expect(collapsedBounds.triggerCenter).toBe(collapsedBounds.panelRight);
    expect(collapsedBounds.triggerCenter).toBe(collapsedBounds.railX);
    expect(collapsedBounds.triggerLeft).toBeGreaterThanOrEqual(collapsedBounds.rootLeft);
    expect(collapsedBounds.triggerRight).toBeLessThanOrEqual(collapsedBounds.rootRight);
    await page.locator("#before-layout").focus();
    await page.keyboard.press("Tab");
    await expect(trigger).toBeFocused();
});

for (const [context, wrap] of [
    [
        "Modal",
        (content) =>
            `<section data-slot="modal"><div data-slot="modal-overlay" data-state="open" role="dialog" aria-modal="true"><div data-slot="modal-positioner"><div data-slot="modal-panel"><div data-slot="modal-content">${content}</div></div></div></div></section>`,
    ],
    [
        "Dropdown",
        (content) =>
            `<div data-slot="dropdown"><button data-slot="dropdown-trigger" aria-expanded="true">Menu</button><div data-slot="dropdown-menu" data-state="open">${content}</div></div>`,
    ],
    ["Turbo Frame", (content) => `<turbo-frame id="context">${content}</turbo-frame>`],
]) {
    test(`operates inside ${context}`, async ({ page }) => {
        await page.setContent(wrap(await fixture()));
        await installController(page);

        await page.locator("#project-trigger").click();

        await expect(page.locator("#layout")).toHaveAttribute("data-state", "collapsed");
    });
}

test("side panel and app sidebar roots do not mutate each other", async ({ page }) => {
    await page.setContent(await fixture(true));
    await installController(page);

    const appSidebar = page.locator("#app-sidebar");
    const sidePanel = page.locator("#layout");

    await page.locator("#project-trigger").click();

    await expect(sidePanel).toHaveAttribute("data-state", "collapsed");
    await expect(appSidebar).toHaveAttribute("data-state", "expanded");

    await appSidebar.evaluate((element) => (element.dataset.state = "collapsed"));

    await expect(sidePanel).toHaveAttribute("data-state", "collapsed");
});

test("right side keeps the panel on the inline end and collapses without overlay behavior", async ({ page }) => {
    await page.setContent(await fixture(false, true, "right"));
    await installController(page);

    const panel = page.locator("#project-panel");
    const inset = page.locator("#project-inset");
    const initial = await page.locator("#layout").evaluate((root) => {
        const panel = root.querySelector("#project-panel").getBoundingClientRect();
        const inset = root.querySelector("#project-inset").getBoundingClientRect();
        return { panelLeft: panel.left, insetRight: inset.right };
    });

    expect(initial.panelLeft).toBe(initial.insetRight);
    await page.locator("#project-trigger").click();

    await expect.poll(() => panel.evaluate((element) => element.getBoundingClientRect().width)).toBe(28);
    await expect.poll(() => inset.evaluate((element) => element.getBoundingClientRect().width)).toBe(772);
    const collapsed = await page.locator("#layout").evaluate((root) => {
        const rootBox = root.getBoundingClientRect();
        const panelBox = root.querySelector("#project-panel").getBoundingClientRect();
        const triggerBox = root.querySelector("#project-trigger").getBoundingClientRect();
        const railPosition = Number.parseFloat(getComputedStyle(root, "::before").right);

        return {
            panelLeft: panelBox.left,
            railX: rootBox.right - railPosition,
            rootLeft: rootBox.left,
            rootRight: rootBox.right,
            triggerCenter: triggerBox.left + triggerBox.width / 2,
            triggerLeft: triggerBox.left,
            triggerRight: triggerBox.right,
        };
    });
    expect(collapsed.triggerCenter).toBe(collapsed.panelLeft);
    expect(collapsed.triggerCenter).toBe(collapsed.railX);
    expect(collapsed.triggerLeft).toBeGreaterThanOrEqual(collapsed.rootLeft);
    expect(collapsed.triggerRight).toBeLessThanOrEqual(collapsed.rootRight);
    await expect(page.locator("#layout")).not.toHaveAttribute("data-mobile-state", /.+/);
});

test("nested opposite-side panels keep trigger position and icon rotation scoped", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");
    await page.setContent(`
        <style>
            ${structural}
            [data-slot="side-panel"]::before { background: black; }
        </style>
        <div id="outer" data-slot="side-panel" data-side="left" data-state="collapsed" style="--side-panel-width: 240px; width: 800px">
            <aside data-slot="side-panel-panel">
                <div data-slot="side-panel-panel-content">Outer</div>
            </aside>
            <main data-slot="side-panel-inset">
                <div id="inner" data-slot="side-panel" data-side="right" data-state="expanded" style="--side-panel-width: 150px; width: 600px">
                    <aside id="inner-panel" data-slot="side-panel-panel">
                        <div data-slot="side-panel-panel-content">Inner</div>
                    </aside>
                    <main data-slot="side-panel-inset">
                        <button id="inner-trigger" data-slot="side-panel-trigger">
                            <span id="inner-icon" data-slot="side-panel-trigger-icon"></span>
                        </button>
                    </main>
                </div>
            </main>
        </div>
    `);

    const geometry = await page.locator("#inner").evaluate((root) => {
        const panel = root.querySelector("#inner-panel").getBoundingClientRect();
        const trigger = root.querySelector("#inner-trigger");
        const triggerBox = trigger.getBoundingClientRect();
        const style = getComputedStyle(trigger);

        return {
            left: style.left,
            leftVariable: getComputedStyle(root).getPropertyValue("--side-panel-trigger-left").trim(),
            panelLeft: panel.left,
            right: style.right,
            rightVariable: getComputedStyle(root).getPropertyValue("--side-panel-trigger-right").trim(),
            triggerCenter: triggerBox.left + triggerBox.width / 2,
        };
    });

    expect(geometry.leftVariable).toBe("auto");
    expect(geometry.rightVariable).toBe("150px");
    expect(geometry.right).toBe("150px");
    expect(geometry.triggerCenter).toBe(geometry.panelLeft);
    await expect(page.locator("#inner-icon")).toHaveCSS("transform", "matrix(-1, 0, 0, -1, 0, 0)");
});

for (const [direction, side] of [
    ["ltr", "left"],
    ["ltr", "right"],
    ["rtl", "left"],
    ["rtl", "right"],
]) {
    test(`${side} remains a physical side in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(await fixture(false, true, side, direction));

        const geometry = await page.locator("#layout").evaluate((root) => {
            const rootBox = root.getBoundingClientRect();
            const panelBox = root.querySelector("#project-panel").getBoundingClientRect();
            const triggerBox = root.querySelector("#project-trigger").getBoundingClientRect();

            return {
                panelLeft: panelBox.left,
                panelRight: panelBox.right,
                rootLeft: rootBox.left,
                rootRight: rootBox.right,
                triggerCenter: triggerBox.left + triggerBox.width / 2,
            };
        });

        if (side === "left") {
            expect(geometry.panelLeft).toBe(geometry.rootLeft);
            expect(geometry.triggerCenter).toBe(geometry.panelRight);
        } else {
            expect(geometry.panelRight).toBe(geometry.rootRight);
            expect(geometry.triggerCenter).toBe(geometry.panelLeft);
        }
    });
}

for (const [side, state, rotation] of [
    ["left", "expanded", "matrix(1, 0, 0, 1, 0, 0)"],
    ["left", "collapsed", "matrix(-1, 0, 0, -1, 0, 0)"],
    ["right", "expanded", "matrix(-1, 0, 0, -1, 0, 0)"],
    ["right", "collapsed", "matrix(1, 0, 0, 1, 0, 0)"],
]) {
    test(`${side} ${state} exposes the expected trigger rotation`, async ({ page }) => {
        const structural = await readFile("resources/css/structural.css", "utf8");
        await page.setContent(`
            <style>${structural}</style>
            <div data-slot="side-panel" data-side="${side}" data-state="${state}">
                <span id="rotation-icon" data-slot="side-panel-trigger-icon"></span>
            </div>
        `);

        await expect(page.locator("#rotation-icon")).toHaveCSS("transform", rotation);
    });
}

test("programmatic collapse returns focus from panel content to the trigger", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);
    await page.locator("#panel-link").focus();

    await page.locator("#layout").evaluate((root) => {
        window.app.getControllerForElementAndIdentifier(root, "side-panel").close();
    });

    await expect(page.locator("#project-trigger")).toBeFocused();
    await expect(page.locator("#project-panel")).toHaveAttribute("inert", "");
});

test.describe("reduced motion", () => {
    test.use({ reducedMotion: "reduce" });

    test("disables panel, trigger and icon transitions", async ({ page }) => {
        await page.emulateMedia({ reducedMotion: "reduce" });
        await page.setContent(await fixture());

        await expect(page.locator("#project-panel")).toHaveCSS("transition-duration", "0s");
        await expect(page.locator("#project-trigger")).toHaveCSS("transition-duration", "0s");
        await expect(page.locator("#project-panel-content")).toHaveCSS("transition-duration", "0s");
    });
});

async function fixture(withSidebar = false, open = true, side = "left", direction = "ltr") {
    const structural = await readFile("resources/css/structural.css", "utf8");

    return `
        <style>${structural}</style>
        ${withSidebar ? '<div id="app-sidebar" data-slot="sidebar-wrapper" data-state="expanded">' : ""}
        <button id="before-layout">Before</button>
        <div id="layout"
             dir="${direction}"
             data-slot="side-panel"
             data-controller="side-panel"
             data-side-panel-name-value="project-nav"
             data-state="${open ? "expanded" : "collapsed"}"
             data-side="${side}"
             data-side-panel-open-value="${open}"
             style="--side-panel-width: 240px; width: 800px; height: 200px">
            <aside id="project-panel" data-slot="side-panel-panel" data-side-panel-target="panel" ${open ? "" : "inert"}>
                <div id="project-panel-content" data-slot="side-panel-panel-content">
                    <a id="panel-link" href="/tasks">Tasks</a>
                </div>
            </aside>
            <main id="project-inset" data-slot="side-panel-inset">
                <button id="project-trigger"
                        data-slot="side-panel-trigger"
                        data-side-panel-target="trigger"
                        data-action="side-panel#toggle"
                        aria-controls="project-panel"
                        aria-expanded="true">Toggle</button>
            </main>
        </div>
        ${withSidebar ? "</div>" : ""}
    `;
}

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("side-panel", window.SidePanelController);
    });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/side_panel_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class SidePanelController extends Controller")
        .concat("\nwindow.SidePanelController = SidePanelController;\n");
}
