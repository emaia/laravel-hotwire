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
const probe = (id, token) => `<span id="${id}" style="color: var(${token})"></span>`;

async function mount(page) {
    await page.setContent(`
        <style>${novaCss}</style>
        ${button("light")}
        ${probe("light-background", "--background")}
        ${probe("light-input", "--input")}
        <div data-theme="dark">
            ${button("dark")}
            ${probe("dark-border", "--border")}
            ${probe("dark-input", "--input")}
            <div data-theme="light">${button("light-inside-dark")}</div>
        </div>
        <div data-theme="light">
            <div data-theme="dark">${button("dark-inside-light")}</div>
        </div>
        <button id="dark-self" data-theme="dark" data-slot="button" data-variant="outline">b</button>
    `);
}

const style = (page, selector, property) =>
    page.locator(selector).evaluate((element, name) => getComputedStyle(element)[name], property);

// The base rule paints `border-border`/`bg-background` and only the `dark:` variant reaches for
// `border-input`/`bg-input/30`, so resolving those tokens is what separates an applied variant from
// a theme that merely swapped tokens underneath the same declarations.
test("applies dark variant declarations, not just dark tokens", async ({ page }) => {
    await mount(page);

    const input = await style(page, "#dark-input", "color");

    expect(input).not.toBe(await style(page, "#dark-border", "color"));
    expect(await style(page, "#dark", "borderColor")).toBe(input);
});

// Light `--border` and `--input` share a value, so the light side is pinned through the background:
// `bg-background` and `bg-input/30` differ there even though the border tokens do not.
test("leaves the light surface free of dark variant declarations", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#light", "backgroundColor")).toBe(await style(page, "#light-background", "color"));
});

test("applies the dark variant on the element carrying the theme", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#dark-self", "borderColor")).toBe(await style(page, "#dark-input", "color"));
});

test("applies the dark variant inside a dark island nested in a light scope", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#dark-inside-light", "borderColor")).toBe(await style(page, "#dark-input", "color"));
});

// Known limitation, documented under Theming: the variant matches any descendant of a dark ancestor,
// so a light island below a dark scope keeps light tokens but still takes dark-tuned surfaces. This
// pins the behaviour the docs promise; expressing nearest-theme needs top-level `@scope`, which
// `@apply` cannot emit.
test("still reaches a light island nested in a dark scope", async ({ page }) => {
    await mount(page);

    expect(await style(page, "#light-inside-dark", "backgroundColor"))
        .not.toBe(await style(page, "#light", "backgroundColor"));
});
