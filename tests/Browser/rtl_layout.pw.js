import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture } from "../../scripts/css_build_contract.js";

let presetCss;

test.describe.configure({ mode: "serial" });

test.beforeAll(async () => {
    presetCss = await compileCssFixture(await readFile("stubs/resources/css/app.css", "utf8"));
});

for (const direction of ["ltr", "rtl"]) {
    test(`connected controls follow inline order in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(`
            <style>${presetCss}</style>
            <div dir="${direction}" data-slot="button-group" data-orientation="horizontal">
                <button id="first" data-slot="button" data-size="default" data-variant="outline">First</button>
                <button id="last" data-slot="button" data-size="default" data-variant="outline">Last</button>
            </div>
        `);

        const corners = await page.locator("#first, #last").evaluateAll((buttons) => buttons.map((button) => {
            const style = getComputedStyle(button);

            return {
                borderLeftWidth: style.borderLeftWidth,
                borderRightWidth: style.borderRightWidth,
                topLeftRadius: style.borderTopLeftRadius,
                topRightRadius: style.borderTopRightRadius,
            };
        }));

        if (direction === "ltr") {
            expect(corners[0].topLeftRadius).not.toBe("0px");
            expect(corners[0].topRightRadius).toBe("0px");
            expect(corners[1].borderLeftWidth).toBe("0px");
            expect(corners[1].topLeftRadius).toBe("0px");
            expect(corners[1].topRightRadius).not.toBe("0px");
        } else {
            expect(corners[0].topLeftRadius).toBe("0px");
            expect(corners[0].topRightRadius).not.toBe("0px");
            expect(corners[1].borderRightWidth).toBe("0px");
            expect(corners[1].topLeftRadius).not.toBe("0px");
            expect(corners[1].topRightRadius).toBe("0px");
        }
    });

    test(`input addons and switch thumbs follow inline direction in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(`
            <style>${presetCss}</style>
            <div dir="${direction}">
                <label data-slot="input-group">
                    <span id="addon" data-slot="input-group-addon" data-align="inline-start">https://</span>
                    <input id="control" data-slot="input" value="example.com">
                </label>
                <input id="switch" type="checkbox" checked data-slot="switch" data-size="default">
            </div>
        `);

        const layout = await page.locator('[data-slot="input-group"]').evaluate((group) => {
            const addon = group.querySelector("#addon");
            const control = group.querySelector("#control");
            const addonBox = addon.getBoundingClientRect();
            const controlBox = control.getBoundingClientRect();
            const controlStyle = getComputedStyle(control);

            return {
                addonLeft: addonBox.left,
                addonRight: addonBox.right,
                controlLeft: controlBox.left,
                controlRight: controlBox.right,
                paddingLeft: controlStyle.paddingLeft,
                paddingRight: controlStyle.paddingRight,
            };
        });
        const thumbTranslation = await page.locator("#switch").evaluate((element) => {
            return new DOMMatrix(getComputedStyle(element, "::before").transform).m41;
        });

        if (direction === "ltr") {
            expect(layout.addonRight).toBeLessThanOrEqual(layout.controlLeft);
            expect(layout.paddingLeft).toBe("4px");
            expect(thumbTranslation).toBeGreaterThan(0);
        } else {
            expect(layout.addonLeft).toBeGreaterThanOrEqual(layout.controlRight);
            expect(layout.paddingRight).toBe("4px");
            expect(thumbTranslation).toBeLessThan(0);
        }
    });

    test(`right sheet remains physically right in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(`
            <style>${presetCss}</style>
            <div dir="${direction}" data-slot="sheet-overlay" data-state="open">
                <section data-slot="sheet-content" data-side="right" style="--sheet-width: 200px"></section>
            </div>
        `);

        const box = await page.locator('[data-slot="sheet-content"]').boundingBox();

        const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);

        expect(box.x + box.width).toBeCloseTo(clientWidth, 5);
        expect(box.width).toBeCloseTo(200, 5);
    });

    test(`sidebar actions and submenus follow inline direction in ${direction.toUpperCase()}`, async ({ page }) => {
        await page.setContent(`
            <style>${presetCss}</style>
            <aside dir="${direction}" data-slot="sidebar" style="display: block; width: 240px">
                <ul data-slot="sidebar-menu">
                    <li id="menu-item" data-slot="sidebar-menu-item">
                        <button data-slot="sidebar-menu-button">Dashboard</button>
                        <button id="menu-action" data-slot="sidebar-menu-action" data-sidebar="menu-action">More</button>
                    </li>
                    <li>
                        <ul id="submenu" data-slot="sidebar-menu-sub">
                            <li data-slot="sidebar-menu-sub-item">
                                <a data-slot="sidebar-menu-sub-button">Reports</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </aside>
        `);

        const layout = await page.locator("#menu-item").evaluate((item) => {
            const itemBox = item.getBoundingClientRect();
            const actionBox = item.querySelector("#menu-action").getBoundingClientRect();
            const submenuStyle = getComputedStyle(document.querySelector("#submenu"));

            return {
                actionEnd: itemBox.right - actionBox.right,
                actionStart: actionBox.left - itemBox.left,
                submenuBorderLeft: submenuStyle.borderLeftWidth,
                submenuBorderRight: submenuStyle.borderRightWidth,
                submenuTranslation: submenuStyle.translate,
            };
        });

        if (direction === "ltr") {
            expect(layout.actionEnd).toBe(4);
            expect(layout.submenuBorderLeft).toBe("1px");
            expect(layout.submenuBorderRight).toBe("0px");
            expect(layout.submenuTranslation).toBe("1px");
        } else {
            expect(layout.actionStart).toBe(4);
            expect(layout.submenuBorderLeft).toBe("0px");
            expect(layout.submenuBorderRight).toBe("1px");
            expect(layout.submenuTranslation).toBe("-1px");
        }
    });
}
