import { expect, test } from "@playwright/test";
import { execFileSync } from "node:child_process";
import { compileCssFixture, packageSourceEntrypoint } from "../../scripts/css_build_contract.js";

const url = "http://navigation-menu.test";
let css;
let bundle;

test.beforeAll(async () => {
    css = await compileCssFixture(
        packageSourceEntrypoint("../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css"),
    );
    bundle = execFileSync("bun", [
        "build", "tests/Browser/fixtures/navigation_menu.js", "--target=browser", "--format=iife",
    ]).toString();
});

test("tabs through active links in context and returns focus on Escape", async ({ page }) => {
    await setup(page);
    const products = page.getByRole("button", { name: "Products" });
    const catalog = page.getByRole("link", { name: "Catalog" });
    const featured = page.getByRole("link", { name: "Featured" });
    const company = page.getByRole("button", { name: "Company" });

    await page.keyboard.press("Tab");
    await expect(products).toBeFocused();
    await expect(catalog).toBeVisible();
    await page.keyboard.press("Tab");
    await expect(catalog).toBeFocused();
    await page.keyboard.press("Shift+Tab");
    await expect(products).toBeFocused();
    await page.keyboard.press("Tab");
    await page.keyboard.press("Tab");
    await expect(featured).toBeFocused();
    await page.keyboard.press("Escape");
    await expect(products).toBeFocused();
    await expect(catalog).toBeHidden();
    await page.keyboard.press("ArrowRight");
    await expect(company).toBeFocused();
    await page.keyboard.press("ArrowLeft");
    await expect(catalog).toBeVisible();
    await page.keyboard.press("Tab");
    await page.keyboard.press("Tab");
    await page.keyboard.press("Tab");
    await expect(company).toBeFocused();
    await page.keyboard.press("Escape");
    await page.keyboard.press("Tab");
    await expect(page.getByRole("link", { name: "About" })).toBeFocused();
    await page.keyboard.press("Tab");
    await expect(page.getByRole("button", { name: "After navigation" })).toBeFocused();
});

test("positions a vertical RTL menu at inline end and moves between entries with up and down", async ({ page }) => {
    await setup(page);
    await page.evaluate(() => {
        document.documentElement.dir = "rtl";
        document.querySelector("#site").dataset.orientation = "vertical";
    });
    const products = page.getByRole("button", { name: "Products" });
    const company = page.getByRole("button", { name: "Company" });
    const viewport = page.locator('[data-slot="navigation-menu-viewport"]');
    await page.keyboard.press("Tab");
    await expect(products).toBeFocused();
    await expect(viewport).toBeVisible();
    await expect(viewport).toHaveAttribute("data-side", "left");
    const panel = await viewport.boundingBox();
    expect(panel.x + panel.width).toBeLessThan((await products.boundingBox()).x);
    await page.keyboard.press("ArrowDown");
    await expect(company).toBeFocused();
    await page.keyboard.press("ArrowUp");
    await expect(products).toBeFocused();
});

