import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test.use({ reducedMotion: "no-preference" });

test("clamps before connect and responds to intrinsic content resizing", async ({ page }) => {
    await page.setContent(await fixture());

    const root = page.locator("#preview");
    const viewport = page.locator("#preview-viewport");
    const trigger = page.locator("#preview-trigger");

    await expect.poll(() => viewport.evaluate((element) => element.getBoundingClientRect().height)).toBe(200);
    await expect(trigger).toBeHidden();

    await installController(page);

    await expect(root).toHaveAttribute("data-state", "collapsed");
    await expect(root).toHaveAttribute("data-ready", "");
    await expect(trigger).toBeVisible();
    await expect(viewport).toHaveCSS("max-block-size", "200px");

    await trigger.click();

    await expect(root).toHaveAttribute("data-state", "expanded");
    await expect(trigger).toHaveAttribute("aria-expanded", "true");
    await page.waitForTimeout(120);
    const expandingHeight = await viewport.evaluate((element) => element.getBoundingClientRect().height);
    expect(expandingHeight).toBeGreaterThan(200);
    expect(expandingHeight).toBeLessThan(480);
    await expect(root).toHaveAttribute("data-transitioning", "");
    await expect.poll(() => viewport.evaluate((element) => element.getBoundingClientRect().height)).toBe(480);
    await expect(root).not.toHaveAttribute("data-transitioning", "");
    await expect(viewport).toHaveCSS("max-block-size", "none");
    await expect(viewport).toHaveCSS("overflow", "visible");

    await trigger.click();

    await expect(root).toHaveAttribute("data-state", "collapsed");
    await expect(root).not.toHaveAttribute("data-transitioning", "");
    await expect(viewport).toHaveCSS("max-block-size", "200px");
    await expect(viewport).toHaveCSS("overflow", "hidden");

    await trigger.click();
    await expect(root).not.toHaveAttribute("data-transitioning", "");

    await page.locator("#preview-content").evaluate((element) => {
        element.style.height = "120px";
    });

    await expect(root).toHaveAttribute("data-state", "static");
    await expect(trigger).toBeHidden();
    await expect.poll(() => viewport.evaluate((element) => element.getBoundingClientRect().height)).toBe(120);
});

test("expanded height includes the content outer box", async ({ page }) => {
    await page.setContent(`
        <style>${await readFile("resources/css/structural.css", "utf8")}</style>
        <section
            id="outer-box"
            data-slot="read-more"
            data-controller="read-more"
            data-state="collapsed"
            data-read-more-collapsed-height-value="200"
            style="--read-more-collapsed-height: 200px"
        >
            <div id="outer-box-viewport" data-slot="read-more-viewport" data-read-more-target="viewport">
                <div data-slot="read-more-content" data-read-more-target="content" style="height: 300px; border: 10px solid; margin-bottom: 30px">Content</div>
            </div>
            <button id="outer-box-trigger" data-read-more-target="trigger" data-action="read-more#toggle" hidden>Toggle</button>
        </section>
    `);

    const viewport = page.locator("#outer-box-viewport");
    const requiredHeight = await viewport.evaluate((element) => element.scrollHeight);

    await installController(page);

    await expect(page.locator("#outer-box")).toHaveCSS("--read-more-expanded-height", `${requiredHeight}px`);
    await page.locator("#outer-box-trigger").click();
    await expect(page.locator("#outer-box")).not.toHaveAttribute("data-transitioning", "");
    await expect(viewport).toHaveCSS("overflow", "visible");
});

test("expanded content does not clip positioned descendants", async ({ page }) => {
    await page.setContent(`
        <style>${await readFile("resources/css/structural.css", "utf8")}</style>
        <section
            id="overlay-host"
            data-slot="read-more"
            data-controller="read-more"
            data-state="collapsed"
            data-read-more-collapsed-height-value="200"
            style="--read-more-collapsed-height: 200px"
        >
            <div id="overlay-viewport" data-slot="read-more-viewport" data-read-more-target="viewport">
                <div data-slot="read-more-content" data-read-more-target="content" style="position: relative; height: 300px">
                    Content
                    <div id="positioned-menu" style="position: absolute; top: 300px; height: 70px">Menu</div>
                </div>
            </div>
            <button id="overlay-trigger" data-read-more-target="trigger" data-action="read-more#toggle" hidden>Toggle</button>
        </section>
    `);
    await installController(page);
    await page.locator("#overlay-trigger").click();
    await expect(page.locator("#overlay-host")).not.toHaveAttribute("data-transitioning", "");

    const boxes = await page.locator("#overlay-viewport").evaluate((viewport) => {
        const menu = document.querySelector("#positioned-menu");

        return {
            overflow: getComputedStyle(viewport).overflow,
            viewportBottom: viewport.getBoundingClientRect().bottom,
            menuBottom: menu.getBoundingClientRect().bottom,
        };
    });

    expect(boxes.overflow).toBe("visible");
    expect(boxes.menuBottom).toBeGreaterThan(boxes.viewportBottom);
});

