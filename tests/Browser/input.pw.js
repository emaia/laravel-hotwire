import { expect, test } from "@playwright/test";
import { execFileSync } from "node:child_process";
import { compileCssFixture, packageSourceEntrypoint } from "../../scripts/css_build_contract.js";

const styles = {};
let html;
let bundle;

test.beforeAll(async () => {
    html = execFileSync(process.env.PHP_BINARY ?? "php", ["tests/Browser/fixtures/render_input.php"]).toString();
    bundle = execFileSync("bun", [
        "build",
        "tests/Browser/fixtures/input.js",
        "--target=browser",
        "--format=iife",
    ]).toString();
    for (const preset of ["nova", "bloom"]) {
        styles[preset] = await compileCssFixture(
            packageSourceEntrypoint(`../../vendor/emaia/laravel-hotwire/resources/css/presets/${preset}.css`) +
                '\n@source inline("flex-1 col-span-2");',
        );
    }
});

async function mount(page, preset, enhance = true) {
    await page.setContent(`<style>${styles[preset]}</style>
        <style>
            .fixture-flex { display: flex; width: 140px; }
            .fixture-sibling { flex: 0 0 100px; }
            .fixture-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); width: 300px; }
        </style>${html}`);
    if (enhance) {
        await page.addScriptTag({ content: bundle });
        await expect
            .poll(() =>
                page.evaluate(
                    () =>
                        !!window.app.getControllerForElementAndIdentifier(
                            document.querySelector("#query").parentElement,
                            "clear-input",
                        ),
                ),
            )
            .toBe(true);
    }
}

for (const preset of ["nova", "bloom"]) {
    test(`${preset}: stable roots participate in flex and grid on first paint`, async ({ page }) => {
        await mount(page, preset, false);
        const metrics = await page.evaluate(() => {
            const rect = (selector) => document.querySelector(selector).getBoundingClientRect().toJSON();
            return {
                flex: rect("#flex-layout"),
                input: rect("#flex-input"),
                sibling: rect(".fixture-sibling"),
                grid: rect("#grid-input"),
                root: rect('#grid-layout > [data-slot="input-wrapper"]'),
            };
        });
        expect(metrics.input.width).toBeCloseTo(40, 0);
        expect(metrics.input.right).toBeLessThanOrEqual(metrics.sibling.x);
        expect(metrics.sibling.right).toBeLessThanOrEqual(metrics.flex.right);
        expect(metrics.grid.width).toBeCloseTo(200, 0);
        expect(metrics.grid.width).toBe(metrics.root.width);
        const plain = await page
            .locator("#plain-input")
            .evaluate((input) => ({
                input: input.getBoundingClientRect().height,
                root: input.parentElement.getBoundingClientRect().height,
            }));
        expect(plain.root).toBe(plain.input);
        await expect(page.locator('[data-slot="input-wrapper"]:has(> #plain-input)')).toHaveCSS("display", "block");
        await expect(page.locator("#query")).toBeVisible();
        await expect(page.locator('#filters [data-slot="clear-input-button"]')).toBeHidden();
    });

    test(`${preset}: search clearing is tabbable, retains focus and cancels pending debounce`, async ({ page }) => {
        await mount(page, preset);
        const input = page.getByRole("searchbox", { name: "Search parks" });
        const clear = page.locator('#filters [data-slot="clear-input-button"]');
        await page.getByText("Search parks", { exact: true }).click();
        await expect(input).toBeFocused();
        await input.fill("Oak");
        await page.keyboard.press("Tab");
        await expect(clear).toBeFocused();
        const focus = await clear.evaluate((node) => {
            const style = getComputedStyle(node);
            return (style.outlineStyle !== "none" && parseFloat(style.outlineWidth) > 0) || style.boxShadow !== "none";
        });
        expect(focus).toBe(true);
        await page.keyboard.press("Enter");
        await expect(input).toHaveValue("");
        await expect(input).toBeFocused();
        await expect(clear).toBeHidden();
        await expect.poll(() => page.evaluate(() => window.submissions)).toEqual([""]);
        // A second submission would be the stale typing debounce firing after the clear.
        await page.waitForTimeout(220);
        expect(await page.evaluate(() => window.submissions)).toEqual([""]);
    });

    test(`${preset}: disabled, readonly and disabled fieldsets cannot expose or activate clearing`, async ({
        page,
    }) => {
        await mount(page, preset);
        for (const id of ["disabled-search", "readonly-search", "fieldset-search"]) {
            const input = page.locator(`#${id}`);
            await input.hover();
            const clear = page.locator(`[data-slot="input-wrapper"]:has(> #${id}) > button`);
            await expect(clear).toBeHidden();
            await clear.evaluate((node) => node.click());
            await expect(input).toHaveValue("Trees");
            await input.evaluate((node) => {
                node.disabled = false;
                node.readOnly = false;
                if (node.closest("fieldset")) node.closest("fieldset").disabled = false;
            });
            await input.focus();
            await expect(clear).toBeVisible();
            await expect(clear).toBeEnabled();
            await clear.click();
            await expect(input).toHaveValue("");
        }
        const input = page.locator("#query");
        await input.focus();
        await input.evaluate((node) => (node.readOnly = true));
        await expect(page.locator('#filters [data-slot="clear-input-button"]')).toBeHidden();
        await input.evaluate((node) => (node.readOnly = false));
        await expect(page.locator('#filters [data-slot="clear-input-button"]')).toBeVisible();
    });

    test(`${preset}: clear controls stay inside grouped input bounds in both directions`, async ({ page }) => {
        await mount(page, preset);
        for (const direction of ["ltr", "rtl"]) {
            await page.locator("#group").evaluate((node, direction) => (node.dir = direction), direction);
            await page.locator("#group-search").focus();
            const bounds = await page.locator("#group").evaluate((group) => {
                const input = group.querySelector("input").getBoundingClientRect();
                const clear = group.querySelector('[data-slot="clear-input-button"]').getBoundingClientRect();
                const addons = [...group.querySelectorAll('[data-slot="input-group-addon"]')].map((node) =>
                    node.getBoundingClientRect(),
                );
                return {
                    contained:
                        clear.left >= input.left &&
                        clear.right <= input.right &&
                        clear.top >= input.top &&
                        clear.bottom <= input.bottom,
                    overlapsAddon: addons.some((addon) => clear.left < addon.right && clear.right > addon.left),
                };
            });
            expect(bounds).toEqual({ contained: true, overlapsAddon: false });
        }
    });

    test(`${preset}: native opt-out and Modal, Dropdown and Frame composition keep working`, async ({ page }) => {
        await mount(page, preset);
        const native = page.locator("#native-search");
        await native.focus();
        await expect(page.locator('[data-slot="input-wrapper"]:has(> #native-search) > button')).toHaveCount(0);
        // Exercise Chromium's native cancel affordance, which must remain available after opting out.
        const box = await native.boundingBox();
        const padding = await native.evaluate((node) => parseFloat(getComputedStyle(node).paddingRight));
        await page.mouse.click(box.x + box.width - padding - 8, box.y + box.height / 2);
        await expect(native).toHaveValue("");

        await page.evaluate(() =>
            window.app.getControllerForElementAndIdentifier(document.querySelector("#search-modal"), "modal").open(),
        );
        await clearWithin(page, "modal-search");
        await page.keyboard.press("Escape");
        await expect(page.locator("#modal-search")).toBeHidden();

        await page.getByRole("button", { name: "Search menu" }).click();
        await clearWithin(page, "dropdown-search");
        await expect(page.locator("#dropdown-search")).toBeVisible();
        await page.keyboard.press("Escape");
        await clearWithin(page, "frame-search");
    });

    test(`${preset}: Multi Select uses the same search and clear composition`, async ({ page }) => {
        await mount(page, preset);
        await page.locator("#species").click();
        const search = page.locator('[data-multi-select-target="search"]');
        await expect(search).toHaveAttribute("type", "search");
        await search.fill("Oak");
        await expect(page.getByRole("option", { name: "Pine", exact: true })).toBeHidden();
        await page.locator('[data-slot="multi-select-content"] [data-slot="clear-input-button"]').click();
        await expect(search).toBeFocused();
        await expect(search).toHaveValue("");
        await expect(page.getByRole("option", { name: "Pine", exact: true })).toBeVisible();
    });
}