test("anchors a wide viewport to the menu while its content slides and height changes", async ({ page }) => {
    await setup(page, "menu", route => route.fulfill({
        contentType: "text/html", body: `<turbo-frame id="company-frame">
            <div style="height: 210px"><a href="/team">Meet the team</a></div>
        </turbo-frame>`,
    }));
    await page.addStyleTag({ content: `
        #site { width: 740px; margin-inline: auto; }
        #site [data-slot="navigation-menu-viewport"] { --navigation-menu-max-width: 600px; }
    ` });
    const root = page.locator("#site");
    const products = root.getByRole("button", { name: "Products" });
    const company = root.getByRole("button", { name: "Company" });
    const viewport = root.locator('[data-slot="navigation-menu-viewport"]');

    async function expectFixedWidthAndPosition() {
        await expect.poll(async () => {
            const box = await viewport.boundingBox();
            const anchor = await root.boundingBox();
            return [Math.round(box.x - anchor.x), Math.round(box.width)];
        }).toEqual([70, 600]);
    }

    await products.hover();
    await expect(viewport).toBeVisible();
    await expectFixedWidthAndPosition();
    await expect(viewport).toHaveCSS("transition-property", "height");
    const initialHeight = (await viewport.boundingBox()).height;

    await company.hover();
    const panel = root.locator("#site-content-company");
    await expect(panel).toHaveAttribute("data-motion", "from-end");
    await expectFixedWidthAndPosition();
    await expect(root.getByRole("link", { name: "Meet the team" })).toBeVisible();
    await expect.poll(async () => (await viewport.boundingBox()).height).toBeGreaterThan(initialHeight);

    await products.hover();
    await expect(root.locator("#site-content-products")).toHaveAttribute("data-motion", "from-start");
    await expectFixedWidthAndPosition();

    await page.setViewportSize({ width: 390, height: 650 });
    await expect.poll(async () => {
        const box = await viewport.boundingBox();
        return box !== null && box.x >= 0 && box.x + box.width <= 390;
    }).toBe(true);
});

test("fades in the positioned viewport without replaying the fade on submenu switches", async ({ page }) => {
    await setup(page, "menu");
    await page.addStyleTag({ content: `
        [data-slot="navigation-menu-viewport"][data-positioned] { animation-duration: 600ms; }
    ` });
    const root = page.locator("#site");
    const viewport = root.locator('[data-slot="navigation-menu-viewport"]');

    await root.getByRole("button", { name: "Products" }).hover();
    await expect(viewport).toBeVisible();
    const opening = await viewport.evaluate(async node => {
        const fade = node.getAnimations().find(animation => animation.animationName === "hotwire-navigation-menu-open");
        if (!fade) return null;
        const opacity = Number(getComputedStyle(node).opacity);
        await fade.finished;
        return { opacity, restingOpacity: Number(getComputedStyle(node).opacity) };
    });
    expect(opening).not.toBeNull();
    expect(opening.opacity).toBeLessThan(1);
    expect(opening.restingOpacity).toBe(1);

    await root.getByRole("button", { name: "Company" }).hover();
    await expect(viewport.locator('[data-motion="from-end"]')).toBeVisible();
    expect(await viewport.evaluate(node =>
        node.getAnimations().some(animation => animation.animationName === "hotwire-navigation-menu-open"),
    )).toBe(false);
    await expect(viewport).toHaveCSS("opacity", "1");
});

test("keeps the bottom edge pinned to the menu when panels slide above their triggers", async ({ page }) => {
    await page.setViewportSize({ width: 900, height: 620 });
    await setup(page, "menu", route => route.fulfill({
        contentType: "text/html", body: `<turbo-frame id="company-frame">
            <div style="height: 240px"><a href="/team">Meet the team</a></div>
        </turbo-frame>`,
    }));
    await page.addStyleTag({ content: `
        #site { position: fixed; left: 80px; bottom: 12px; width: 740px; }
        #site-content-products { min-height: 110px; }
        #site-content-company { height: 264px; }
        [data-slot="navigation-menu-viewport"][data-positioned] { transition-duration: 650ms; }
    ` });
    const root = page.locator("#site");
    const products = root.getByRole("button", { name: "Products" });
    const company = root.getByRole("button", { name: "Company" });
    const viewport = root.locator('[data-slot="navigation-menu-viewport"]');
    await products.hover();
    await expect(viewport).toBeVisible();
    await expect(viewport).toHaveAttribute("data-side", "top");

    for (const [trigger, direction] of [[company, "from-end"], [products, "from-start"]]) {
        await trigger.hover();
        await expect(viewport.locator(`[data-motion="${direction}"]`)).toBeVisible();
        await expect.poll(() => viewport.evaluate(node =>
            node.getAnimations().some(animation => animation.transitionProperty === "height"),
        )).toBe(true);
        const duringMotion = await viewport.evaluate(async (node) => {
            const anchor = node.parentElement;
            const panel = node.querySelector('[data-state="open"]');
            const samples = [];
            while (node.getAnimations().some(animation => animation.transitionProperty === "height")) {
                await new Promise(requestAnimationFrame);
                const box = node.getBoundingClientRect();
                samples.push({
                    side: node.dataset.side,
                    gap: anchor.getBoundingClientRect().top - box.bottom,
                    height: box.height,
                    panelTop: panel.getBoundingClientRect().top,
                });
            }
            return samples;
        });
        expect(new Set(duringMotion.map(sample => sample.height)).size).toBeGreaterThan(1);
        expect(duringMotion.every(sample => sample.side === "top" && Math.abs(sample.gap - 4) <= 1)).toBe(true);
        const panelTops = duringMotion.map(sample => sample.panelTop);
        expect(Math.max(...panelTops) - Math.min(...panelTops)).toBeLessThanOrEqual(1);
    }
});

