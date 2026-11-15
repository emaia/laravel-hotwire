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
                '\n@source inline("flex-1 col-span-2 w-40 md:hidden");',
        );
    }
});

async function mount(page, preset, enhance = true) {
    await page.setContent(`<style>${styles[preset]}</style>
        <style>
            .fixture-flex { display: flex; width: 140px; }
            .fixture-wide-flex { display: flex; width: 500px; }
            .fixture-horizontal { width: 500px; }
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
    test(`${preset}: capture-phase overlays defer Escape only to a live editable search`, async ({ page }) => {
        await page.setViewportSize({ width: 600, height: 900 });
        await mount(page, preset);
        for (const [root, identifier, id] of [
            ["search-drawer", "drawer", "drawer-search"],
            ["search-alert", "alert-dialog", "alert-search"],
            ["search-sidebar", "sidebar", "sidebar-search"],
        ]) {
            const open = async () => {
                if (identifier === "alert-dialog") await page.locator("#search-alert-trigger").click();
                else
                    await page.evaluate(
                        ({ root, identifier }) => {
                            const controller = window.app.getControllerForElementAndIdentifier(
                                document.getElementById(root),
                                identifier,
                            );
                            return identifier === "sidebar" ? controller.openMobile() : controller.open();
                        },
                        { root, identifier },
                    );
            };
            const input = page.locator(`#${id}`);
            await open();
            await input.fill("Trees");
            await page.keyboard.press("Escape");
            await expect(input).toHaveValue("");
            await expect(input).toBeVisible();
            await page.keyboard.press("Escape");
            await expect(input).toBeHidden();

            for (const state of ["readonly", "disabled", "unmanaged"]) {
                await open();
                await input.fill("Trees");
                await input.evaluate((input, state) => {
                    if (state === "unmanaged") input.parentElement.removeAttribute("data-controller");
                    else input.setAttribute(state, "");
                }, state);
                if (state === "unmanaged") await expect(input).not.toHaveClass(/clear-input--touched/);
                if (state !== "disabled") await input.focus();
                // Dispatch on disabled controls too: capture handlers must not mistake them for a pending clear.
                await input.dispatchEvent("keydown", { key: "Escape", bubbles: true, cancelable: true });
                await expect(input).toHaveValue("Trees");
                await expect(input).toBeHidden();
                await input.evaluate((input, state) => {
                    input.removeAttribute(state);
                    if (state === "unmanaged") input.parentElement.setAttribute("data-controller", "clear-input");
                }, state);
            }
        }
    });

    test(`${preset}: default inputs fill flex rows and horizontal Fields without layout classes`, async ({ page }) => {
        await mount(page, preset, false);
        expect((await page.locator("#default-flex-input").boundingBox()).width).toBeCloseTo(400, 0);
        expect((await page.locator("#horizontal-input").boundingBox()).width).toBeGreaterThan(300);
        expect((await page.locator("#fixed-width-input").boundingBox()).width).toBeCloseTo(160, 0);
    });

    test(`${preset}: native clearing remains available before enhancement and after disconnect`, async ({ page }) => {
        await mount(page, preset, false);
        const input = page.locator("#query");
        const clear = page.locator('#filters [data-slot="clear-input-button"]');
        await input.focus();
        await expect(clear).toBeHidden();
        await clickNativeCancel(page, input);
        await expect(input).toHaveValue("");
        await input.fill("Trees");
        await page.addScriptTag({ content: bundle });
        await expect(clear).toBeVisible();
        await input.locator("..").evaluate((node) => node.removeAttribute("data-controller"));
        await expect(input).not.toHaveClass(/clear-input--touched/);
        await expect(clear).toBeHidden();
        await clickNativeCancel(page, input);
        await expect(input).toHaveValue("");
    });

    test(`${preset}: stable roots participate in flex and grid on first paint`, async ({ page }) => {
        await mount(page, preset, false);
        const metrics = await page.evaluate(() => {
            const rect = (selector) => document.querySelector(selector).getBoundingClientRect().toJSON();
            return {
                flex: rect("#flex-layout"),
                input: rect("#flex-input"),
                sibling: rect("#flex-layout .fixture-sibling"),
                grid: rect("#grid-input"),
                root: rect('#grid-layout > [data-slot="input-wrapper"]'),
            };
        });
        expect(metrics.input.width).toBeCloseTo(40, 0);
        expect(metrics.input.right).toBeLessThanOrEqual(metrics.sibling.x);
        expect(metrics.sibling.right).toBeLessThanOrEqual(metrics.flex.right);
        expect(metrics.grid.width).toBeCloseTo(200, 0);
        expect(metrics.grid.width).toBe(metrics.root.width);
        const plain = await page.locator("#plain-input").evaluate((input) => ({
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

    test(`${preset}: Escape clears immediately before an enclosing overlay can close`, async ({ page }) => {
        await mount(page, preset);
        const input = page.locator("#query");
        await input.fill("Oak");
        await page.keyboard.press("Escape");
        await expect(input).toHaveValue("");
        expect(await page.evaluate(() => window.submissions)).toEqual([""]);
        await page.waitForTimeout(220);
        expect(await page.evaluate(() => window.submissions)).toEqual([""]);

        await page.evaluate(() =>
            window.app.getControllerForElementAndIdentifier(document.querySelector("#search-modal"), "modal").open(),
        );
        await page.locator("#modal-search").focus();
        await page.keyboard.press("Escape");
        await expect(page.locator("#modal-search")).toHaveValue("");
        await expect(page.locator("#modal-search")).toBeVisible();
        await page.keyboard.press("Escape");
        await expect(page.locator("#modal-search")).toBeHidden();

        await page.getByRole("button", { name: "Search menu" }).click();
        await page.locator("#dropdown-search").focus();
        await page.keyboard.press("Escape");
        await expect(page.locator("#dropdown-search")).toHaveValue("");
        await expect(page.locator("#dropdown-search")).toBeVisible();
        await page.keyboard.press("Escape");
        await expect(page.locator("#dropdown-search")).toBeHidden();
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
        await search.fill("Pine");
        await search.evaluate((node) => {
            window.searchClearEvents = 0;
            node.addEventListener("inputCleared", () => window.searchClearEvents++);
        });
        await page.keyboard.press("Escape");
        await expect(page.locator('[data-slot="multi-select-content"]')).toBeHidden();
        await expect(page.locator("#species")).toBeFocused();
        expect(await page.evaluate(() => window.searchClearEvents)).toBe(0);
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

async function clickNativeCancel(page, input) {
    const box = await input.boundingBox();
    const padding = await input.evaluate((node) => parseFloat(getComputedStyle(node).paddingRight));
    await page.mouse.click(box.x + box.width - padding - 8, box.y + box.height / 2);
}

test("partial clear-input markup tolerates missing targets without errors", async ({ page }) => {
    await mount(page, "nova");
    await page.evaluate(() => {
        window.controllerErrors = [];
        window.addEventListener("error", (event) => window.controllerErrors.push(event.message));
        const input = document.querySelector("#missing-button input");
        input.focus();
        input.blur();
        document.querySelector("#missing-input button").click();
    });
    expect(await page.evaluate(() => window.controllerErrors)).toEqual([]);
});

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
