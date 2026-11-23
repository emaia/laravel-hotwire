import { expect, test } from "@playwright/test";
import { execFileSync } from "node:child_process";
import { compileCssFixture, packageSourceEntrypoint } from "../../scripts/css_build_contract.js";

const styles = {};
let html;
let bundle;

test.beforeAll(async () => {
    html = execFileSync(process.env.PHP_BINARY ?? "php", ["tests/Browser/fixtures/render_breadcrumb.php"]).toString();
    bundle = execFileSync("bun", [
        "build",
        "tests/Browser/fixtures/breadcrumb.js",
        "--target=browser",
        "--format=iife",
    ]).toString();
    for (const preset of ["nova", "bloom"]) {
        styles[preset] = await compileCssFixture(
            packageSourceEntrypoint(`../../vendor/emaia/laravel-hotwire/resources/css/presets/${preset}.css`),
        );
    }
});

for (const preset of ["nova", "bloom"]) {
    test(`${preset}: keeps ancestor links available before enhancement and supports keyboard navigation after it`, async ({ page }) => {
        await page.setViewportSize({ width: 320, height: 600 });
        await page.setContent(`<style>${styles[preset]}</style><style>header { display: flex }</style>${html}`);
        const trail = page.locator("#md");
        const expanded = trail.locator('[data-breadcrumb-layout="expanded"]');
        const compact = trail.locator('[data-breadcrumb-layout="compact"]');
        const trigger = trail.getByRole("button", { name: "More pages" });

        await expect(expanded.getByRole("link", { name: "Projects" })).toBeVisible();
        await expect(compact).toBeHidden();
        await page.addScriptTag({ content: bundle });
        await expect(expanded).toBeHidden();
        await expect(trail.locator('[aria-current="page"]:visible')).toHaveCount(1);
        const current = compact.locator('[aria-current="page"]');
        await expect.poll(() => current.evaluate(node => node.scrollWidth > node.clientWidth)).toBe(true);
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBe(320);

        await trigger.press("Enter");
        const menu = trail.locator('[data-slot="dropdown-menu"]');
        // During positioning the menu is visible but inert.
        await expect(menu).toHaveAttribute("data-state", "open");
        await page.keyboard.press("Tab");
        await expect(menu.getByRole("link", { name: "Dashboard" })).toBeFocused();
        await expect(menu.getByRole("link", { name: "Projects" })).toHaveAttribute("data-turbo-frame", "results");
        await page.keyboard.press("Escape");
        await expect(menu).toBeHidden();
        await expect(trigger).toBeFocused();

        await trigger.click();
        await expect(menu).toBeVisible();
        await page.setViewportSize({ width: 768, height: 600 });
        await expect(menu).toBeHidden();
        await expect(expanded.getByRole("link", { name: "Projects" })).toBeVisible();
        // CSS hides the compact trail before the dropdown handles the media-query change.
        await expect(compact.locator('button[aria-label="More pages"]')).toHaveAttribute("aria-expanded", "false");
        await page.setViewportSize({ width: 320, height: 600 });
        await expect(trigger).toHaveAttribute("aria-expanded", "false");
        await expect(menu).toBeHidden();

        await page.evaluate(() => window.app.unload("dropdown"));
        await expect(expanded.getByRole("link", { name: "Projects" })).toBeVisible();
        await expect(compact).toBeHidden();
    });
}

test("switches at the selected breakpoint", async ({ page }) => {
    await page.setContent(`<style>${styles.nova}</style>${html}`);
    await page.addScriptTag({ content: bundle });
    for (const [breakpoint, width] of [["sm", 640], ["md", 768], ["lg", 1024]]) {
        const compact = page.locator(`#${breakpoint} [data-breadcrumb-layout="compact"]`);
        await page.setViewportSize({ width: width - 1, height: 600 });
        await expect(compact).toBeVisible();
        await page.setViewportSize({ width, height: 600 });
        await expect(compact).toBeHidden();
    }
});