test("clips a wider incoming panel during the shared viewport transition without exposing a scroll container", async ({ page }) => {
    await setup(page);
    await page.addStyleTag({ content: `
        [data-slot="navigation-menu-viewport"][data-positioned] { transition-duration: 10s; }
        #site-content-company { width: 420px; }
    ` });
    const products = page.getByRole("button", { name: "Products" });
    const company = page.getByRole("button", { name: "Company" });
    const viewport = page.locator('[data-slot="navigation-menu-viewport"]');
    await products.hover();
    await expect(viewport).toBeVisible();
    await company.hover();
    await expect.poll(() => viewport.evaluate(node =>
        node.getAnimations().some(animation => animation.transitionProperty === "width"),
    )).toBe(true);
    const duringTransition = await viewport.evaluate(node => {
        const panel = node.querySelector("#site-content-company");
        node.scrollLeft = 30;
        return { clipsPanel: node.clientWidth < panel.offsetWidth, scrollLeft: node.scrollLeft };
    });
    expect(duringTransition).toEqual({ clipsPanel: true, scrollLeft: 0 });
});

test("shares one positioned viewport and remeasures after a real Turbo Frame response", async ({ page }) => {
    await page.setViewportSize({ width: 640, height: 480 });
    let requests = 0;
    let release;
    const responseReady = new Promise((resolve) => { release = resolve; });
    await setup(page, "frame", async (route) => {
        requests++;
        expect(route.request().headers()["turbo-frame"]).toBe("company-frame");
        await responseReady;
        await route.fulfill({ contentType: "text/html", body: `
            <turbo-frame id="company-frame"><div style="width: 440px; max-width: 100%; height: 510px">
                <a href="/team">Meet the team</a>
                <a href="/contact" style="display: block; margin-top: 450px">Contact</a>
            </div></turbo-frame>
        ` });
    });

    const products = page.getByRole("button", { name: "Products" });
    const company = page.getByRole("button", { name: "Company" });
    const viewport = page.locator('[data-slot="navigation-menu-viewport"]');
    expect(requests).toBe(0);
    await products.hover();
    await expect(viewport).toBeVisible();
    const x = (await viewport.boundingBox()).x;
    await page.getByRole("link", { name: "Catalog" }).hover();
    await expect(products).toHaveAttribute("aria-expanded", "true");

    await company.hover();
    await expect(viewport).toBeVisible();
    await expect(page.locator("#site-content-company")).toHaveAttribute("data-motion", "from-end");
    await expect.poll(async () => (await viewport.boundingBox()).x).toBeGreaterThan(x);
    await expect.poll(() => requests).toBe(1);
    const placeholderWidth = await viewport.evaluate(node => parseFloat(node.style.getPropertyValue("--navigation-menu-viewport-width")));
    release();
    const team = page.getByRole("link", { name: "Meet the team" });
    await expect(team).toBeVisible();
    await expect.poll(() => viewport.evaluate(node => parseFloat(node.style.getPropertyValue("--navigation-menu-viewport-width"))))
        .toBeGreaterThan(placeholderWidth);
    const panel = page.locator("#site-content-company");
    await expect.poll(() => panel.evaluate(node => node.scrollHeight > node.clientHeight)).toBe(true);
    await page.getByRole("link", { name: "Contact" }).scrollIntoViewIfNeeded();
    await expect.poll(() => panel.evaluate(node => node.scrollTop)).toBeGreaterThan(0);
    await expect.poll(async () => {
        const box = await viewport.boundingBox();
        return box.x >= 0 && box.x + box.width <= 640 && box.y >= 0 && box.y + box.height <= 480;
    }).toBe(true);
    await page.mouse.move(630, 470);
    await expect(viewport).toBeHidden();
    await company.hover();
    await expect(team).toBeVisible();
    expect(requests).toBe(1);

    await viewport.evaluate(element => element.querySelector("#site-content-company").remove());
    await expect(viewport).toBeHidden();
    await expect(company).toHaveAttribute("aria-expanded", "false");
});

