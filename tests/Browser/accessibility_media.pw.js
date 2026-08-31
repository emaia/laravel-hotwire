import { expect, test } from "@playwright/test";
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const run = promisify(execFile);
const compiler = `
    import { readFile } from "node:fs/promises";
    import { compileCssFixture } from "./scripts/css_build_contract.js";

    process.stdout.write(await compileCssFixture(await readFile("stubs/resources/css/app.css", "utf8")));
`;

let novaCss;

test.beforeAll(async () => {
    ({ stdout: novaCss } = await run("bun", ["-e", compiler], {
        cwd: process.cwd(),
        maxBuffer: 1024 * 1024,
    }));
});

test("restores native checkable states and preserves custom state marks in forced colors", async ({ page }) => {
    await page.emulateMedia({ forcedColors: "active" });
    await page.setContent(fixture());
    await page.locator("#indeterminate").evaluate((element) => {
        element.indeterminate = true;
    });

    await expect(page.locator("#checkbox-on")).toHaveCSS("appearance", "auto");
    await expect(page.locator("#radio-on")).toHaveCSS("appearance", "auto");
    await expect(page.locator("#switch-on")).toHaveCSS("appearance", "auto");
    await expect(page.locator("#slider")).toHaveCSS("appearance", "none");
    await expect(page.locator("#checkbox-disabled")).toBeDisabled();

    await expectStatesDiffer(page, "#checkbox-off", "#checkbox-on");
    await expectStatesDiffer(page, "#checkbox-off", "#indeterminate");
    await expectStatesDiffer(page, "#radio-off", "#radio-on");
    await expectStatesDiffer(page, "#switch-off", "#switch-on");
    await expectStatesDiffer(page, "#checkbox-on", "#checkbox-disabled");
    await expectStatesDiffer(page, "#radio-on", "#radio-disabled");
    await expectStatesDiffer(page, "#switch-on", "#switch-disabled");

    await page.locator("#checkbox-on").focus();
    await expect(page.locator("#checkbox-on")).toHaveCSS("outline-style", "solid");
    await expect(page.locator("#checkbox-on")).toHaveCSS("outline-width", "2px");

    expect(await stateGlyph(page, "#selected-indicator")).toContain("2713");
    expect(await stateGlyph(page, "#mixed-indicator")).toContain("2212");
    await expect(page.locator("#progress-track")).toHaveCSS("border-style", "solid");
});

test("keeps persistent control states observable when backgrounds are not printed", async ({ page }) => {
    await page.emulateMedia({ media: "print" });
    await page.setContent(
        fixture(`
            @media print {
                *, *::before, *::after { background: transparent !important; }
            }
        `),
    );
    await page.locator("#indeterminate").evaluate((element) => {
        element.indeterminate = true;
    });

    for (const selector of ["#checkbox-on", "#radio-on", "#switch-on", "#slider"]) {
        await expect(page.locator(selector)).toHaveCSS("appearance", "auto");
    }

    await expect(page.locator("#checkbox-disabled")).toBeDisabled();
    await expectStatesDiffer(page, "#checkbox-off", "#checkbox-on");
    await expectStatesDiffer(page, "#checkbox-off", "#indeterminate");
    await expectStatesDiffer(page, "#radio-off", "#radio-on");
    await expectStatesDiffer(page, "#switch-off", "#switch-on");
    await expectStatesDiffer(page, "#checkbox-on", "#checkbox-disabled");
    await expectStatesDiffer(page, "#radio-on", "#radio-disabled");
    await expectStatesDiffer(page, "#switch-on", "#switch-disabled");
    await expectStatesDiffer(page, "#unselected-option", "#selected-option");
    await expectStatesDiffer(page, "#unselected-option", "#mixed-option");
    await expectStatesDiffer(page, "#progress-empty", "#progress-partial");

    expect(await stateGlyph(page, "#selected-indicator")).toContain("2713");
    expect(await stateGlyph(page, "#mixed-indicator")).toContain("2212");
    await expect(page.locator("#progress-track")).toHaveCSS("border-style", "solid");
    await expect(page.locator("#progress-indicator")).toHaveCSS("outline-style", "solid");
});

test("allows presets to refine the shared baseline without important declarations", async ({ page }) => {
    await page.emulateMedia({ forcedColors: "active" });
    await page.setContent(
        fixture(`
            @layer hotwire-accessibility {
                @media (forced-colors: active) {
                    [data-slot="switch"] { appearance: none; width: 2.5rem; }
                }
            }
        `),
    );

    await expect(page.locator("#switch-on")).toHaveCSS("appearance", "none");
    await expect(page.locator("#switch-on")).toHaveCSS("width", "40px");
});

async function stateGlyph(page, selector) {
    return page.locator(selector).evaluate((element) => {
        const style = getComputedStyle(element, "::before");
        const codepoint = style.content.length > 2 ? style.content.codePointAt(1).toString(16) : "";

        return `${codepoint} ${style.maskImage}`;
    });
}

async function expectStatesDiffer(page, first, second) {
    const [firstImage, secondImage] = await Promise.all([
        page.locator(first).screenshot(),
        page.locator(second).screenshot(),
    ]);

    expect(Buffer.compare(firstImage, secondImage)).not.toBe(0);
}

function fixture(extraCss = "") {
    return `
        <style>${novaCss}${extraCss}</style>
        <input id="checkbox-off" data-slot="checkbox" data-checkable="true" type="checkbox">
        <input id="checkbox-on" data-slot="checkbox" data-checkable="true" type="checkbox" checked>
        <input id="indeterminate" data-slot="checkbox" data-checkable="true" type="checkbox">
        <input id="checkbox-disabled" data-slot="checkbox" data-checkable="true" type="checkbox" checked disabled>
        <input id="radio-off" data-slot="radio-group-input" data-checkable="true" type="radio">
        <input id="radio-on" data-slot="radio-group-input" data-checkable="true" type="radio" checked>
        <input id="radio-disabled" data-slot="radio-group-input" data-checkable="true" type="radio" checked disabled>
        <input id="switch-off" data-slot="switch" data-checkable="true" data-size="default" type="checkbox" role="switch">
        <input id="switch-on" data-slot="switch" data-checkable="true" data-size="default" type="checkbox" role="switch" checked>
        <input id="switch-disabled" data-slot="switch" data-checkable="true" data-size="default" type="checkbox" role="switch" checked disabled>
        <input id="slider" data-slot="slider" data-orientation="horizontal" type="range" value="40">

        <button id="unselected-option" data-slot="multi-select-option" data-selected="false">
            <span data-slot="multi-select-indicator"></span>
        </button>
        <button id="selected-option" data-slot="multi-select-option" data-selected="true">
            <span id="selected-indicator" data-slot="multi-select-indicator"></span>
        </button>
        <button id="mixed-option" data-slot="multi-select-select-all" data-selected="false" data-indeterminate="true">
            <span id="mixed-indicator" data-slot="multi-select-indicator"></span>
        </button>

        <div id="progress-empty" data-slot="progress" role="progressbar" aria-valuenow="0" style="--progress-value: 0%; width: 10rem">
            <div data-slot="progress-track">
                <div data-slot="progress-indicator"></div>
            </div>
        </div>
        <div id="progress-partial" data-slot="progress" role="progressbar" aria-valuenow="40" style="--progress-value: 40%; width: 10rem">
            <div id="progress-track" data-slot="progress-track">
                <div id="progress-indicator" data-slot="progress-indicator"></div>
            </div>
        </div>
    `;
}