async function clearWithin(page, id) {
    const input = page.locator(`#${id}`);
    await input.focus();
    await page.keyboard.press("Tab");
    const clear = page.locator(`[data-slot="input-wrapper"]:has(> #${id}) > button`);
    await expect(clear).toBeFocused();
    await page.keyboard.press("Enter");
    await expect(input).toHaveValue("");
    await expect(input).toBeFocused();
}

test("Turbo morph and controller reconnect retain clearing without duplicate events", async ({ page }) => {
    await mount(page, "nova");
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.locator("#search-frame").evaluate((frame) => {
        const next = frame.cloneNode(true);
        next.querySelector("input").className = "";
        next.querySelector("input").setAttribute("value", "Oak");
        next.querySelector("button").classList.add("hidden");
        window.Turbo.morphElements(frame, next);
        document.dispatchEvent(new Event("turbo:render"));
        window.clearEvents = 0;
        frame.addEventListener("inputCleared", () => window.clearEvents++);
    });
    await expect(page.locator("#frame-search")).toHaveValue("Oak");
    const root = page.locator('[data-slot="input-wrapper"]:has(> #frame-search)');
    await root.evaluate((node) => node.removeAttribute("data-controller"));
    await expect
        .poll(() => root.evaluate((node) => window.app.getControllerForElementAndIdentifier(node, "clear-input")))
        .toBeNull();
    await root.evaluate((node) => node.setAttribute("data-controller", "clear-input"));
    await expect
        .poll(() => root.evaluate((node) => !!window.app.getControllerForElementAndIdentifier(node, "clear-input")))
        .toBe(true);
    await clearWithin(page, "frame-search");
    expect(await page.evaluate(() => window.clearEvents)).toBe(1);
    await page.locator("#frame-search").evaluate((input) => input.replaceWith(input.cloneNode(true)));
    await page.locator("#frame-search").fill("Pine");
    await clearWithin(page, "frame-search");
    expect(await page.evaluate(() => window.clearEvents)).toBe(2);
});
