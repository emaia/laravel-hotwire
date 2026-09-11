import { expect, test } from "@playwright/test";
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const run = promisify(execFile);
const compiler = `
    import { readFile } from "node:fs/promises";
    import { compileCssFixture } from "./scripts/css_build_contract.js";

    const source = await readFile("stubs/resources/css/app.css", "utf8");
    process.stdout.write(await compileCssFixture(source, { minify: false }));
`;

let novaCss;

test.beforeAll(async () => {
    ({ stdout: novaCss } = await run("bun", ["-e", compiler], {
        cwd: process.cwd(),
        maxBuffer: 1024 * 1024,
    }));
});

const button = (id) => `<button id="${id}" data-slot="button" data-variant="outline">b</button>`;
const activeTab = (id, attributes = "") => `
    <div data-slot="tabs-list" data-variant="default">
        <button id="${id}" ${attributes} data-slot="tabs-trigger" data-state="active">t</button>
    </div>
`;
const probe = (id, token) => `<span id="${id}" style="color: var(${token})"></span>`;

async function mount(page) {
    await page.setContent(`
        <style>${novaCss}</style>
        ${button("light")}
        ${activeTab("light-tab")}
        ${probe("light-background", "--background")}
        ${probe("light-input", "--input")}
        <div data-theme="dark">
            ${button("dark")}
            ${activeTab("dark-tab")}
            ${probe("dark-border", "--border")}
            ${probe("dark-input", "--input")}
            ${probe("dark-popover", "--popover")}
            ${probe("dark-popover-foreground", "--popover-foreground")}
            <select id="dark-select" data-slot="select">
                <optgroup id="dark-optgroup" label="Plans">
                    <option id="dark-option">Pro</option>
                </optgroup>
            </select>
            <div data-theme="light">
                ${button("light-inside-dark")}
                ${activeTab("light-tab-inside-dark")}
                <div data-theme="dark">
                    ${button("dark-inside-light-inside-dark")}
                    ${activeTab("dark-tab-inside-light-inside-dark")}
                </div>
            </div>
        </div>
        <div data-theme="light">
            <div data-theme="dark">${button("dark-inside-light")}</div>
        </div>
        <button id="dark-self" data-theme="dark" data-slot="button" data-variant="outline">b</button>
        ${activeTab("dark-tab-self", 'data-theme="dark"')}
        <div data-theme="dark">${activeTab("light-tab-self-inside-dark", 'data-theme="light"')}</div>
    `);
}

const style = (page, selector, property) =>
    page.locator(selector).evaluate((element, name) => getComputedStyle(element)[name], property);

// The base rule paints `border-border`/`bg-background` and only the scoped dark adjustment reaches for
// `border-input`/`bg-input/30`, so resolving those tokens is what separates an applied adjustment from
// a theme that merely swapped tokens underneath the same declarations.
test("applies dark adjustments, not just dark tokens", async ({ page }) => {
    await mount(page);

    const input = await style(page, "#dark-input", "color");

    expect(input).not.toBe(await style(page, "#dark-border", "color"));
    expect(await style(page, "#dark", "borderColor")).toBe(input);
});

// Light `--border` and `--input` share a value, so the light side is pinned through the background:
// `bg-background` and `bg-input/30` differ there even though the border tokens do not.
test("leaves the light surface free of dark adjustments", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#light", "backgroundColor")).toBe(await style(page, "#light-background", "color"));
});

test("applies dark adjustments on the element carrying the theme", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#dark-self", "borderColor")).toBe(await style(page, "#dark-input", "color"));
});

test("applies dark adjustments inside a dark island nested in a light scope", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#dark-inside-light", "borderColor")).toBe(await style(page, "#dark-input", "color"));
});

test("stops dark adjustments at a nested light theme", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#light-inside-dark", "backgroundColor")).toBe(
        await style(page, "#light", "backgroundColor"),
    );
});

test("reapplies dark adjustments below a light boundary nested in dark", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#dark-inside-light-inside-dark", "borderColor")).toBe(
        await style(page, "#dark-input", "color"),
    );
});

test("scopes contextual dark adjustments when the themed subject is a later compound", async ({ page }) => {
    await mount(page);

    const darkInput = await style(page, "#dark-input", "color");
    const lightBorder = await style(page, "#light-tab", "borderColor");

    expect(await style(page, "#dark-tab", "borderColor")).toBe(darkInput);
    expect(await style(page, "#light-tab-inside-dark", "borderColor")).toBe(lightBorder);
    expect(await style(page, "#dark-tab-inside-light-inside-dark", "borderColor")).toBe(darkInput);
    expect(await style(page, "#dark-tab-self", "borderColor")).toBe(darkInput);
    expect(await style(page, "#light-tab-self-inside-dark", "borderColor")).toBe(lightBorder);
});

test("keeps native Select options readable in the dark theme", async ({ page }) => {
    await mount(page);

    const background = await style(page, "#dark-popover", "color");
    const foreground = await style(page, "#dark-popover-foreground", "color");

    expect(await style(page, "#dark-option", "backgroundColor")).toBe(background);
    expect(await style(page, "#dark-option", "color")).toBe(foreground);
    expect(await style(page, "#dark-optgroup", "backgroundColor")).toBe(background);
    expect(await style(page, "#dark-optgroup", "color")).toBe(foreground);
});
