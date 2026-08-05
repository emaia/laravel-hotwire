import { expect, test } from "@playwright/test";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";

test("color scheme transitions respect the reduced motion preference", async ({ page }) => {
    const server = createServer((_request, response) => {
        response.writeHead(200, {
            "cache-control": "no-store",
            "content-type": "text/html; charset=utf-8",
        });
        response.end(`
            <!doctype html>
            <html>
                <body>
                    <button
                        id="toggle"
                        data-controller="color-scheme"
                        data-action="color-scheme#toggle"
                        data-color-scheme-view-transition-value="true"
                    >Toggle scheme</button>
                </body>
            </html>
        `);
    });
    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    const origin = `http://127.0.0.1:${server.address().port}`;

    try {
        await page.emulateMedia({ reducedMotion: "reduce" });
        await page.addInitScript(() => {
            window.viewTransitionCount = 0;
            const native = document.startViewTransition?.bind(document);

            document.startViewTransition = (callback) => {
                window.viewTransitionCount++;

                window.lastViewTransition = native
                    ? native(callback)
                    : fallbackTransition(callback);

                return window.lastViewTransition;
            };

            function fallbackTransition(callback) {
                callback();

                return {
                    finished: Promise.resolve(),
                    ready: Promise.resolve(),
                    updateCallbackDone: Promise.resolve(),
                };
            }
        });
        await page.goto(origin);
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("color-scheme", window.ColorSchemeController);
        });

        const toggle = page.locator("#toggle");
        await expect.poll(async () => toggle.evaluate((element) =>
            Boolean(window.StimulusApplication.getControllerForElementAndIdentifier(element, "color-scheme")),
        )).toBe(true);

        await toggle.click();

        await expect(page.locator("html")).toHaveAttribute("data-theme", "dark");
        expect(await page.evaluate(() => window.viewTransitionCount)).toBe(0);

        await page.emulateMedia({ reducedMotion: "no-preference" });
        await toggle.click();

        await expect(page.locator("html")).toHaveAttribute("data-theme", "light");
        expect(await page.evaluate(() => window.viewTransitionCount)).toBe(1);

        await page.evaluate(() => window.lastViewTransition.finished);
        await page.evaluate(async () => {
            window.viewTransitionCount = 0;
            const toggle = document.querySelector("#toggle");
            const controller = window.StimulusApplication.getControllerForElementAndIdentifier(toggle, "color-scheme");

            controller.dark();
            controller.light();

            await window.lastViewTransition.updateCallbackDone;
        });

        await expect(page.locator("html")).toHaveAttribute("data-theme", "light");
        await expect(page.locator("html")).toHaveAttribute("data-color-scheme-mode", "light");
        expect(await page.evaluate(() => window.localStorage.getItem("hotwire.colorScheme"))).toBe("light");
        expect(await page.evaluate(() => window.viewTransitionCount)).toBe(1);
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/color_scheme_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class ColorSchemeController extends Controller")
        .concat("\nwindow.ColorSchemeController = ColorSchemeController;\n");
}