test("ignores unrelated infinite animations when settling expansion", async ({ page }) => {
    await page.setContent(`
        <style>
            ${await readFile("resources/css/structural.css", "utf8")}
            @keyframes unrelated-pulse { from { opacity: .99 } to { opacity: 1 } }
            #animated-viewport { animation: unrelated-pulse 100ms infinite alternate; }
        </style>
        <section
            id="animated-host"
            data-slot="read-more"
            data-controller="read-more"
            data-state="collapsed"
            data-read-more-collapsed-height-value="200"
            style="--read-more-collapsed-height: 200px"
        >
            <div id="animated-viewport" data-slot="read-more-viewport" data-read-more-target="viewport">
                <div data-read-more-target="content" style="height: 600px">Content</div>
            </div>
            <button id="animated-trigger" data-read-more-target="trigger" data-action="read-more#toggle" hidden>Toggle</button>
        </section>
    `);
    await installController(page);
    await page.locator("#animated-trigger").click();

    await page.waitForTimeout(120);
    await expect(page.locator("#animated-host")).toHaveAttribute("data-transitioning", "");
    const transitionProperties = await page.locator("#animated-viewport").evaluate((element) =>
        element
            .getAnimations()
            .map((animation) => animation.transitionProperty)
            .filter(Boolean),
    );
    expect(transitionProperties.some((property) => ["max-block-size", "max-height"].includes(property))).toBe(true);

    await expect(page.locator("#animated-host")).not.toHaveAttribute("data-transitioning", "", { timeout: 1500 });
    await expect(page.locator("#animated-viewport")).toHaveCSS("max-block-size", "none");
    await expect(page.locator("#animated-viewport")).toHaveCSS("overflow", "visible");
});

test("interrupts expansion from the current rendered height", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);
    const viewport = page.locator("#preview-viewport");
    const trigger = page.locator("#preview-trigger");

    await trigger.click();
    await page.waitForTimeout(150);
    const heights = await page.evaluate(() => {
        const viewport = document.querySelector("#preview-viewport");
        const trigger = document.querySelector("#preview-trigger");
        const midExpand = viewport.getBoundingClientRect().height;
        trigger.click();

        return {
            midExpand,
            afterCollapse: viewport.getBoundingClientRect().height,
        };
    });

    expect(Math.abs(heights.afterCollapse - heights.midExpand)).toBeLessThan(5);
    await expect(page.locator("#preview")).not.toHaveAttribute("data-transitioning", "");
    await expect.poll(() => viewport.evaluate((element) => element.getBoundingClientRect().height)).toBe(200);
});

test("applies an external expanded value change during motion", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);

    await page.locator("#preview-trigger").click();
    await page.waitForTimeout(100);
    await page.locator("#preview").evaluate((element) => {
        element.setAttribute("data-read-more-expanded-value", "false");
    });

    await expect(page.locator("#preview")).toHaveAttribute("data-state", "collapsed");
    await expect(page.locator("#preview-trigger")).toHaveAttribute("aria-expanded", "false");
    await expect(page.locator("#preview")).not.toHaveAttribute("data-transitioning", "");
});

test("settles a stalled max-height transition through the safety timeout", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);
    await page.locator("#preview-trigger").click();
    await page.waitForTimeout(80);

    const property = await page.locator("#preview-viewport").evaluate((element) => {
        const transition = element
            .getAnimations()
            .find((animation) => ["max-block-size", "max-height"].includes(animation.transitionProperty));
        transition?.pause();

        return transition?.transitionProperty;
    });

    expect(["max-block-size", "max-height"]).toContain(property);
    await expect(page.locator("#preview")).not.toHaveAttribute("data-transitioning", "", { timeout: 1200 });
    await expect(page.locator("#preview-viewport")).toHaveCSS("overflow", "visible");
});

