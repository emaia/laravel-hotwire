import { expect, test } from "@playwright/test";

// Guards the fixes that made the package pin an unreleased Turbo build. When
// these pass on an official release, the package can return to npm.

const ORIGIN = "http://turbo-build.test";

const tasks = (page) =>
    Array.from({ length: 6 }, (_, index) => `<article>Task ${page}.${index + 1}</article>`).join("");

test("replaces Turbo Frame contents in a single mutation", async ({ page }) => {
    await page.route(`${ORIGIN}/**`, (route) => {
        const { pathname } = new URL(route.request().url());
        const body = pathname === "/next"
            ? `<!doctype html><html><body>
                <turbo-frame id="results"><strong id="marker">Page 2</strong>${tasks(2)}</turbo-frame>
               </body></html>`
            : `<!doctype html><html><body>
                <select name="due"><option>Any</option><option>Today</option></select>
                <turbo-frame id="results">
                    <strong id="marker">Page 1</strong>${tasks(1)}
                    <nav><a id="next" href="/next">Next</a></nav>
                </turbo-frame>
               </body></html>`;

        return route.fulfill({ status: 200, contentType: "text/html", body });
    });

    await page.goto(`${ORIGIN}/`);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.evaluate(() => {
        window.frameMutations = [];
        new MutationObserver((records) => {
            for (const record of records) {
                window.frameMutations.push({
                    removed: record.removedNodes.length,
                    added: record.addedNodes.length,
                });
            }
        }).observe(document.getElementById("results"), { childList: true });
    });

    await page.locator("#next").click();
    await expect(page.locator("#marker")).toHaveText("Page 2");

    const mutations = await page.evaluate(() => window.frameMutations);

    expect(mutations).toHaveLength(1);
    expect(mutations[0].removed).toBeGreaterThan(0);
    expect(mutations[0].added).toBeGreaterThan(0);
});