for (const surface of ["modal", "dropdown"]) {
    test(`Escape closes the submenu first inside a ${surface}`, async ({ page }) => {
        await setup(page, surface);
        await page.getByRole("button", { name: `Open ${surface}` }).click();
        const products = page.getByRole("button", { name: "Products" });
        await expect(products).toBeVisible();
        // The parent may focus its first control on open. Activate the trigger only if still collapsed.
        if (await products.getAttribute("aria-expanded") === "false") await products.click();
        const catalog = page.getByRole("link", { name: "Catalog" });
        await expect(catalog).toBeVisible();
        await catalog.focus();
        await page.keyboard.press("Escape");
        await expect(catalog).toBeHidden();
        await expect(products).toBeFocused();
        await page.keyboard.press("Escape");
        await expect(products).toBeHidden();
    });
}

test("Escape dismisses a hovered submenu without taking focus from its parent Modal", async ({ page }) => {
    await setup(page, "modal");
    await page.locator("#site").evaluate(node => { node.dataset.navigationMenuHoverValue = "true"; });
    await page.getByRole("button", { name: "Open modal" }).click();
    const dialog = page.getByRole("dialog");
    await expect(dialog).toBeFocused();
    await dialog.getByRole("button", { name: "Products" }).hover();
    const catalog = dialog.getByRole("link", { name: "Catalog" });
    await expect(catalog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(catalog).toBeHidden();
    await expect(dialog).toBeFocused();
    await page.keyboard.press("Escape");
    await expect(dialog).toBeHidden();
});

test.describe(() => {
    test.use({ hasTouch: true, reducedMotion: "reduce" });

    test("toggles with a single tap and respects reduced motion", async ({ page }) => {
        await setup(page);
        const products = page.getByRole("button", { name: "Products" });
        const viewport = page.locator('[data-slot="navigation-menu-viewport"]');
        await products.tap();
        await expect(viewport).toBeVisible();
        await expect(viewport).toHaveCSS("transition-property", "none");
        await expect(viewport).toHaveCSS("animation-name", "none");
        await products.tap();
        await expect(viewport).toBeHidden();
    });
});

async function setup(page, surface = "", frameResponse = (route) => route.fulfill({
    contentType: "text/html", body: '<turbo-frame id="company-frame"><a href="/team">Meet the team</a></turbo-frame>',
})) {
    const html = execFileSync(process.env.PHP_BINARY ?? "php", ["tests/Browser/fixtures/render_navigation_menu.php", surface]).toString();
    await page.route(`${url}/company-menu`, frameResponse);
    await page.route(`${url}/`, (route) => route.fulfill({
        contentType: "text/html",
        body: `<!doctype html><meta charset="utf-8"><style>${css}</style><main>${html}</main>`,
    }));
    await page.goto(url);
    await page.addScriptTag({ content: bundle });
}