test("continues expansion when intrinsic content resizes during motion", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);
    const root = page.locator("#preview");
    const viewport = page.locator("#preview-viewport");

    await page.locator("#preview-trigger").click();
    await page.waitForTimeout(100);
    await page.locator("#preview-content").evaluate((element) => {
        element.style.height = "600px";
    });
    await page.waitForTimeout(120);

    const height = await viewport.evaluate((element) => element.getBoundingClientRect().height);
    expect(height).toBeGreaterThan(200);
    expect(height).toBeLessThan(600);
    await expect(root).toHaveAttribute("data-transitioning", "");
    await expect(root).not.toHaveAttribute("data-transitioning", "");
    await expect.poll(() => viewport.evaluate((element) => element.getBoundingClientRect().height)).toBe(600);
});

test("refreshes when the viewport target is removed and restored", async ({ page }) => {
    await page.setContent(await fixture());
    await installController(page);
    const root = page.locator("#preview");
    const trigger = page.locator("#preview-trigger");
    const viewport = page.locator("#preview-viewport");

    await viewport.evaluate((element) => element.removeAttribute("data-read-more-target"));

    await expect(root).toHaveAttribute("data-state", "static");
    await expect(root).not.toHaveAttribute("data-transitioning", "");
    await expect(trigger).toBeHidden();

    await viewport.evaluate((element) => element.setAttribute("data-read-more-target", "viewport"));

    await expect(root).toHaveAttribute("data-state", "collapsed");
    await expect(trigger).toBeVisible();
});

test("keeps instances scoped inside overlays and Turbo Frames", async ({ page }) => {
    const markup = (id) => `
        <section
            id="${id}"
            data-controller="read-more"
            data-state="collapsed"
            data-read-more-collapsed-height-value="100"
        >
            <div data-read-more-target="viewport">
                <div data-read-more-target="content" style="height: 240px">${id}</div>
            </div>
            <button id="${id}-trigger" data-read-more-target="trigger" data-action="read-more#toggle" hidden>Toggle</button>
        </section>
    `;

    await page.setContent(`
        ${markup("isolated")}
        <div data-slot="modal-panel">${markup("modal")}</div>
        <div data-slot="dropdown-menu">${markup("dropdown")}</div>
        <turbo-frame id="details">${markup("frame")}</turbo-frame>
    `);
    await installController(page);

    for (const id of ["isolated", "modal", "dropdown", "frame"]) {
        await expect(page.locator(`#${id}`)).toHaveAttribute("data-state", "collapsed");
        await expect(page.locator(`#${id}`)).toHaveCSS("--read-more-collapsed-height", "100px");
        await expect(page.locator(`#${id}-trigger`)).toBeVisible();
    }

    await page.locator("#modal-trigger").click();

    await expect(page.locator("#modal")).toHaveAttribute("data-state", "expanded");
    await expect(page.locator("#isolated")).toHaveAttribute("data-state", "collapsed");
    await expect(page.locator("#dropdown")).toHaveAttribute("data-state", "collapsed");
    await expect(page.locator("#frame")).toHaveAttribute("data-state", "collapsed");
});

test.describe("without scripting", () => {
    test.use({ javaScriptEnabled: false });

    test("leaves all content visible", async ({ page }) => {
        await page.setContent(await fixture());

        const viewport = page.locator("#preview-viewport");
        const trigger = page.locator("#preview-trigger");

        expect((await viewport.boundingBox())?.height).toBe(480);
        await expect(trigger).toBeHidden();
    });
});

async function fixture() {
    const structural = await readFile("resources/css/structural.css", "utf8");

    return `
        <style>${structural}</style>
        <section
            id="preview"
            data-slot="read-more"
            data-controller="read-more"
            data-state="collapsed"
            data-read-more-collapsed-height-value="200"
            data-read-more-expanded-value="false"
            style="--read-more-collapsed-height: 200px"
        >
            <div id="preview-viewport" data-slot="read-more-viewport" data-read-more-target="viewport">
                <div id="preview-content" data-slot="read-more-content" data-read-more-target="content" style="height: 480px">Content</div>
                <div data-slot="read-more-fade" data-read-more-target="fade" hidden></div>
            </div>
            <button
                id="preview-trigger"
                data-slot="read-more-trigger"
                data-read-more-target="trigger"
                data-action="read-more#toggle"
                aria-expanded="false"
                hidden
            >
                <span data-read-more-target="moreLabel">More</span>
                <span data-read-more-target="lessLabel" hidden>Less</span>
            </button>
        </section>
    `;
}

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });

    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("read-more", window.ReadMoreController);
    });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/read_more_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class ReadMoreController extends Controller")
        .concat("\nwindow.ReadMoreController = ReadMoreController;\n");
}
