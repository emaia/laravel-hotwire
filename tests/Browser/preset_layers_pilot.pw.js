import { expect, test } from "@playwright/test";
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const run = promisify(execFile);
const compiler = `
    import { readFile } from "node:fs/promises";
    import { compileCssFixture } from "./scripts/css_build_contract.js";

    const source = await readFile("tests/Fixtures/css/preset_layers_pilot.css", "utf8");
    process.stdout.write(await compileCssFixture(source));
`;

let css;

test.beforeAll(async () => {
    ({ stdout: css } = await run("bun", ["-e", compiler], {
        cwd: process.cwd(),
        maxBuffer: 1024 * 1024,
    }));
});

async function mount(page, body, applicationCss = "") {
    await page.setContent(`<style>${css}</style>${body}<style>${applicationCss}</style>`);
}

test("utilities and application CSS keep their override priority", async ({ page }) => {
    await mount(
        page,
        `
            <button id="layered" class="app-layered" data-slot="button" data-variant="default">Layered</button>
            <button id="utility" class="rounded-none" data-slot="button" data-variant="default">Utility</button>
            <button id="unlayered" class="app-unlayered" data-slot="button" data-variant="default">Unlayered</button>
        `,
        `
            @layer components { .app-layered { background-color: rgb(1, 2, 3); } }
            .app-unlayered { background-color: rgb(4, 5, 6); }
        `,
    );

    await expect(page.locator("#layered")).toHaveCSS("background-color", "rgb(1, 2, 3)");
    await expect(page.locator("#utility")).toHaveCSS("border-radius", "0px");
    await expect(page.locator("#unlayered")).toHaveCSS("background-color", "rgb(4, 5, 6)");
});

test("preset scoping does not leak and permits a different rule structure", async ({ page }) => {
    await mount(
        page,
        `
            <button id="default" data-slot="button" data-variant="default">Default</button>
            <div data-preset="compact">
                <button id="compact" data-slot="button" data-variant="default">Compact</button>
            </div>
            <button id="sibling" data-slot="button" data-variant="default">Sibling</button>
            <div data-preset="poster">
                <button id="poster" data-slot="button" data-variant="default">Poster</button>
            </div>
        `,
        `
            :root {
                --primary: rgb(10, 20, 30);
                --primary-foreground: rgb(240, 241, 242);
                --secondary: rgb(40, 50, 60);
                --secondary-foreground: rgb(230, 231, 232);
            }
        `,
    );

    await expect(page.locator("#default")).toHaveCSS("background-color", "rgb(10, 20, 30)");
    await expect(page.locator("#compact")).toHaveCSS("background-color", "rgb(40, 50, 60)");
    await expect(page.locator("#sibling")).toHaveCSS("background-color", "rgb(10, 20, 30)");
    await expect(page.locator("#poster")).toHaveCSS("background-color", "rgb(80, 20, 120)");
    await expect(page.locator("#poster")).toHaveCSS("border-radius", "0px");
});

test("shared control states remain observable", async ({ page }) => {
    await mount(
        page,
        `
            <input id="input" data-slot="input">
            <select id="select" data-slot="select"><option>Select</option></select>
            <textarea id="textarea" data-slot="textarea" aria-invalid="true"></textarea>
            <input id="disabled" data-slot="input" disabled>
        `,
        `
            :root {
                --background: rgb(250, 250, 250);
                --foreground: rgb(20, 20, 20);
                --input: rgb(100, 100, 100);
                --ring: rgb(0, 100, 200);
                --destructive: rgb(200, 0, 0);
            }
        `,
    );

    await expect(page.locator("#input")).toHaveCSS("background-color", "rgb(250, 250, 250)");
    await expect(page.locator("#select")).toHaveCSS("border-color", "rgb(100, 100, 100)");
    await expect(page.locator("#textarea")).toHaveCSS("border-color", "rgb(200, 0, 0)");
    await expect(page.locator("#disabled")).toHaveCSS("opacity", "0.5");

    await page.locator("#input").focus();
    await expect(page.locator("#input")).toHaveCSS("border-color", "rgb(0, 100, 200)");
});
