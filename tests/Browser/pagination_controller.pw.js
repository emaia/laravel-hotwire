import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("incremental pagination appends inside a Turbo Frame instead of navigating to the next page", async ({ page }) => {
    await page.route("**/tasks*", async (route) => {
        const url = new URL(route.request().url());
        const pageNumber = url.searchParams.get("page") || "1";

        await route.fulfill({
            contentType: "text/html",
            body: documentHtml(pageNumber),
        });
    });

    await page.goto("https://pagination.test/tasks");
    await installController(page);

    await page.locator("#page-2-link").click();

    await expect(page.locator("#task-1")).toBeVisible();
    await expect(page.locator("#task-2")).toBeVisible();
    await expect(page.locator("#page-2-link")).toHaveCount(0);
    await expect(page.locator("#page-3-link")).toBeVisible();
    await expect(page.locator("#tasks")).toHaveCount(1);
    await expect(page.locator("#page-marker")).toHaveText("Page 1");
});

test("incremental pagination swaps visible button content while loading", async ({ page }) => {
    let resolveResponse;

    await page.route("**/tasks*", async (route) => {
        const url = new URL(route.request().url());
        const pageNumber = url.searchParams.get("page") || "1";

        if (pageNumber === "2") {
            await new Promise((resolve) => { resolveResponse = resolve; });
        }

        await route.fulfill({
            contentType: "text/html",
            body: documentHtml(pageNumber),
        });
    });

    await page.goto("https://pagination.test/tasks");
    await installController(page);
    await installStructuralCss(page);

    await expect(page.locator("[data-slot='pagination-next-content']")).toBeVisible();
    await expect(page.locator("[data-slot='pagination-next-loading-content']")).toBeHidden();

    await page.locator("#page-2-link").click();

    await expect(page.locator("[data-slot='pagination-next-content']")).toBeHidden();
    await expect(page.locator("[data-slot='pagination-next-loading-content']")).toBeVisible();

    resolveResponse();
    await expect(page.locator("#page-3-link")).toBeVisible();
});

test("infinite pagination stops auto-loading after a failed visible-link request", async ({ page }) => {
    let requests = 0;
    let failNextPage = true;

    await page.route("**/tasks*", async (route) => {
        const url = new URL(route.request().url());
        const pageNumber = url.searchParams.get("page") || "1";

        if (pageNumber === "1") {
            await route.fulfill({ contentType: "text/html", body: documentHtml(pageNumber, { infinite: true }) });

            return;
        }

        requests += 1;

        if (!failNextPage) {
            await route.fulfill({ contentType: "text/html", body: documentHtml(pageNumber, { infinite: pageNumber === "2" }) });

            return;
        }

        await route.fulfill({ status: 503, contentType: "text/html", body: "Service unavailable" });
    });

    await page.goto("https://pagination.test/tasks");
    await page.evaluate(() => {
        window.IntersectionObserver = class ImmediateIntersectionObserver {
            constructor(callback) { this.callback = callback; }
            observe(element) { queueMicrotask(() => this.callback([{ isIntersecting: true, target: element }])); }
            disconnect() {}
        };
    });
    await installController(page);

    await expect.poll(() => requests).toBe(1);
    await page.waitForTimeout(100);
    expect(requests).toBe(1);
    await expect(page.locator("[data-slot='pagination']")).toHaveAttribute("data-state", "error");

    failNextPage = false;
    await page.locator("#page-2-link").click();

    await expect.poll(() => requests).toBe(3);
    await expect(page.locator("#task-1")).toBeVisible();
    await expect(page.locator("#task-2")).toBeVisible();
    await expect(page.locator("#task-3")).toBeVisible();
    await expect(page.locator("#page-4-link")).toBeVisible();
    expect(requests).toBe(3);
});

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });

    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("pagination", window.PaginationController);
    });
}

async function installStructuralCss(page) {
    const css = await readFile("resources/css/structural.css", "utf8");
    const paginationRules = [
        ...css.matchAll(/\[data-slot="pagination"\][^{]+\{[^}]+\}/g),
    ].map((match) => match[0]).join("\n");

    if (!paginationRules.includes("pagination-next-loading-content")) {
        throw new Error("Failed to extract pagination structural CSS rules.");
    }

    await page.addStyleTag({ content: paginationRules });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/pagination_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class PaginationController extends Controller")
        .concat("\nwindow.PaginationController = PaginationController;\n");
}

function documentHtml(pageNumber, { infinite = false } = {}) {
    return `<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="turbo-prefetch" content="false"></head>
<body>
    <turbo-frame id="results" data-turbo-action="advance">
        <strong id="page-marker">Page ${pageNumber}</strong>
        <div id="tasks">
            <article id="task-${pageNumber}">Task ${pageNumber}</article>
        </div>
        <div id="tasks-pagination">
            <nav data-controller="pagination" data-slot="pagination" aria-label="Task pages" data-pagination-append-to-value="#tasks"${infinite ? ' data-pagination-infinite-value="true"' : ""}>
                <span data-slot="pagination-status" data-pagination-target="status" role="status" aria-live="polite" aria-atomic="true"></span>
                <a
                    id="page-${Number(pageNumber) + 1}-link"
                    href="/tasks?page=${Number(pageNumber) + 1}"
                    data-pagination-target="next"
                    data-action="click->pagination#load"
                >
                    <span data-slot="pagination-next-content">
                        <span data-slot="pagination-next-label">Load more</span>
                        <svg data-slot="pagination-next-icon" aria-hidden="true"></svg>
                    </span>
                    <span data-slot="pagination-next-loading-content">
                        <span data-slot="pagination-next-loading-label">Loading more</span>
                        <svg data-slot="pagination-next-spinner" aria-hidden="true"></svg>
                    </span>
                </a>
            </nav>
        </div>
    </turbo-frame>
</body>
</html>`;
}
