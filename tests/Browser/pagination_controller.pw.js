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

test("incremental pagination preserves a results list in the same wrapper as the control", async ({ page }) => {
    await page.route("**/tasks*", (route) => route.fulfill({
        contentType: "text/html",
        body: documentHtml(new URL(route.request().url()).searchParams.get("page") ?? "1", { sharedWrapper: true }),
    }));
    await page.goto("https://pagination.test/tasks");
    await installController(page);
    await page.evaluate(() => { window.initialList = document.querySelector("#tasks"); });

    await page.locator("#page-2-link").click();
    await expect(page.locator("#tasks article")).toHaveText(["Task 1", "Task 2"]);
    await page.locator("#page-3-link").click();
    await expect(page.locator("#tasks article")).toHaveText(["Task 1", "Task 2", "Task 3"]);
    expect(await page.locator("#tasks").evaluate((list) => list === window.initialList)).toBe(true);
    await expect(page.locator("#tasks-pagination")).toBeVisible();
    await expect(page.locator('[data-controller="pagination"]')).toHaveCount(0);
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

for (const changedUrl of [true, false]) {
    test(`pagination discards a pending response when morph changes results ${changedUrl ? "and next URL" : "at the same next URL"}`, async ({ page }) => {
        await openWithPendingRequests(page);
        await page.evaluate(() => {
            const root = document.querySelector('[data-controller="pagination"]');
            window.originalPagination = { root, next: root.querySelector("a") };
        });

        const control = page.locator('[data-controller="pagination"]');
        const next = control.locator("a");
        await next.click();
        await expect(control).toHaveAttribute("data-state", "loading");

        await page.evaluate(({ changedUrl, incoming }) => {
            const updated = new DOMParser().parseFromString(incoming, "text/html").querySelector("turbo-frame");
            updated.querySelector("#task-1").textContent = "Filtered task";
            if (changedUrl) {
                updated.querySelector("a").setAttribute("href", "/tasks?page=2&filter=new");
                updated.querySelector("a").setAttribute("aria-label", "Load filtered tasks");
            }
            window.Turbo.renderStreamMessage(`<turbo-stream action="update" method="morph" target="results">
                <template>${updated.innerHTML}</template></turbo-stream>`);
        }, { changedUrl, incoming: documentHtml("1") });

        await expect(page.locator("#task-1")).toHaveText("Filtered task");
        expect(await next.evaluate((link) => link === window.originalPagination.next)).toBe(true);
        expect(await control.evaluate((root) => root === window.originalPagination.root)).toBe(true);
        await expect.poll(() => page.evaluate(() => window.nextRequests[0].signal.aborted)).toBe(true);
        await expect(next).not.toHaveAttribute("aria-busy");
        if (changedUrl) await expect(next).toHaveAccessibleName("Load filtered tasks");

        await next.click();
        await expect.poll(() => page.evaluate(() => window.nextRequests.length)).toBe(2);
        await page.evaluate(async (html) => {
            window.nextRequests[0].resolve(new Response(html, { status: 503 }));
            await new Promise(queueMicrotask);
        }, documentHtml("2"));
        await expect(control).toHaveAttribute("data-state", "loading");
        await expect(next).toHaveAttribute("aria-busy", "true");

        await page.evaluate((html) => {
            const fresh = new DOMParser().parseFromString(html, "text/html");
            fresh.querySelector("#task-2").textContent = "Filtered continuation";
            window.nextRequests[1].resolve(new Response(fresh.body.innerHTML));
        }, documentHtml("2"));
        await expect(page.locator("#tasks article")).toHaveText(["Filtered task", "Filtered continuation"]);
        await expect(page.locator("#page-3-link")).toBeFocused();
    });
}

test("infinite pagination resumes on the new next link after a morph supersedes a load", async ({ page }) => {
    await openWithPendingRequests(page, { infinite: true });
    await expect.poll(() => page.evaluate(() => window.nextRequests.length)).toBe(1);

    await page.evaluate(({ current }) => {
        const incoming = new DOMParser().parseFromString(current, "text/html").querySelector("turbo-frame");
        incoming.querySelector("#task-1").textContent = "Filtered task";
        incoming.querySelector("a").setAttribute("href", "/tasks?page=2&filter=new");
        window.Turbo.renderStreamMessage(`<turbo-stream action="update" method="morph" target="results">
            <template>${incoming.innerHTML}</template></turbo-stream>`);
    }, { current: documentHtml("1", { infinite: true }) });

    await expect.poll(() => page.evaluate(() => window.nextRequests.map(({ url }) => url))).toEqual([
        "https://pagination.test/tasks?page=2",
        "https://pagination.test/tasks?page=2&filter=new",
    ]);
    await page.evaluate(async ({ stale, fresh }) => {
        window.nextRequests[0].resolve(new Response(stale));
        await new Promise(queueMicrotask);
        window.nextRequests[1].resolve(new Response(fresh));
    }, { stale: documentHtml("2"), fresh: documentHtml("2").replace("Task 2", "Filtered continuation") });
    await expect(page.locator("#tasks article")).toHaveText(["Filtered task", "Filtered continuation"]);
});

for (const [action, stream, expected] of [
    ["appending a broadcast item", `<turbo-stream action="append" target="tasks">
        <template><article id="broadcast">Broadcast task</article></template></turbo-stream>`, ["Task 1", "Broadcast task"]],
    ["editing one result", `<turbo-stream action="replace" method="morph" target="task-1">
        <template><article id="task-1">Edited task</article></template></turbo-stream>`, ["Edited task"]],
    ["removing one result", '<turbo-stream action="remove" target="task-1"></turbo-stream>', []],
]) {
    test(`pagination keeps the pending load while ${action}`, async ({ page }) => {
        await openWithPendingRequests(page);
        await page.locator("#page-2-link").click();
        await applyStream(page, stream);

        await expect(page.locator("#tasks article")).toHaveText(expected);
        expect(await page.evaluate(() => window.nextRequests.map(({ signal }) => signal.aborted))).toEqual([false]);
        await expect(page.locator('[data-controller="pagination"]')).toHaveAttribute("data-state", "loading");

        await resolveNextRequest(page, 0);
        await expect(page.locator("#tasks article")).toHaveText([...expected, "Task 2"]);
    });
}

test("infinite pagination does not restart its pending load after a broadcast append", async ({ page }) => {
    await openWithPendingRequests(page, { infinite: true });
    await expect.poll(() => page.evaluate(() => window.nextRequests.length)).toBe(1);

    await applyStream(page, `<turbo-stream action="append" target="tasks">
        <template><article id="broadcast">Broadcast task</article></template></turbo-stream>`);
    expect(await page.evaluate(() => window.nextRequests.map(({ signal }) => signal.aborted))).toEqual([false]);

    await resolveNextRequest(page, 0);
    await expect(page.locator("#tasks article")).toHaveText(["Task 1", "Broadcast task", "Task 2"]);
    expect(await page.evaluate(() => window.nextRequests.length)).toBe(1);
});

for (const [mode, method] of [["replacement", ""], ["morph", ' method="morph"']]) {
    test(`pagination discards its pending load after a whole-list Stream update with ${mode}`, async ({ page }) => {
        await openWithPendingRequests(page);
        await page.locator("#page-2-link").click();
        await applyStream(page, `<turbo-stream action="update"${method} targets="#tasks">
            <template><article id="task-1">Refreshed task</article></template></turbo-stream>`);

        await expect(page.locator("#tasks article")).toHaveText(["Refreshed task"]);
        expect(await page.evaluate(() => window.nextRequests[0].signal.aborted)).toBe(true);
        await expect(page.locator('[data-controller="pagination"]')).not.toHaveAttribute("aria-busy");
        await resolveNextRequest(page, 0);
        await expect(page.locator("#tasks article")).toHaveText(["Refreshed task"]);
    });
}

test("pagination leaves a load pending if the whole-list Stream update is prevented", async ({ page }) => {
    await openWithPendingRequests(page);
    await page.locator("#page-2-link").click();
    await page.evaluate(() => {
        document.addEventListener("turbo:before-stream-render", (event) => event.preventDefault(), { once: true });
    });
    await applyStream(page, `<turbo-stream action="update" target="tasks">
        <template><article>Unrendered task</article></template></turbo-stream>`);

    expect(await page.evaluate(() => window.nextRequests[0].signal.aborted)).toBe(false);
    await resolveNextRequest(page, 0);
    await expect(page.locator("#tasks article")).toHaveText(["Task 1", "Task 2"]);
});

test("changing append-to invalidates only the request for the old results destination", async ({ page }) => {
    await openWithPendingRequests(page);
    const control = page.locator('[data-controller="pagination"]');
    await page.locator("#page-2-link").click();
    await control.evaluate((nav) => {
        const list = document.createElement("div");
        list.id = "other-tasks";
        list.innerHTML = "<article>Other task 1</article>";
        nav.parentElement.before(list);
        nav.dataset.paginationAppendToValue = "#other-tasks";
    });
    await expect.poll(() => page.evaluate(() => window.nextRequests[0].signal.aborted)).toBe(true);
    await expect(control).not.toHaveAttribute("data-state", "loading");

    await page.locator("#page-2-link").click();
    await resolveNextRequest(page, 0);
    await expect(control).toHaveAttribute("data-state", "loading");
    await resolveNextRequest(page, 1, documentHtml("2").replace('id="tasks"', 'id="other-tasks"'));
    await expect(page.locator("#tasks article")).toHaveText(["Task 1"]);
    await expect(page.locator("#other-tasks article")).toHaveText(["Other task 1", "Task 2"]);
});

async function openWithPendingRequests(page, { infinite = false } = {}) {
    await page.route("**/tasks", (route) => route.fulfill({
        contentType: "text/html",
        body: documentHtml("1", { infinite }),
    }));
    await page.goto("https://pagination.test/tasks");
    await page.evaluate((autoLoad) => {
        if (autoLoad) {
            window.IntersectionObserver = class {
                constructor(callback) { this.callback = callback; }
                observe(element) { queueMicrotask(() => this.callback([{ isIntersecting: true, target: element }])); }
                disconnect() {}
            };
        }
        window.nextRequests = [];
        // Deliver even an aborted response to check that it no longer owns the UI.
        window.fetch = (url, { signal }) => new Promise((resolve) => {
            window.nextRequests.push({ url, signal, resolve });
        });
    }, infinite);
    await installController(page);
}

async function applyStream(page, html) {
    await page.evaluate(async (stream) => {
        window.Turbo.renderStreamMessage(stream);
        const element = document.querySelector("turbo-stream");
        if (element) await element.render();
    }, html);
}

async function resolveNextRequest(page, index, html = documentHtml("2")) {
    await page.evaluate(async ({ index, html }) => {
        window.nextRequests[index].resolve(new Response(html));
        await new Promise((resolve) => setTimeout(resolve, 0));
    }, { index, html });
}

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

function documentHtml(pageNumber, { infinite = false, sharedWrapper = false } = {}) {
    const results = `<div id="tasks"><article id="task-${pageNumber}">Task ${pageNumber}</article></div>`;
    const terminal = sharedWrapper && pageNumber === "3";

    return `<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="turbo-prefetch" content="false"></head>
<body>
    <turbo-frame id="results" data-turbo-action="advance">
        <strong id="page-marker">Page ${pageNumber}</strong>
        ${sharedWrapper ? "" : results}
        <div id="tasks-pagination">
            ${sharedWrapper ? results : ""}
            ${terminal ? "" : `<nav data-controller="pagination" data-slot="pagination" aria-label="Task pages" data-pagination-append-to-value="#tasks"${infinite ? ' data-pagination-infinite-value="true"' : ""}>
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
            </nav>`}
        </div>
    </turbo-frame>
</body>
</html>`;
}
