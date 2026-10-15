import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { createServer } from "node:http";

test("opens when dynamic content is inserted and closes cleanly through the public API", async ({ page }) => {
    await page.setContent(`
        <div data-controller="modal">
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                data-modal-lock-scroll-class="overflow-hidden"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <turbo-frame id="modal-frame" data-modal-target="dynamicContent"></turbo-frame>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    const frame = page.locator("#modal-frame");
    const modal = page.locator('[data-modal-target="modal"]');

    await frame.evaluate((element) => {
        const content = document.createElement("div");
        content.textContent = "Loaded content";
        element.appendChild(content);
    });

    await expect(modal).toHaveAttribute("data-state", "open");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(frame).toContainText("Loaded content");

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");

        controller.close();
    });

    await expect(modal).toHaveAttribute("data-state", "closed");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect(frame).toBeEmpty();
});

for (const family of ["modal", "sheet", "drawer"]) {
    test(`closeOverlay closes a static ${family} without deleting its reusable content`, async ({ page }) => {
        await page.setContent(`
        <div id="static-dialog" data-controller="${family}">
            <button id="open-static" data-action="${family}#open">Open</button>
            <div data-${family}-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-${family}-target="backdrop"></div>
                <div data-${family}-target="dialog"><p id="static-content">Reusable content</p></div>
            </div>
        </div>
    `);
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("modal", window.ModalController);
            window.StimulusApplication.register("sheet", window.SheetController);
            window.StimulusApplication.register("drawer", window.DrawerController);
        });

        await page.locator("#open-static").click();
        await expect(page.locator(`#static-dialog [data-${family}-target="modal"]`)).toHaveAttribute(
            "data-state",
            "open",
        );
        await page.evaluate(() =>
            window.Turbo.renderStreamMessage(
                '<turbo-stream action="hotwire-overlay-close" target="static-dialog"></turbo-stream>',
            ),
        );
        await expect(page.locator(`#static-dialog [data-${family}-target="modal"]`)).toHaveAttribute("hidden", "");
        await expect(page.locator("#static-content")).toHaveText("Reusable content");

        await page.locator("#open-static").click();
        await expect(page.locator(`#static-dialog [data-${family}-target="modal"]`)).toHaveAttribute(
            "data-state",
            "open",
        );
    });
}

test("a server Stream opens a Sheet from one host with presentation and a safe validation source", async ({ page }) => {
    const errors = [];
    page.on("console", (message) => {
        if (message.type() === "error") errors.push(message.text());
    });
    await page.route("http://overlays.test/", (route) =>
        route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" }),
    );
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <div id="sheet-shell" data-controller="sheet" data-hotwire-overlay-host="sheet" style="--sheet-width: 75%">
            <div data-sheet-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog" data-side="right"><turbo-frame id="sheet" data-sheet-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("sheet", window.SheetController);
    });

    await expect
        .poll(() => page.evaluate(() => typeof window.Turbo.StreamActions["hotwire-overlay-open"]))
        .toBe("function");

    await page.evaluate(() =>
        window.Turbo.renderStreamMessage(`
        <turbo-stream action="hotwire-overlay-open" target="sheet" data-overlay-family="sheet" data-overlay-side="bottom" data-overlay-size="40vh" data-overlay-source="/settings/edit">
            <template><h2 data-slot="sheet-title">Review</h2><form action="/settings" method="post"><button type="submit">Save</button></form></template>
        </turbo-stream>
    `),
    );

    const instance = page.locator('[data-controller="sheet"][data-hotwire-overlay-instance]');
    await expect(instance).toHaveCount(1);
    await expect(instance.locator('[data-sheet-target="modal"]')).toHaveAttribute("data-state", "open");
    await expect(instance.locator('[data-sheet-target="dialog"]')).toHaveAttribute("data-side", "bottom");
    await expect.poll(() => instance.evaluate((el) => el.style.getPropertyValue("--sheet-height"))).toBe("40vh");
    await expect(instance.locator('input[name="_turbo_frame_src"]')).toHaveValue("http://overlays.test/settings/edit");
    await expect(instance.locator("turbo-frame")).not.toHaveAttribute("src", /.+/);
    await expect(page.locator("#sheet-shell [data-sheet-target='dialog']")).toHaveAttribute("data-side", "right");

    const parentId = await instance.first().locator("turbo-frame").getAttribute("id");
    await instance.first().locator('button[type="submit"]').focus();
    await page.evaluate(
        (origin) =>
            window.Turbo.renderStreamMessage(`
        <turbo-stream action="hotwire-overlay-open" target="sheet" data-overlay-family="sheet" data-overlay-origin="${origin}">
            <template><p>Nested review</p></template>
        </turbo-stream>
    `),
        parentId,
    );
    await expect(instance).toHaveCount(2);
    await expect(instance.last()).toContainText("Nested review");
    const childId = await instance.last().locator("turbo-frame").getAttribute("id");
    await page.evaluate(
        (id) =>
            window.Turbo.renderStreamMessage(
                `<turbo-stream action="hotwire-overlay-close" target="${id}"></turbo-stream>`,
            ),
        childId,
    );
    await expect(instance).toHaveCount(1);
    await expect(instance.first().locator('button[type="submit"]')).toBeFocused();

    await page.evaluate(
        (origin) =>
            window.Turbo.renderStreamMessage(`
            <turbo-stream action="hotwire-overlay-open" target="sheet" data-overlay-family="sheet" data-overlay-origin="${origin}">
                <template><p>Nested review again</p></template>
            </turbo-stream>
        `),
        parentId,
    );
    await expect(instance).toHaveCount(2);
    await page.evaluate(
        (origin) =>
            window.Turbo.renderStreamMessage(
                `<turbo-stream action="hotwire-overlay-close" target="${origin}"></turbo-stream>`,
            ),
        parentId,
    );
    await expect(instance).toHaveCount(0);

    await page.evaluate(
        (origin) =>
            window.Turbo.renderStreamMessage(`
        <turbo-stream action="hotwire-overlay-open" target="sheet" data-overlay-family="sheet" data-overlay-origin="${origin}">
            <template><p>Late review</p></template>
        </turbo-stream>
    `),
        parentId,
    );
    await expect.poll(() => errors.filter((message) => message.includes("no longer active")).length).toBe(1);
    await expect(instance).toHaveCount(0);
});

test("a form opened by a server Stream redirects validation back to its source instead of the background page", async ({
    page,
}) => {
    const requests = [];
    const server = createServer((request, response) => {
        requests.push({
            path: request.url,
            frame: request.headers["turbo-frame"],
            host: request.headers["x-hotwire-overlay-host"],
        });
        request.resume();
        if (request.url === "/save") {
            response.writeHead(303, { Location: "/edit" });
            response.end();
        } else if (request.url === "/edit") {
            response.writeHead(200, { "Content-Type": "text/html" });
            response.end(
                `<turbo-frame id="${request.headers["turbo-frame"]}"><p>Validation error</p><form action="/save" method="post"><button>Retry</button></form></turbo-frame>`,
            );
        } else {
            response.writeHead(200, { "Content-Type": "text/html" });
            response.end("<html><body></body></html>");
        }
    });
    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    try {
        const address = server.address();
        await page.goto(`http://127.0.0.1:${address.port}/`);
        await page.setContent(`
            <div data-controller="modal" data-hotwire-overlay-host="modal">
                <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                    <div data-modal-target="backdrop"></div>
                    <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
                </div>
            </div>
        `);
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("modal", window.ModalController);
        });
        await expect
            .poll(() => page.evaluate(() => typeof window.Turbo.StreamActions["hotwire-overlay-open"]))
            .toBe("function");
        await page.evaluate(() =>
            window.Turbo.renderStreamMessage(`
            <turbo-stream action="hotwire-overlay-open" target="modal" data-overlay-family="modal" data-overlay-source="/edit">
                <template><form action="/save" method="post"><button type="submit">Save</button></form></template>
            </turbo-stream>
        `),
        );

        const instance = page.locator('[data-controller="modal"][data-hotwire-overlay-instance]');
        await expect(instance).toHaveCount(1);
        const id = await instance.locator("turbo-frame").getAttribute("id");
        await expect(instance.locator('input[name="_turbo_frame_src"]')).toHaveValue(
            `http://127.0.0.1:${address.port}/edit`,
        );
        await instance.locator('button[type="submit"]').click();
        await expect(instance).toContainText("Validation error");
        await expect(instance).toHaveCount(1);
        expect(requests.slice(1)).toEqual([
            { path: "/save", frame: id, host: "modal" },
            { path: "/edit", frame: id, host: "modal" },
        ]);
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

for (const family of ["modal", "sheet", "drawer"]) {
    test(`frame content names the ${family} before it opens`, async ({ page }) => {
        await mountFrameOverlay(page, family);

        const frame = page.locator(`#${family}-frame`);
        const overlay = page.locator(`[data-${family}-target="modal"]`);
        await frame.evaluate((element, identifier) => {
            element.innerHTML = `
                <h2 data-slot="${identifier}-title">Loaded title</h2>
                <p data-slot="${identifier}-description">Loaded description</p>
            `;
        }, family);

        await expect(overlay).toHaveAttribute("data-state", "open");
        await expect(overlay).toHaveAttribute("aria-labelledby", `${family}-shell-title`);
        await expect(overlay).toHaveAttribute("aria-describedby", `${family}-shell-description`);
        await expect(page.locator(`#${family}-shell-title`)).toHaveText("Loaded title");
        await expect(page.locator(`#${family}-shell-description`)).toHaveText("Loaded description");

        await frame.evaluate((element) => element.replaceChildren());

        await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
        await expect(overlay).not.toHaveAttribute("aria-describedby", /.+/);
    });
}

test("a morphed sheet retains its active size without freezing unrelated inline styles", async ({ page }) => {
    await page.setContent(`
        <style>[data-slot="sheet-content"] { width: var(--sheet-width); max-width: 24rem; }</style>
        <div id="sheet-shell" data-controller="sheet" data-sheet-lock-scroll-class="overflow-hidden" style="--sheet-width: 75%; color: red">
            <a id="open" data-turbo-frame="settings-panel" data-overlay-family="sheet" data-overlay-side="left" data-overlay-size="28rem">Settings</a>
            <div data-sheet-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-slot="sheet-content" data-sheet-target="dialog" data-side="right">
                    <turbo-frame id="settings-panel" data-sheet-target="dynamicContent"></turbo-frame>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("sheet", window.SheetController);
    });

    await page.locator("#open").click();
    await expect(page.locator('[data-sheet-target="dialog"]')).toHaveAttribute("data-side", "left");
    await expect(page.locator('[data-sheet-target="dialog"]')).toHaveCSS("max-width", "100%");

    await page.evaluate(() => {
        const root = document.querySelector("#sheet-shell");
        const next = root.cloneNode(true);
        next.style.cssText = "--sheet-width: 90%; color: blue";
        next.querySelector('[data-sheet-target="dialog"]').dataset.side = "top";
        next.querySelector('[data-sheet-target="dialog"]').style.removeProperty("max-width");
        window.Turbo.morphElements(root, next);
    });

    await expect(page.locator("#sheet-shell")).toHaveCSS("color", "rgb(0, 0, 255)");
    await expect(page.locator('[data-sheet-target="dialog"]')).toHaveAttribute("data-side", "left");
    await expect
        .poll(() => page.locator("#sheet-shell").evaluate((el) => el.style.getPropertyValue("--sheet-width")))
        .toBe("28rem");

    await page.evaluate(async () => {
        const root = document.querySelector("#sheet-shell");
        await window.StimulusApplication.getControllerForElementAndIdentifier(root, "sheet").open();
        await window.StimulusApplication.getControllerForElementAndIdentifier(root, "sheet").close();
    });
    await expect(page.locator('[data-sheet-target="dialog"]')).toHaveAttribute("data-side", "top");
    await expect
        .poll(() => page.locator("#sheet-shell").evaluate((el) => el.style.getPropertyValue("--sheet-width")))
        .toBe("90%");
    await expect(page.locator('[data-sheet-target="dialog"]')).toHaveCSS("max-width", "384px");
});

test("a single modal host opens nested frame instances without replacing the parent's draft", async ({ page }) => {
    const frameRequests = [];
    const saveRequests = [];
    await page.route("http://overlays.test/**", async (route) => {
        const request = route.request();
        if (new URL(request.url()).pathname === "/") {
            await route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" });
            return;
        }
        if (new URL(request.url()).pathname === "/save") {
            saveRequests.push({
                id: request.headers()["turbo-frame"],
                host: request.headers()["x-hotwire-overlay-host"],
                instance: request.headers()["x-hotwire-overlay-instance"],
            });
            await route.fulfill({
                status: 422,
                contentType: "text/html",
                body: `<turbo-frame id="${request.headers()["turbo-frame"]}"><p>Validation error</p><button type="button" data-action="modal#close">Close</button></turbo-frame>`,
            });
            return;
        }
        frameRequests.push({
            id: request.headers()["turbo-frame"],
            host: request.headers()["x-hotwire-overlay-host"],
            instance: request.headers()["x-hotwire-overlay-instance"],
        });
        const frameId = request.headers()["turbo-frame"];
        await route.fulfill({
            status: 200,
            contentType: "text/html",
            body: `<turbo-frame id="${frameId}"><h2 data-slot="modal-title">Editor</h2><input aria-label="Draft"><a href="/edit" data-turbo-frame="modal">Open child</a><a href="/step">Next step</a><form action="/save" method="post"><button type="submit">Save</button></form><button type="button" data-action="modal#close">Close</button></turbo-frame>`,
        });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <a id="open-editor" href="/edit" data-turbo-frame="modal">Open editor</a>
        <div id="modal-shell" data-controller="modal" data-hotwire-overlay-host="modal" data-modal-lock-scroll-class="overflow-hidden">
            <div data-hotwire-overlay-labels data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" aria-describedby="shell-description" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><p id="shell-description">Hint</p><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-editor").hover();
    await page.waitForTimeout(150);
    expect(frameRequests).toHaveLength(0);
    await page.locator("#open-editor").click();
    const instances = page.locator("[data-hotwire-overlay-instance]");
    await expect(instances).toHaveCount(1);
    const parent = instances.first();
    const parentId = await parent.locator("turbo-frame").getAttribute("id");
    await parent.locator("input").fill("Unsaved sale draft");
    await parent.locator("a", { hasText: "Open child" }).click();

    await expect(instances).toHaveCount(2);
    const child = instances.last();
    const childId = await child.locator("turbo-frame").getAttribute("id");
    expect(childId).not.toBe(parentId);
    await expect(child.locator("input")).toBeVisible();
    const parentLabel = await parent.locator('[data-modal-target="modal"]').getAttribute("aria-labelledby");
    const childLabel = await child.locator('[data-modal-target="modal"]').getAttribute("aria-labelledby");
    expect(parentLabel).toBeTruthy();
    expect(childLabel).toBeTruthy();
    expect(childLabel).not.toBe(parentLabel);
    const parentDescription = await parent.locator('[data-modal-target="modal"]').getAttribute("aria-describedby");
    const childDescription = await child.locator('[data-modal-target="modal"]').getAttribute("aria-describedby");
    expect(parentDescription).not.toBe(childDescription);
    await expect(page.locator(`#${parentDescription}`)).toHaveText("Hint");
    await expect(page.locator(`#${childDescription}`)).toHaveText("Hint");
    expect(
        await page.locator("[id]").evaluateAll((elements) => {
            const ids = elements.map((element) => element.id);
            return ids.length === new Set(ids).size;
        }),
    ).toBe(true);
    expect(frameRequests).toEqual([
        { id: parentId, host: "modal", instance: parentId.slice("modal--".length) },
        { id: childId, host: "modal", instance: childId.slice("modal--".length) },
    ]);

    await child.locator("a", { hasText: "Next step" }).click();
    await expect.poll(() => frameRequests.length).toBe(3);
    expect(frameRequests[2]).toEqual({ id: childId, host: "modal", instance: childId.slice("modal--".length) });
    await expect(instances).toHaveCount(2);

    await child.locator('button[type="submit"]').click();
    await expect.poll(() => saveRequests.length).toBe(1);
    await expect(child).toContainText("Validation error");
    expect(saveRequests).toEqual([{ id: childId, host: "modal", instance: childId.slice("modal--".length) }]);

    await child.locator("button", { hasText: "Close" }).click();
    await expect(instances).toHaveCount(1);
    await expect(parent.locator("input")).toHaveValue("Unsaved sale draft");
    await expect(parent.locator("a", { hasText: "Open child" })).toBeFocused();
});

async function mountRefreshOverlayStack(
    page,
    { method = "replace", ignored = true, hostLoading = "eager", nestedLazy = false } = {},
) {
    const requests = [];
    let pageRequests = 0;
    const shell = `<div data-controller="modal" data-hotwire-overlay-host="modal" data-modal-lock-scroll-class="overflow-hidden">
        <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
            <div data-modal-target="backdrop"></div><div data-modal-target="dialog"><turbo-frame id="modal" loading="${hostLoading}" data-modal-target="dynamicContent"></turbo-frame></div>
        </div>
    </div>`;
    await page.route("http://overlays.test/**", async (route) => {
        const request = route.request();
        const path = new URL(request.url()).pathname;
        if (path === "/") {
            pageRequests++;
            return route.fulfill({
                contentType: "text/html",
                body: `<html><body><p id="page-version">Page ${pageRequests}</p>${shell}</body></html>`,
            });
        }
        if (path === "/save") {
            return route.fulfill({
                contentType: "text/vnd.turbo-stream.html",
                body: `<turbo-stream action="refresh" method="${method}" ${ignored ? `request-id="${request.headers()["x-turbo-request-id"]}"` : ""}></turbo-stream>`,
            });
        }
        requests.push(path);
        const frameId = request.headers()["turbo-frame"];
        const content =
            path === "/details"
                ? "<p>Details loaded</p>"
                : `<input aria-label="Draft"><a href="/editor" data-turbo-frame="modal">Child editor</a><form method="post" action="/save"><button>Save without navigation</button></form>${nestedLazy ? `<turbo-frame id="${frameId}-details" loading="lazy" src="/details" hidden></turbo-frame>` : ""}`;
        return route.fulfill({
            contentType: "text/html",
            body: `<turbo-frame id="${frameId}">${content}</turbo-frame>`,
        });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <a href="/editor" data-turbo-frame="modal">Open editor</a>
        ${shell}
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        const original = window.Turbo.StreamActions.refresh;
        window.refreshCalls = 0;
        window.Turbo.StreamActions.refresh = function () {
            window.refreshCalls++;
            return original.call(this);
        };
    });
    await page.getByRole("link", { name: "Open editor", exact: true }).click();
    const openings = page.locator("[data-hotwire-overlay-instance]");
    const parent = openings.first();
    await parent.getByLabel("Draft").fill("Unsaved parent");
    await parent.getByRole("link", { name: "Child editor", exact: true }).click();
    const child = openings.last();
    await child.getByLabel("Draft").fill("Unsaved child");

    return { openings, parent, child, requests, pageRequests: () => pageRequests };
}

test("a refresh ignored by Turbo does not close stacked overlay drafts", async ({ page }) => {
    const { openings, parent, child, requests, pageRequests } = await mountRefreshOverlayStack(page);
    await child.getByRole("button", { name: "Save without navigation", exact: true }).click();

    await expect.poll(() => page.evaluate(() => window.refreshCalls)).toBe(1);
    await expect(page.locator("turbo-stream")).toHaveCount(0);
    await expect(openings).toHaveCount(2);
    await expect(parent.getByLabel("Draft")).toHaveValue("Unsaved parent");
    await expect(child.getByLabel("Draft")).toHaveValue("Unsaved child");
    expect(requests).toEqual(["/editor", "/editor"]);
    expect(pageRequests()).toBe(1);
});

for (const method of ["replace", "morph"]) {
    test(`an accepted ${method} refresh runs once and discards the overlay stack`, async ({ page }) => {
        const { openings, child, pageRequests } = await mountRefreshOverlayStack(page, { method, ignored: false });
        await child.getByRole("button", { name: "Save without navigation", exact: true }).click();

        await expect(page.locator("#page-version")).toHaveText("Page 2");
        await expect(openings).toHaveCount(0);
        await expect(page.locator('[data-hotwire-overlay-host="modal"]')).toHaveCount(1);
        expect(await page.evaluate(() => window.refreshCalls)).toBe(1);
        expect(pageRequests()).toBe(2);
        await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
    });
}

test("an explicit opening eagerly loads its hidden host without eagerly loading nested lazy frames", async ({
    page,
}) => {
    const { openings, parent, child, requests } = await mountRefreshOverlayStack(page, {
        hostLoading: "lazy",
        nestedLazy: true,
    });
    await expect(openings).toHaveCount(2);
    await expect(parent.locator('turbo-frame[data-modal-target="dynamicContent"]')).toHaveAttribute("loading", "eager");
    await expect(child.locator('turbo-frame[data-modal-target="dynamicContent"]')).toHaveAttribute("loading", "eager");
    await expect(page.locator('[data-hotwire-overlay-host="modal"] turbo-frame')).toHaveAttribute("loading", "lazy");
    expect(requests).toEqual(["/editor", "/editor"]);

    const details = child.locator('turbo-frame[loading="lazy"]');
    await expect(details).toBeHidden();
    await details.evaluate((frame) => {
        frame.hidden = false;
    });
    await expect(details).toContainText("Details loaded");
    expect(requests).toEqual(["/editor", "/editor", "/details"]);
    await page.keyboard.press("Escape");
    await expect(openings).toHaveCount(1);
    await expect(parent.getByLabel("Draft")).toHaveValue("Unsaved parent");
});

test("a validation redirect preserves an instance's Frame context through the POST and redirected GET", async ({
    page,
}) => {
    const requests = [];
    let rejected = false;
    let rejectedDetails = false;
    const server = createServer((request, response) => {
        requests.push({
            path: request.url,
            method: request.method,
            frame: request.headers["turbo-frame"],
            host: request.headers["x-hotwire-overlay-host"],
            instance: request.headers["x-hotwire-overlay-instance"],
            origin: request.headers["x-hotwire-overlay-origin"],
        });
        request.resume();
        if (request.url === "/") {
            response.writeHead(200, { "Content-Type": "text/html" });
            response.end("<html><body></body></html>");
        } else if (request.url === "/save") {
            rejected = true;
            response.writeHead(303, { Location: "/edit" });
            response.end();
        } else if (request.url === "/save-details") {
            rejectedDetails = true;
            response.writeHead(303, { Location: "/details" });
            response.end();
        } else if (request.url === "/details") {
            response.writeHead(200, { "Content-Type": "text/html" });
            response.end(
                `<turbo-frame id="customer-details"><form action="/save-details" method="post"><input name="detail" aria-label="Detail"><button type="submit">Save detail</button></form>${rejectedDetails ? "<p>Detail validation error</p>" : ""}</turbo-frame>`,
            );
        } else {
            response.writeHead(200, { "Content-Type": "text/html" });
            response.end(`<turbo-frame id="${request.headers["turbo-frame"]}">
                <form action="/save" method="post"><input name="name" aria-label="Name"><input name="_turbo_frame_src" type="hidden" value="/edit"><button type="submit">Save</button></form>
                ${rejected ? "<p>Validation error</p>" : ""}
                <turbo-frame id="customer-details" src="/details"></turbo-frame>
            </turbo-frame>`);
        }
    });
    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    try {
        const address = server.address();
        await page.goto(`http://127.0.0.1:${address.port}/`);
        await page.setContent(`
            <a id="edit" href="/edit" data-turbo-frame="modal">Edit</a>
            <div id="modal-shell" data-controller="modal" data-hotwire-overlay-host="modal">
                <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                    <div data-modal-target="backdrop"></div>
                    <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
                </div>
            </div>
        `);
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("modal", window.ModalController);
        });

        await page.locator("#edit").click();
        const instance = page.locator("[data-hotwire-overlay-instance]");
        await instance.locator('input[name="name"]').fill("Unsubmitted parent data");
        const id = await instance.locator('turbo-frame[data-modal-target="dynamicContent"]').getAttribute("id");
        await instance.locator('turbo-frame[data-modal-target="dynamicContent"] > form button[type="submit"]').click();
        await expect(instance).toContainText("Validation error");
        await expect(instance).toHaveCount(1);
        expect(requests.filter(({ path }) => path === "/edit" || path === "/save")).toEqual([
            {
                path: "/edit",
                method: "GET",
                frame: id,
                host: "modal",
                instance: id.slice("modal--".length),
                origin: id,
            },
            {
                path: "/save",
                method: "POST",
                frame: id,
                host: "modal",
                instance: id.slice("modal--".length),
                origin: id,
            },
            {
                path: "/edit",
                method: "GET",
                frame: id,
                host: "modal",
                instance: id.slice("modal--".length),
                origin: id,
            },
        ]);

        const inner = instance.locator("#customer-details");
        await inner.locator('button[type="submit"]').click();
        await expect(inner).toContainText("Detail validation error");
        await expect(instance).toHaveCount(1);
        const details = requests.filter(({ path }) => path === "/details" || path === "/save-details");
        expect(details.slice(-3)).toEqual([
            {
                path: "/details",
                method: "GET",
                frame: "customer-details",
                host: undefined,
                instance: undefined,
                origin: id,
            },
            {
                path: "/save-details",
                method: "POST",
                frame: "customer-details",
                host: undefined,
                instance: undefined,
                origin: id,
            },
            {
                path: "/details",
                method: "GET",
                frame: "customer-details",
                host: undefined,
                instance: undefined,
                origin: id,
            },
        ]);
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

test("a child Stream updates only its initiating form and closes only that instance", async ({ page }) => {
    await page.route("http://overlays.test/**", async (route) => {
        const request = route.request();
        const path = new URL(request.url()).pathname;
        if (path === "/") {
            await route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" });
            return;
        }
        const id = request.headers()["turbo-frame"];
        if (path === "/customers") {
            await route.fulfill({
                status: 200,
                contentType: "text/vnd.turbo-stream.html",
                body: `<turbo-stream action="hotwire-overlay-return" target="${id}"><template><option value="1">Existing</option><option value="2" selected>New customer</option></template></turbo-stream><turbo-stream action="hotwire-overlay-close" target="${id}"></turbo-stream>`,
            });
            return;
        }
        const html =
            path === "/sale"
                ? `<form><input name="notes" aria-label="Notes"><p class="server-errors">Unrelated error</p><select name="customer_id" aria-label="Customer" data-overlay-return-region="customer"><option value="1">Existing</option></select><a href="/customer" data-turbo-frame="modal" data-overlay-return-to="customer">New customer</a><a href="/sale" data-turbo-frame="modal">Open another sale</a></form>`
                : `<form action="/customers" method="post"><input name="name" aria-label="New customer name"><button type="submit">Save customer</button></form>`;
        await route.fulfill({
            status: 200,
            contentType: "text/html",
            body: `<turbo-frame id="${id}">${html}</turbo-frame>`,
        });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <a id="sale" href="/sale" data-turbo-frame="modal">New sale</a>
        <div id="modal-shell" data-controller="modal" data-hotwire-overlay-host="modal" data-modal-lock-scroll-class="overflow-hidden">
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#sale").click();
    const instances = page.locator("[data-hotwire-overlay-instance]");
    const firstSale = instances.first();
    await firstSale.locator('input[name="notes"]').fill("First draft");
    await firstSale.getByText("Open another sale").click();
    await expect(instances).toHaveCount(2);
    const secondSale = instances.last();
    const firstForm = firstSale.locator("form");
    const secondForm = page.locator(`#${await secondSale.getAttribute("id")} form`);
    await secondForm.locator('input[name="notes"]').fill("Second draft");
    await secondForm.evaluate((form) => {
        form.dataset.preservedNode = "yes";
    });
    await secondSale.getByText("New customer").click();
    await expect(instances).toHaveCount(3);
    const child = instances.last();
    const childFrameId = await child.locator("turbo-frame").getAttribute("id");
    await child.locator('input[name="name"]').fill("Ada");
    await child.getByText("Save customer").click();

    await expect(instances).toHaveCount(2);
    await expect(firstForm.locator('input[name="notes"]')).toHaveValue("First draft");
    await expect(firstForm.locator('select[name="customer_id"]')).toHaveValue("1");
    await expect(secondForm.locator('input[name="notes"]')).toHaveValue("Second draft");
    await expect(secondForm.locator('select[name="customer_id"]')).toHaveValue("2");
    await expect(secondForm).toHaveAttribute("data-preserved-node", "yes");
    await expect(secondForm.locator(".server-errors")).toHaveText("Unrelated error");
    await expect(secondForm.getByRole("link", { name: "New customer" })).toBeFocused();

    await page.evaluate(
        (oldId) =>
            window.Turbo.renderStreamMessage(
                `<turbo-stream action="hotwire-overlay-return" target="${oldId}"><template><option value="stale" selected>Stale</option></template></turbo-stream>`,
            ),
        childFrameId,
    );
    await expect(secondForm.locator('select[name="customer_id"]')).toHaveValue("2");

    await secondForm.getByRole("link", { name: "New customer" }).click();
    await expect(instances).toHaveCount(3);
    const retryChild = instances.last();
    await secondForm.locator('select[name="customer_id"]').evaluate((region) => {
        region.outerHTML =
            '<select name="customer_id" aria-label="Customer" data-overlay-return-region="customer"><option value="1" selected>Existing</option></select>';
    });
    await retryChild.getByText("Save customer").click();
    await expect(retryChild.locator('[role="alert"]')).toContainText("return destination changed");
    await expect(retryChild.getByText("Save customer")).toBeDisabled();
    await expect(instances).toHaveCount(3);
    await expect(secondForm.locator('select[name="customer_id"]')).toHaveValue("1");
    await expect(firstForm.locator('select[name="customer_id"]')).toHaveValue("1");
});

test("a consumer renderer receives only declared context and close waits for its result", async ({ page }) => {
    let release;
    let fetched;
    const gate = new Promise((resolve) => {
        release = resolve;
    });
    const started = new Promise((resolve) => {
        fetched = resolve;
    });
    let rendererUrl;
    const scenario = await setupRenderedReturn(page, async (route) => {
        rendererUrl = new URL(route.request().url());
        fetched();
        await gate;
        await route.fulfill({ contentType: "text/html", body: '<p class="rich-result">New rendered customer</p>' });
    });

    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();
    await started;
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(1);
    expect(rendererUrl.searchParams.get("result")).toBe("42");
    expect(rendererUrl.searchParams.get("context[company_id]")).toBe("north");
    expect(rendererUrl.searchParams.has("notes")).toBe(false);
    release();

    await expect(page.locator(".rich-result")).toHaveText("New rendered customer");
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
    await expect(page.getByRole("link", { name: "New customer", exact: true })).toBeFocused();
    expect(scenario.creations()).toBe(1);
});

test("retrying a failed consumer renderer does not repeat customer creation", async ({ page }) => {
    let attempts = 0;
    const scenario = await setupRenderedReturn(page, async (route) => {
        attempts++;
        await route.fulfill(
            attempts === 1
                ? { status: 503, contentType: "text/html", body: "Unavailable" }
                : { contentType: "text/html", body: '<p class="rich-result">Recovered selection</p>' },
        );
    });

    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();
    await expect(page.getByRole("alert")).toContainText("Saved, but the field could not be updated");
    await expect(page.getByRole("button", { name: "Save customer" })).toBeDisabled();
    await page.getByRole("button", { name: "Retry return", exact: true }).click();

    await expect(page.locator(".rich-result")).toHaveText("Recovered selection");
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
    expect(scenario.creations()).toBe(1);
    expect(attempts).toBe(2);
});

async function setupRenderedReturn(page, render, { feedback = true, result = "42" } = {}) {
    let creations = 0;
    await page.route("http://overlays.test/**", async (route) => {
        const request = route.request();
        const path = new URL(request.url()).pathname;
        if (path === "/field") return render(route);
        if (path === "/customers") {
            creations++;
            const origin = request.headers()["x-hotwire-overlay-origin"];
            return route.fulfill({
                contentType: "text/vnd.turbo-stream.html",
                body: `<turbo-stream action="hotwire-overlay-return" target="${origin}" ${result === null ? "" : `data-overlay-result="${result}"`}><template><option>Native fallback must not be used</option></template></turbo-stream><turbo-stream action="hotwire-overlay-close" target="${origin}"></turbo-stream>`,
            });
        }
        const body =
            path === "/customer"
                ? `<turbo-frame id="${request.headers()["turbo-frame"]}"><form method="post" action="/customers"><button type="submit">Save customer</button></form></turbo-frame>`
                : "<html><body></body></html>";
        return route.fulfill({ contentType: "text/html", body });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <form id="parent"><select name="company_id"><option value="north">North</option><option value="south">South</option></select>
            <input name="notes" value="Private draft">
            <div data-overlay-return-region="customer" data-overlay-return-src="/field" data-overlay-return-fields="company_id"><p>Old selection</p></div>
            <a href="/customer" data-turbo-frame="modal" data-overlay-return-to="customer">New customer</a>
        </form>
        <div data-controller="modal" data-hotwire-overlay-host="modal">
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div><div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
            ${feedback ? '<template data-hotwire-overlay-return-feedback><div data-hotwire-overlay-return-error role="alert" tabindex="-1"><p data-hotwire-overlay-return-message></p><button type="button" data-hotwire-overlay-return-retry hidden>Retry return</button></div></template>' : ""}
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    return { creations: () => creations };
}

test("a rich return without its feedback template fails before opening the creation form", async ({ page }) => {
    const errors = [];
    page.on("console", (message) => {
        if (message.type() === "error") errors.push(message.text());
    });
    await setupRenderedReturn(page, (route) => route.fulfill({ contentType: "text/html", body: "Unused" }), {
        feedback: false,
    });
    await page.getByRole("link", { name: "New customer", exact: true }).click();

    await expect.poll(() => errors.some((message) => message.includes("feedback template"))).toBe(true);
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
});

test("a successful creation missing result identity cannot be submitted again", async ({ page }) => {
    const scenario = await setupRenderedReturn(
        page,
        (route) => route.fulfill({ contentType: "text/html", body: "Unused" }),
        { result: null },
    );
    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();

    await expect(page.getByRole("alert")).toContainText("Saved");
    await expect(page.getByRole("button", { name: "Save customer" })).toBeDisabled();
    expect(scenario.creations()).toBe(1);
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(1);
});

test("a changed unsaved context expires an in-flight renderer without replacing the field", async ({ page }) => {
    let release;
    let fetched;
    const gate = new Promise((resolve) => {
        release = resolve;
    });
    const started = new Promise((resolve) => {
        fetched = resolve;
    });
    await setupRenderedReturn(page, async (route) => {
        fetched();
        await gate;
        await route.fulfill({ contentType: "text/html", body: '<p class="rich-result">Wrong company</p>' });
    });
    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();
    await started;
    await page.locator('[name="company_id"]').evaluate((control) => {
        control.value = "south";
    });
    release();

    await expect(page.getByRole("alert")).toContainText("return context changed");
    await expect(page.locator('[data-overlay-return-region="customer"]')).toHaveText("Old selection");
    await expect(page.getByRole("button", { name: "Retry return", exact: true })).toBeHidden();
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(1);
});

test("a renderer conflict is terminal rather than a retryable creation failure", async ({ page }) => {
    await setupRenderedReturn(page, (route) =>
        route.fulfill({ status: 409, contentType: "text/html", body: "Different company" }),
    );
    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();

    await expect(page.getByRole("alert")).toContainText("incompatible with the return context");
    await expect(page.getByRole("button", { name: "Retry return", exact: true })).toBeHidden();
    await expect(page.locator('[data-overlay-return-region="customer"]')).toHaveText("Old selection");
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(1);
});

test("retiring an opening aborts its renderer and cannot update a reopened customer", async ({ page }) => {
    let fetched;
    let release;
    const started = new Promise((resolve) => {
        fetched = resolve;
    });
    const gate = new Promise((resolve) => {
        release = resolve;
    });
    await setupRenderedReturn(page, async (route) => {
        fetched();
        await gate;
        await route.fulfill({ contentType: "text/html", body: '<p class="rich-result">Stale customer</p>' });
    });
    const aborted = page.waitForEvent("requestfailed", (request) => new URL(request.url()).pathname === "/field");
    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();
    await started;
    await page.keyboard.press("Escape");
    await aborted;
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
    await page.getByRole("link", { name: "New customer", exact: true }).click();
    release();

    await expect(page.getByRole("button", { name: "Save customer" })).toBeEnabled();
    await expect(page.locator('[data-overlay-return-region="customer"]')).toHaveText("Old selection");
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(1);
});

test("renderer retry checks context again before issuing another GET", async ({ page }) => {
    let attempts = 0;
    await setupRenderedReturn(page, (route) => {
        attempts++;
        return route.fulfill({ status: 503, contentType: "text/html", body: "Unavailable" });
    });
    await page.getByRole("link", { name: "New customer", exact: true }).click();
    await page.getByRole("button", { name: "Save customer" }).click();
    await expect(page.getByRole("button", { name: "Retry return", exact: true })).toBeVisible();
    await page.locator('[name="company_id"]').evaluate((control) => {
        control.value = "south";
    });
    await page.getByRole("button", { name: "Retry return", exact: true }).click();

    await expect(page.getByRole("alert")).toContainText("return context changed");
    expect(attempts).toBe(1);
});

for (const source of [
    "https://other.test/field",
    "//overlays.test/field",
    "/field#fragment",
    "https://name:secret@overlays.test/field",
    " ",
    "/field\\other",
]) {
    test(`a return renderer rejects unsafe source ${source}`, async ({ page }) => {
        const errors = [];
        page.on("console", (message) => {
            if (message.type() === "error") errors.push(message.text());
        });
        await setupRenderedReturn(page, (route) => route.fulfill({ contentType: "text/html", body: "Unused" }));
        await page
            .locator("[data-overlay-return-src]")
            .evaluate((region, value) => region.setAttribute("data-overlay-return-src", value), source);
        await page.getByRole("link", { name: "New customer", exact: true }).click();

        await expect.poll(() => errors.some((message) => message.includes("safe same-origin URL"))).toBe(true);
        await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
    });
}

test("an ambiguous return region blocks opening instead of selecting a global field", async ({ page }) => {
    const errors = [];
    page.on("console", (message) => {
        if (message.type() === "error") errors.push(message.text());
    });
    await page.setContent(`
        <form>
            <select data-overlay-return-region="customer"></select>
            <select data-overlay-return-region="customer"></select>
            <a id="ambiguous-return" href="/customer" data-turbo-frame="modal" data-overlay-return-to="customer">New</a>
        </form>
        <div data-controller="modal" data-hotwire-overlay-host="modal">
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#ambiguous-return").click();
    await expect.poll(() => errors.filter((error) => error.includes("exactly one named region")).length).toBe(1);
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
});

test("a delayed return and close cannot affect a later opening for another form", async ({ page }) => {
    const pending = [];
    await page.route("http://overlays.test/**", async (route) => {
        const path = new URL(route.request().url()).pathname;
        if (path === "/") {
            await route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" });
        } else if (path === "/save") {
            pending.push({ route, id: route.request().headers()["turbo-frame"] });
        } else {
            const id = route.request().headers()["turbo-frame"];
            await route.fulfill({
                status: 200,
                contentType: "text/html",
                body: `<turbo-frame id="${id}"><form action="/save" method="post"><button type="submit">Save</button></form></turbo-frame>`,
            });
        }
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <form id="first"><select data-overlay-return-region="customer"><option value="1" selected>Old</option></select><a href="/create" data-turbo-frame="modal" data-overlay-return-to="customer">Open first</a></form>
        <form id="second"><select data-overlay-return-region="customer"><option value="1" selected>Old</option></select><a href="/create" data-turbo-frame="modal" data-overlay-return-to="customer">Open second</a></form>
        <div data-controller="modal" data-hotwire-overlay-host="modal">
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    const instances = page.locator("[data-hotwire-overlay-instance]");
    await page.locator("#first a").click();
    await instances.first().locator("button").click();
    await expect.poll(() => pending.length).toBe(1);
    await page.evaluate(() => {
        const root = document.querySelector("[data-hotwire-overlay-instance]");
        window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal").close();
    });
    await expect(instances).toHaveCount(0);

    await page.locator("#second a").click();
    await instances.first().locator("button").click();
    await expect.poll(() => pending.length).toBe(2);
    const response = (target, selected) =>
        `<turbo-stream action="hotwire-overlay-return" target="${target}"><template><option value="${selected}" selected>New</option></template></turbo-stream><turbo-stream action="hotwire-overlay-close" target="${target}"></turbo-stream>`;
    await pending[0].route
        .fulfill({ status: 200, contentType: "text/vnd.turbo-stream.html", body: response(pending[0].id, "stale") })
        .catch(() => {});
    await expect(instances).toHaveCount(1);
    await expect(page.locator("#first select")).toHaveValue("1");
    await expect(page.locator("#second select")).toHaveValue("1");

    await pending[1].route.fulfill({
        status: 200,
        contentType: "text/vnd.turbo-stream.html",
        body: response(pending[1].id, "current"),
    });
    await expect(instances).toHaveCount(0);
    await expect(page.locator("#second select")).toHaveValue("current");
    await expect(page.locator("#first select")).toHaveValue("1");
});

test("closing a drawer ancestor retires a child modal without losing the parent draft", async ({ page }) => {
    await page.route("http://overlays.test/**", async (route) => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;
        if (pathname === "/") {
            await route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" });
            return;
        }
        const id = request.headers()["turbo-frame"];
        const body =
            pathname === "/sale"
                ? `<input aria-label="Sale draft"><a href="/customer" data-turbo-frame="modal">New customer</a><button type="button" data-action="drawer#close">Close sale</button>`
                : `<h2 data-slot="modal-title">Customer</h2><button type="button" data-action="modal#close">Close customer</button>`;
        await route.fulfill({
            status: 200,
            contentType: "text/html",
            body: `<turbo-frame id="${id}">${body}</turbo-frame>`,
        });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <a href="/sale" data-turbo-frame="drawer" id="open-sale">New sale</a>
        <div id="drawer-shell" data-controller="drawer" data-hotwire-overlay-host="drawer" data-drawer-lock-scroll-class="overflow-hidden">
            <div data-drawer-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-drawer-target="backdrop"></div>
                <div data-drawer-target="dialog"><turbo-frame id="drawer" data-drawer-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
        <div id="modal-shell" data-controller="modal" data-hotwire-overlay-host="modal" data-modal-lock-scroll-class="overflow-hidden">
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("drawer", window.DrawerController);
    });

    await page.locator("#open-sale").click();
    const sale = page.locator('[data-controller="drawer"][data-hotwire-overlay-instance]');
    await expect(sale).toHaveCount(1);
    await sale.locator("input").fill("Unsaved sale");
    await sale.locator("a", { hasText: "New customer" }).click();
    const customer = page.locator('[data-controller="modal"][data-hotwire-overlay-instance]');
    await expect(customer).toHaveCount(1);
    await expect(customer).toContainText("Customer");
    await page.keyboard.press("Escape");
    await expect(customer).toHaveCount(0);
    await expect(sale.locator("input")).toHaveValue("Unsaved sale");

    await sale.locator("a", { hasText: "New customer" }).click();
    await expect(customer).toHaveCount(1);
    await page.evaluate(() => {
        const root = document.querySelector('[data-controller="drawer"][data-hotwire-overlay-instance]');
        window.StimulusApplication.getControllerForElementAndIdentifier(root, "drawer").close();
    });
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);

    await page.locator("#open-sale").click();
    await sale.locator("a", { hasText: "New customer" }).click();
    await expect(customer).toHaveCount(1);
    await sale.evaluate((element) => element.remove());
    await expect(customer).toHaveCount(0);
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);

    await page.locator("#open-sale").click();
    await sale.locator("a", { hasText: "New customer" }).click();
    await expect(customer).toHaveCount(1);
    await page.evaluate(() => window.dispatchEvent(new CustomEvent("turbo:before-cache")));
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
});

test("a cancelled frame load cannot populate a later opening of the same host", async ({ page }) => {
    const pending = [];
    await page.route("http://overlays.test/**", async (route) => {
        if (new URL(route.request().url()).pathname === "/slow") {
            pending.push(route);
            return;
        }
        await route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <a id="open-slow" href="/slow" data-turbo-frame="modal">Open</a>
        <div id="modal-shell" data-controller="modal" data-hotwire-overlay-host="modal">
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-slow").click();
    await expect.poll(() => pending.length).toBe(1);
    const instances = page.locator("[data-hotwire-overlay-instance]");
    const originalId = await instances.first().locator("turbo-frame").getAttribute("id");
    await page.evaluate(() => {
        const root = document.querySelector("[data-hotwire-overlay-instance]");
        window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal").close();
    });
    await expect(instances).toHaveCount(0);

    await page.locator("#open-slow").click();
    await expect.poll(() => pending.length).toBe(2);
    const nextId = await instances.first().locator("turbo-frame").getAttribute("id");
    expect(nextId).not.toBe(originalId);

    await pending[0]
        .fulfill({
            status: 200,
            contentType: "text/html",
            body: `<turbo-frame id="${originalId}">Stale content</turbo-frame>`,
        })
        .catch(() => {});
    await pending[1].fulfill({
        status: 200,
        contentType: "text/html",
        body: `<turbo-frame id="${nextId}">Current content</turbo-frame>`,
    });
    await expect(instances).toHaveCount(1);
    await expect(instances.first()).toContainText("Current content");
    await expect(instances.first()).not.toContainText("Stale content");
});

test("a generated Sheet instance uses the same hint and response presentation contract", async ({ page }) => {
    let pending;
    await page.route("http://overlays.test/**", async (route) => {
        if (new URL(route.request().url()).pathname === "/settings") {
            pending = route;
            return;
        }
        await route.fulfill({ status: 200, contentType: "text/html", body: "<html><body></body></html>" });
    });
    await page.goto("http://overlays.test/");
    await page.setContent(`
        <a id="open-settings" href="/settings" data-turbo-frame="sheet" data-overlay-family="sheet" data-overlay-side="left" data-overlay-size="28rem">Settings</a>
        <div id="sheet-shell" data-controller="sheet" data-hotwire-overlay-host="sheet" style="--sheet-width: 75%">
            <div data-sheet-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog" data-side="right">
                    <turbo-frame id="sheet" data-sheet-target="dynamicContent"></turbo-frame>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("sheet", window.SheetController);
    });

    await page.locator("#open-settings").click();
    const instance = page.locator('[data-controller="sheet"][data-hotwire-overlay-instance]');
    const dialog = instance.locator('[data-sheet-target="dialog"]');
    await expect(dialog).toHaveAttribute("data-side", "left");
    await expect.poll(() => instance.evaluate((el) => el.style.getPropertyValue("--sheet-width"))).toBe("28rem");
    await expect.poll(() => Boolean(pending)).toBe(true);
    const id = await instance.locator("turbo-frame").getAttribute("id");
    await pending.fulfill({
        status: 200,
        contentType: "text/html",
        body: `<turbo-frame id="${id}" data-overlay-family="sheet" data-overlay-side="top" data-overlay-size="40vh"><button type="button" data-action="sheet#close">Close</button></turbo-frame>`,
    });
    await expect(dialog).toHaveAttribute("data-side", "top");
    await expect.poll(() => instance.evaluate((el) => el.style.getPropertyValue("--sheet-height"))).toBe("40vh");
    await page.evaluate(
        (frameId) =>
            window.Turbo.renderStreamMessage(
                `<turbo-stream action="hotwire-overlay-close" target="${frameId}"></turbo-stream>`,
            ),
        id,
    );
    await expect(instance).toHaveCount(0);
    await expect(page.locator("#sheet-shell [data-sheet-target='dialog']")).toHaveAttribute("data-side", "right");
    await expect
        .poll(() => page.locator("#sheet-shell").evaluate((el) => el.style.getPropertyValue("--sheet-width")))
        .toBe("75%");
});

test("two families claiming the same Frame do not arbitrarily select a host", async ({ page }) => {
    const errors = [];
    page.on("console", (message) => {
        if (message.type() === "error") errors.push(message.text());
    });
    await page.setContent(`
        <a id="ambiguous" href="/new" data-turbo-frame="shared">Open</a>
        <div data-controller="modal" data-hotwire-overlay-host="shared">
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog"><turbo-frame id="shared" data-modal-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
        <div data-controller="sheet" data-hotwire-overlay-host="shared">
            <div data-sheet-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog"><turbo-frame id="shared" data-sheet-target="dynamicContent"></turbo-frame></div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("sheet", window.SheetController);
    });

    const cancelled = await page.locator("#ambiguous").evaluate((link) => {
        const event = new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 });
        link.dispatchEvent(event);
        return event.defaultPrevented;
    });
    expect(cancelled).toBe(true);
    await expect.poll(() => errors.filter((error) => error.includes("exactly one host")).length).toBeGreaterThan(0);
    await expect(page.locator("[data-hotwire-overlay-instance]")).toHaveCount(0);
});

for (const family of ["modal", "sheet", "drawer"]) {
    test(`${family} applies each initial focus strategy`, async ({ page }) => {
        const strategies = ["auto", "dialog", "first-focusable", "none"];
        await page.setContent(
            strategies
                .map(
                    (strategy) => `
            <div
                id="${family}-${strategy}"
                data-controller="${family}"
                data-${family}-initial-focus-value="${strategy}"
                data-${family}-lock-scroll-class="overflow-hidden"
            >
                <button id="${family}-${strategy}-trigger" data-action="${family}#open">Open</button>
                <div
                    id="${family}-${strategy}-overlay"
                    data-${family}-target="modal"
                    data-state="closed"
                    data-motion="none"
                    role="dialog"
                    tabindex="-1"
                    hidden inert
                >
                    <div data-${family}-target="backdrop"></div>
                    <div data-${family}-target="dialog">
                        <input id="${family}-${strategy}-first" />
                        <button id="${family}-${strategy}-autofocus" autofocus>Explicit</button>
                    </div>
                </div>
            </div>
        `,
                )
                .join(""),
        );
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("modal", window.ModalController);
            window.StimulusApplication.register("sheet", window.SheetController);
            window.StimulusApplication.register("drawer", window.DrawerController);
        });

        const expected = {
            auto: `#${family}-auto-autofocus`,
            dialog: `#${family}-dialog-overlay`,
            "first-focusable": `#${family}-first-focusable-first`,
            none: `#${family}-none-trigger`,
        };

        for (const strategy of strategies) {
            await page.locator(`#${family}-${strategy}-trigger`).click();
            await expect(page.locator(expected[strategy])).toBeFocused();

            if (strategy === "none") {
                await page.keyboard.press("Tab");
                await expect(page.locator(`#${family}-none-first`)).toBeFocused();
            }

            await page.evaluate(
                ({ family: identifier, strategy: value }) => {
                    const root = document.querySelector(`#${identifier}-${value}`);
                    const controller = window.StimulusApplication.getControllerForElementAndIdentifier(
                        root,
                        identifier,
                    );
                    controller.close();
                },
                { family, strategy },
            );
        }
    });
}

test("initial focus skips hidden candidates and follows value changes", async ({ page }) => {
    await page.setContent(`
        <div
            id="modal-root"
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <details><button id="collapsed">Collapsed</button></details>
                    <button id="hidden" style="display: none">Hidden</button>
                    <button id="visible">Visible</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await expect(page.locator("#visible")).toBeFocused();

    await page.evaluate(async () => {
        const root = document.querySelector("#modal-root");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.close();
        root.dataset.modalInitialFocusValue = "dialog";
    });
    await page.locator("#trigger").click();

    await expect(page.locator('[data-modal-target="modal"]')).toBeFocused();
});

test("the focus trap ignores invalid contenteditable candidates", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="first">First</button>
                    <button id="last">Last</button>
                    <div contenteditable="inherit">Not an editing host</div>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await page.locator("#last").focus();
    await page.keyboard.press("Tab");

    await expect(page.locator("#first")).toBeFocused();
});

test("wrapping focus scrolls the destination control into view", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div id="scroller" data-modal-target="dialog" style="height: 200px; overflow: auto">
                    <button id="first">First</button>
                    <div style="height: 2000px"></div>
                    <button id="last">Last</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await page.keyboard.press("Shift+Tab");

    await expect(page.locator("#last")).toBeFocused();
    await expect.poll(() => page.locator("#scroller").evaluate((element) => element.scrollTop)).toBeGreaterThan(0);
});

test("content-visibility auto does not remove controls from the focus trap", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog" style="position: relative; height: 2400px">
                    <div style="position: absolute; top: 1600px; content-visibility: auto; contain-intrinsic-size: 100px">
                        <button id="deferred">Deferred</button>
                    </div>
                    <button id="fallback">Fallback</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();

    await expect(page.locator("#deferred")).toBeFocused();
});

test("Tab from a programmatically focused descendant follows native order", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="dialog"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="first">First</button>
                    <div id="programmatic" tabindex="-1">Programmatic</div>
                    <button id="next">Next</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await page.locator("#programmatic").focus();
    await page.keyboard.press("Tab");

    await expect(page.locator("#next")).toBeFocused();
});

test("Tab from a negative-tabindex autofocus target stays inside the modal", async ({ page }) => {
    await page.setContent(`
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-action="modal#open">Open</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="first">First</button>
                    <button id="last">Last</button>
                    <button id="autofocus" tabindex="-1" autofocus>Autofocus</button>
                </div>
            </div>
        </div>
        <button id="outside">Outside</button>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await expect(page.locator("#autofocus")).toBeFocused();
    await page.keyboard.press("Tab");

    await expect(page.locator("#first")).toBeFocused();
});

test("a pre-rendered open modal with none does not honor native autofocus", async ({ page }) => {
    await page.setContent(`
        <button id="outside">Outside</button>
        <div
            data-controller="modal"
            data-modal-initial-focus-value="none"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <div
                data-modal-target="modal"
                data-state="open"
                data-motion="none"
                role="dialog"
                tabindex="-1"
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="autofocus" autofocus>Autofocus</button>
                </div>
            </div>
        </div>
    `);
    await page.locator("#outside").focus();
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await expect(page.locator("#outside")).toBeFocused();
});

test("a pre-rendered open modal applies an explicit strategy over existing focus", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="dialog"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <div
                data-modal-target="modal"
                data-state="open"
                data-motion="none"
                role="dialog"
                tabindex="-1"
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="focused">Focused before connect</button>
                </div>
            </div>
        </div>
    `);
    await page.locator("#focused").focus();
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await expect(page.locator('[data-modal-target="modal"]')).toBeFocused();
});

test("frame labels replace ambiguous authored ids with collision-free ids", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<span id="page-title">Layout title</span>',
    });

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Initial title</h2>';
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Initial title");

    await frame.evaluate((element) => {
        element.innerHTML = '<h2 id="page-title" data-slot="modal-title">Updated title</h2>';
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(overlay).toHaveAttribute("data-hotwire-overlay-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Updated title");
    await expect(page.locator("#page-title")).toHaveCount(1);
    await expect(page.locator("#page-title")).toHaveText("Layout title");
});

test("frame labels resync after relevant attribute mutations", async ({ page }) => {
    await mountFrameOverlay(page, "modal");

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Account settings</h2>';
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");

    await page.locator("#modal-shell-title").evaluate((title) => {
        title.id = "renamed-title";
    });
    await expect(overlay).toHaveAttribute("aria-labelledby", "renamed-title");

    await page.locator("#renamed-title").evaluate((title) => {
        title.removeAttribute("data-slot");
    });
    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);

    await page.getByText("Account settings").evaluate((title) => {
        title.setAttribute("data-slot", "modal-title");
    });
    await expect(overlay).toHaveAttribute("aria-labelledby", "renamed-title");
});

test("frame labels move from loading content to the response without claiming nested overlays", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<button id="modal-shell-title">Open</button>',
        loadingTemplate: '<h2 data-slot="modal-title">Loading post</h2>',
    });

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.dispatchEvent("turbo:before-fetch-request");

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title-2");
    await expect(page.locator("#modal-shell-title-2")).toHaveText("Loading post");
    await expect(overlay).toHaveAttribute("data-state", "open");

    await frame.evaluate((element) => {
        const replacement = document.createElement("turbo-frame");
        replacement.id = element.id;
        replacement.innerHTML = `
            <div role="dialog">
                <h2 data-slot="modal-title">Nested title</h2>
            </div>
            <h2 id="response-title" data-slot="modal-title">Edit post</h2>
        `;
        element.replaceWith(replacement);
        replacement.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "response-title");
    await expect(page.locator("#response-title")).toHaveText("Edit post");
    await expect(page.getByText("Nested title")).not.toHaveAttribute("id", /.+/);

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal").clearContent();
    });

    await expect(frame).toBeEmpty();
    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
});

test("frame labels do not override an authored accessible name", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        overlayAttributes: 'aria-label="Authored dialog"',
    });

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Loaded title</h2>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-label", "Authored dialog");
    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
});

test("frame labels preserve an authored labelledby reference", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<span id="app-title">Authored title</span>',
        overlayAttributes: 'aria-labelledby="app-title"',
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Loaded title</h2>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-labelledby", "app-title");
    await expect(page.locator("#app-title")).toHaveText("Authored title");
});

test("frame labels do not override an authored accessible description", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        overlayAttributes: 'aria-description="Authored description"',
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<p data-slot="modal-description">Loaded description</p>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-description", "Authored description");
    await expect(overlay).not.toHaveAttribute("aria-describedby", /.+/);
});

test("frame labels preserve an authored describedby reference", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<span id="app-description">Authored description</span>',
        overlayAttributes: 'aria-describedby="app-description"',
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<p data-slot="modal-description">Loaded description</p>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-describedby", "app-description");
    await expect(page.locator("#app-description")).toHaveText("Authored description");
});

test("frame labels resume generated ownership after a morph removes authored semantics", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        overlayAttributes: 'aria-label="Authored dialog"',
    });
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

    await page.locator('[data-modal-target="modal"]').evaluate((overlay) => {
        const replacement = overlay.cloneNode(true);
        replacement.removeAttribute("aria-label");
        replacement.querySelector("turbo-frame").innerHTML = '<h2 data-slot="modal-title">Morphed title</h2>';

        window.Turbo.morphElements(overlay, replacement);
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).not.toHaveAttribute("aria-label", /.+/);
    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(overlay).toHaveAttribute("data-hotwire-overlay-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Morphed title");
});

test("frame labels reacquire a replaced overlay target", async ({ page }) => {
    await mountFrameOverlay(page, "modal");
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Initial title</h2>';
    });
    await expect(page.locator('[data-modal-target="modal"]')).toHaveAttribute("aria-labelledby", "modal-shell-title");

    await page.locator('[data-modal-target="modal"]').evaluate((element) => {
        const replacement = element.cloneNode(true);
        replacement.removeAttribute("aria-labelledby");
        replacement.removeAttribute("data-hotwire-overlay-labelledby");
        replacement.querySelector('[data-slot="modal-title"]').removeAttribute("id");
        element.replaceWith(replacement);
    });

    const replacement = page.locator('[data-modal-target="modal"]');
    await expect(replacement).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Initial title");
});

test("frame removal clears managed references after close", async ({ page }) => {
    await mountFrameOverlay(page, "modal");
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = `
            <h2 data-slot="modal-title">Loaded title</h2>
            <p data-slot="modal-description">Loaded description</p>
        `;
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await page.locator("#modal-frame").evaluate((element) => element.remove());
    await page.evaluate(async () => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.close();
    });

    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
    await expect(overlay).not.toHaveAttribute("aria-describedby", /.+/);
    await expect(overlay).not.toHaveAttribute("data-hotwire-overlay-labelledby", /.+/);
    await expect(overlay).not.toHaveAttribute("data-hotwire-overlay-describedby", /.+/);
});

test("static overlay names survive close and reopen", async ({ page }) => {
    await page.setContent(`
        <div id="static-modal" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div
                data-hotwire-overlay-labels
                data-hotwire-overlay-labelledby="static-modal-title"
                data-slot="modal-overlay"
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                aria-labelledby="static-modal-title"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <h2 id="static-modal-title" data-slot="modal-title">Static title</h2>
                    <button id="close" data-action="modal#close">Close</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#open").click();
    await page.locator("#close").click();
    await page.locator("#open").click();

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-labelledby", "static-modal-title");
    await expect(page.locator("#static-modal-title")).toHaveText("Static title");
});

test("Turbo morph updates modal content without overwriting its presence state", async ({ page }) => {
    await page.setContent(`
        <div id="modal-shell" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div id="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <p id="modal-content">Initial content</p>
                    <p id="server-status" data-state="stale">Initial status</p>
                    <button data-action="modal#close">Close</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open").click();

    const modal = page.locator("#modal-overlay");
    await expect(modal).toHaveAttribute("data-state", "open");
    await expect.poll(async () => modal.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await page.evaluate(() => {
        const replacement = document.createElement("div");
        replacement.id = "modal-overlay";
        replacement.setAttribute("data-modal-target", "modal");
        replacement.setAttribute("data-state", "closed");
        replacement.setAttribute("data-motion", "none");
        replacement.setAttribute("hidden", "");
        replacement.setAttribute("inert", "");
        replacement.innerHTML = `
            <div data-modal-target="backdrop"></div>
            <div data-modal-target="dialog">
                <p id="modal-content">Morphed open content</p>
                <p id="server-status" data-state="updated" hidden>Updated status</p>
                <button data-action="modal#close">Close</button>
            </div>
        `;

        window.Turbo.morphElements(document.querySelector("#modal-overlay"), replacement);
    });

    await expect(modal).toHaveAttribute("data-state", "open");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(modal).not.toHaveAttribute("inert", "");
    await expect(modal).toContainText("Morphed open content");
    await expect(page.locator("#server-status")).toHaveAttribute("data-state", "updated");
    await expect(page.locator("#server-status")).toHaveAttribute("hidden", "");
    await expect.poll(async () => modal.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.evaluate(async () => {
        const root = document.querySelector("#modal-shell");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.close();

        const replacement = document.createElement("div");
        replacement.id = "modal-overlay";
        replacement.setAttribute("data-modal-target", "modal");
        replacement.setAttribute("data-state", "open");
        replacement.setAttribute("data-motion", "none");
        replacement.innerHTML = `
            <div data-modal-target="backdrop"></div>
            <div data-modal-target="dialog">
                <p id="modal-content">Morphed closed content</p>
                <p id="server-status" data-state="final">Final status</p>
                <button data-action="modal#close">Close</button>
            </div>
        `;

        window.Turbo.morphElements(document.querySelector("#modal-overlay"), replacement);
    });

    await expect(modal).toHaveAttribute("data-state", "closed");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect(modal).toHaveAttribute("inert", "");
    await expect(modal).toContainText("Morphed closed content");
    await expect(page.locator("#server-status")).toHaveAttribute("data-state", "final");
    await expect(page.locator("#server-status")).not.toHaveAttribute("hidden", "");
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
});

test("Turbo Frame morph preserves an open drawer while updating its content", async ({ page }) => {
    await page.setContent(`
        <turbo-frame id="drawer-frame">
            <div id="drawer-shell" data-controller="drawer" data-drawer-lock-scroll-class="overflow-hidden">
                <button id="open-drawer" data-action="drawer#open">Open</button>
                <div id="drawer-overlay" data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-drawer-target="backdrop"></div>
                    <div data-drawer-target="dialog"><p id="drawer-content">Initial drawer</p></div>
                </div>
            </div>
        </turbo-frame>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("drawer", window.DrawerController);
    });

    await page.locator("#open-drawer").click();

    const drawer = page.locator("#drawer-overlay");
    await expect(drawer).toHaveAttribute("data-state", "open");

    await page.evaluate(() => {
        const replacement = document.createElement("turbo-frame");
        replacement.id = "drawer-frame";
        replacement.innerHTML = `
            <div id="drawer-shell" data-controller="drawer" data-drawer-lock-scroll-class="overflow-hidden">
                <button id="open-drawer" data-action="drawer#open">Open</button>
                <div id="drawer-overlay" data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-drawer-target="backdrop"></div>
                    <div data-drawer-target="dialog"><p id="drawer-content">Morphed drawer</p></div>
                </div>
            </div>
        `;

        window.Turbo.morphChildren(document.querySelector("#drawer-frame"), replacement);
    });

    await expect(drawer).toHaveAttribute("data-state", "open");
    await expect(drawer).not.toHaveAttribute("hidden", "");
    await expect(drawer).not.toHaveAttribute("inert", "");
    await expect(drawer).toContainText("Morphed drawer");
    await expect.poll(async () => drawer.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);
});

test("tabs from the modal close button into native accordion summaries", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="modal" data-modal-initial-focus-value="first-focusable">
            <button id="open-modal" data-action="modal#open">Open modal</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                data-modal-lock-scroll-class="overflow-hidden"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="close-modal" type="button" data-action="modal#close">Close</button>
                    <section data-controller="accordion" data-accordion-type-value="single">
                        <details data-accordion-target="item" data-value="billing">
                            <summary id="billing-summary">Billing</summary>
                            <section>Billing answers.</section>
                        </details>
                    </section>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-modal").click();
    await expect(page.locator("#close-modal")).toBeFocused();

    await page.keyboard.press("Tab");
    await expect(page.locator("#billing-summary")).toBeFocused();
});

test("nested modal overlay enters the browser top layer", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
            [data-hotwire-top-layer][popover][data-slot="modal-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-backdrop"] { position: absolute; inset: 0; }
            [data-slot="modal-positioner"] { position: relative; z-index: 1; }
            .hidden { pointer-events: none; }
            .visible { pointer-events: auto; }
            .dialog-hidden { opacity: 0; transform: scale(.8); }
            .dialog-visible { opacity: 1; transform: scale(1); }
        </style>
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <section style="width: 260px; height: 140px; overflow: hidden; transform: scale(.95); border-radius: 16px; background: white;">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div id="inner" data-controller="modal">
                            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                                <div data-slot="modal-positioner" data-modal-target="dialog">
                                    <button id="inner-close" data-action="modal#close">Close inner</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-outer").click();
    await page.evaluate(() => {
        const root = document.querySelector("#inner");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");

        controller.open({ target: document.querySelector("#open-inner") });
    });

    const innerOverlay = page.locator("#inner [data-modal-target='modal']");

    await expect(innerOverlay).toHaveAttribute("popover", "manual");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => innerOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect
        .poll(async () =>
            innerOverlay.evaluate((element) => {
                const rect = element.getBoundingClientRect();

                return { width: Math.round(rect.width), height: Math.round(rect.height) };
            }),
        )
        .toEqual({ width: 1280, height: 720 });
});

test("nested modal and alert dialog close one layer at a time with Escape", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
            [data-hotwire-top-layer][popover]:is([data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"]) { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-backdrop"], [data-slot="alert-dialog-backdrop"] { position: absolute; inset: 0; }
            [data-slot="modal-positioner"], [data-slot="alert-dialog-panel"] { position: relative; z-index: 1; background: white; }
            .hidden { pointer-events: none; }
            .visible { pointer-events: auto; }
            .dialog-hidden { opacity: 0; transform: scale(.8); }
            .dialog-visible { opacity: 1; transform: scale(1); }
        </style>
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <button id="outer-close" data-action="modal#close">Close outer</button>
                    <div id="inner" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                            <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                            <div data-slot="modal-positioner" data-modal-target="dialog">
                                <button id="inner-close" data-action="modal#close">Close inner</button>
                                <div id="confirm" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                                    <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                                    <div data-slot="alert-dialog-overlay" data-alert-dialog-target="modal" data-state="closed" data-motion="none" data-action="click->alert-dialog#clickOutside" hidden inert>
                                        <div data-slot="alert-dialog-backdrop" data-alert-dialog-target="backdrop"></div>
                                        <div data-slot="alert-dialog-panel" data-alert-dialog-target="dialog">
                                            <button id="cancel" data-action="alert-dialog#cancel">Cancel</button>
                                            <button id="confirm-action" data-action="alert-dialog#confirm">Confirm</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    await page.locator("#open-outer").click();
    await page.locator("#open-inner").click();
    await page.locator("#delete").click();

    const outerOverlay = page.locator("#outer > [data-modal-target='modal']");
    const innerOverlay = page.locator("#inner > [data-modal-target='modal']");
    const alertOverlay = page.locator("#confirm [data-alert-dialog-target='modal']");

    await expect(alertOverlay).not.toHaveAttribute("hidden", "");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await page.evaluate(() => {
        const overlay = document.querySelector("#outer > [data-modal-target='modal']");
        const replacement = overlay.cloneNode(true);
        const overlays = [
            replacement,
            ...replacement.querySelectorAll('[data-modal-target="modal"], [data-alert-dialog-target="modal"]'),
        ];

        for (const element of overlays) {
            element.dataset.state = "closed";
            element.hidden = true;
            element.setAttribute("inert", "");
            element.removeAttribute("popover");
            element.removeAttribute("data-hotwire-top-layer");
            element.removeAttribute("data-hotwire-top-layer-popover");
        }

        replacement.querySelector("#outer-close").textContent = "Morphed outer close";
        window.Turbo.morphElements(overlay, replacement);
    });

    await expect(page.locator("#outer-close")).toHaveText("Morphed outer close");
    await expect(alertOverlay).toHaveAttribute("data-state", "open");
    await expect(innerOverlay).toHaveAttribute("data-state", "open");
    await expect(outerOverlay).toHaveAttribute("data-state", "open");
    await expect(alertOverlay).not.toHaveAttribute("hidden", "");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await outerOverlay.locator(':scope > [data-modal-target="backdrop"]').evaluate((element) => {
        element.replaceWith(element.cloneNode(true));
    });
    await page.waitForTimeout(0);

    await expect
        .poll(async () =>
            page.evaluate(() => {
                const target = document.elementFromPoint(window.innerWidth / 2, window.innerHeight / 2);

                return target?.closest('[data-slot="alert-dialog-overlay"]') !== null;
            }),
        )
        .toBe(true);

    await page.keyboard.press("Escape");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
    await expect(page.locator("#delete")).toBeFocused();
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.keyboard.press("Escape");
    await expect(innerOverlay).toHaveAttribute("hidden", "");
    await expect(page.locator("#open-inner")).toBeFocused();
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.keyboard.press("Escape");
    await expect(outerOverlay).toHaveAttribute("hidden", "");
    await expect(page.locator("#open-outer")).toBeFocused();
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
});

for (const identifier of ["drawer", "sheet"]) {
    test(`${identifier} and alert dialog close one layer at a time with Escape`, async ({ page }) => {
        await page.setContent(`
            <style>
                [hidden] { display: none !important; }
                [data-slot$="-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
                [data-hotwire-top-layer][popover][data-slot$="-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
                [data-slot$="-backdrop"] { position: absolute; inset: 0; }
                [data-slot="${identifier}-popup"], [data-slot="alert-dialog-panel"] { position: relative; z-index: 1; background: white; }
            </style>
            <div id="parent" data-controller="${identifier}" data-${identifier}-lock-scroll-class="overflow-hidden">
                <button id="open-parent" data-action="${identifier}#open">Open ${identifier}</button>
                <div data-slot="${identifier}-overlay" data-${identifier}-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-slot="${identifier}-backdrop" data-${identifier}-target="backdrop" data-action="click->${identifier}#clickOutside"></div>
                    <div data-slot="${identifier}-popup" data-${identifier}-target="dialog">
                        <button id="parent-first">First</button>
                        <div id="confirm" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                            <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                            <div data-slot="alert-dialog-overlay" data-alert-dialog-target="modal" data-state="closed" data-motion="none" data-action="click->alert-dialog#clickOutside" hidden inert>
                                <div data-slot="alert-dialog-backdrop" data-alert-dialog-target="backdrop"></div>
                                <div data-slot="alert-dialog-panel" data-alert-dialog-target="dialog">
                                    <button id="cancel" data-action="alert-dialog#cancel">Cancel</button>
                                    <button data-action="alert-dialog#confirm">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);

        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate((controllerIdentifier) => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register(
                controllerIdentifier,
                controllerIdentifier === "drawer" ? window.DrawerController : window.SheetController,
            );
            window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
        }, identifier);

        await page.locator("#open-parent").click();
        await page.locator("#delete").click();

        const parentOverlay = page.locator(`#parent > [data-${identifier}-target="modal"]`);
        const alertOverlay = page.locator('#confirm [data-alert-dialog-target="modal"]');

        await expect(parentOverlay).toHaveAttribute("data-state", "open");
        await expect(alertOverlay).toHaveAttribute("data-state", "open");
        await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);
        await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

        await page.keyboard.press("Escape");
        await expect(alertOverlay).toHaveAttribute("hidden", "");
        await expect(page.locator("#delete")).toBeFocused();
        await expect(parentOverlay).not.toHaveAttribute("hidden", "");
        await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

        await page.locator("#parent-first").focus();
        await page.keyboard.press("Shift+Tab");
        await expect(page.locator("#delete")).toBeFocused();

        await page.keyboard.press("Escape");
        await expect(parentOverlay).toHaveAttribute("hidden", "");
        await expect(page.locator("#open-parent")).toBeFocused();
        await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
    });
}

test("Escape during nested overlay entry closes the entering overlay", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            #confirm [data-alert-dialog-target="dialog"] { opacity: 0; transition: opacity 100ms linear; }
            #confirm [data-alert-dialog-target="modal"][data-state="open"] [data-alert-dialog-target="dialog"] { opacity: 1; }
        </style>
        <div id="parent" data-controller="sheet" data-sheet-lock-scroll-class="overflow-hidden">
            <button id="open-parent" data-action="sheet#open">Open sheet</button>
            <div data-sheet-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog">
                    <div id="confirm" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                        <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                        <div data-alert-dialog-target="modal" data-state="closed" data-motion="default" hidden inert>
                            <div data-alert-dialog-target="backdrop"></div>
                            <div data-alert-dialog-target="dialog">
                                <button data-action="alert-dialog#cancel">Cancel</button>
                                <button data-action="alert-dialog#confirm">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("sheet", window.SheetController);
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    const parentOverlay = page.locator('#parent > [data-sheet-target="modal"]');
    const alertOverlay = page.locator('#confirm [data-alert-dialog-target="modal"]');

    await page.locator("#open-parent").click();
    await expect(parentOverlay).toHaveAttribute("data-state", "open");

    const tabPrevented = await page.locator("#delete").evaluate((trigger) => {
        trigger.click();
        const tab = new KeyboardEvent("keydown", { key: "Tab", shiftKey: true, bubbles: true, cancelable: true });
        trigger.dispatchEvent(tab);
        trigger.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));

        return tab.defaultPrevented;
    });

    expect(tabPrevented).toBe(true);
    await expect(alertOverlay).toHaveAttribute("data-state", "closed");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
    await expect(parentOverlay).toHaveAttribute("data-state", "open");
    await expect(parentOverlay).not.toHaveAttribute("hidden", "");

    await page.locator("#delete").click();
    await expect(alertOverlay).toHaveAttribute("data-state", "open");
    await expect(alertOverlay).not.toHaveAttribute("data-presence", "entering");

    const exitTabPrevented = await page.evaluate(() => {
        document.activeElement.dispatchEvent(
            new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }),
        );
        const trigger = document.querySelector("#delete");
        const tab = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
        trigger.dispatchEvent(tab);

        return tab.defaultPrevented;
    });

    expect(exitTabPrevented).toBe(true);
    await expect(alertOverlay).toHaveAttribute("data-state", "closed");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
});

test("a modal nested in another modal animates after entering the top layer", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
            [data-slot="modal-overlay"][data-state="open"] { pointer-events: auto; }
            [data-hotwire-top-layer][popover][data-slot="modal-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-positioner"] { position: relative; z-index: 1; opacity: 0; transform: scale(.8); transition: opacity 400ms linear, transform 400ms linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; transform: scale(1); }
            [data-slot="modal-overlay"][data-motion="none"] > [data-slot="modal-positioner"] { transition: none !important; }
        </style>
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <div id="inner" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                            <div data-modal-target="backdrop"></div>
                            <div data-slot="modal-positioner" data-modal-target="dialog">
                                <button id="close-inner" data-action="modal#close">Close inner</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-outer").click();
    await page.locator("#open-inner").click();

    const innerOverlay = page.locator("#inner > [data-modal-target='modal']");
    const innerDialog = page.locator("#inner > [data-modal-target='modal'] > [data-modal-target='dialog']");

    await expect(innerOverlay).toHaveAttribute("data-state", "open");
    await expect
        .poll(async () =>
            innerDialog.evaluate((element) =>
                element.getAnimations().some((animation) => animation.playState === "running"),
            ),
        )
        .toBe(true);
    await expect
        .poll(async () => innerDialog.evaluate((element) => parseFloat(getComputedStyle(element).opacity)))
        .toBeLessThan(1);
});

test("rapid reopen cancels stale modal exit teardown", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; }
            [data-slot="modal-positioner"] { opacity: 0; transition: opacity 300ms linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; }
        </style>
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog"><button data-action="modal#close">Close</button></div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open").click();
    const modal = page.locator('[data-modal-target="modal"]');
    await expect(modal).toHaveAttribute("data-state", "open");
    await page.waitForTimeout(350);

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        controller.close();
        const modal = document.querySelector('[data-modal-target="modal"]');
        const replacement = modal.cloneNode(true);
        replacement.removeAttribute("data-presence");
        replacement.querySelector("button").textContent = "Morphed close";
        window.Turbo.morphElements(modal, replacement);
        window.presenceDuringMorph = modal.dataset.presence;
        controller.open({ currentTarget: document.querySelector("#open") });
    });
    await page.waitForTimeout(350);

    expect(await page.evaluate(() => window.presenceDuringMorph)).toBe("leaving");
    await expect(modal).toContainText("Morphed close");
    await expect(modal).toHaveAttribute("data-state", "open");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(modal).not.toHaveAttribute("inert", "");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);
});

test("Turbo cache synchronously closes a modal during motion", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; }
            [data-slot="modal-positioner"] { opacity: 0; transition: opacity 10s linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; }
        </style>
        <div
            data-controller="modal"
            data-action="turbo:before-cache@window->modal#closeForCache"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="open" data-action="modal#open">Open</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open").click();
    const modal = page.locator('[data-modal-target="modal"]');
    const dialog = page.locator('[data-modal-target="dialog"]');
    await expect(modal).toHaveAttribute("data-state", "open");
    await expect
        .poll(async () =>
            dialog.evaluate((element) =>
                element.getAnimations().some((animation) => (animation.currentTime ?? 0) > 100),
            ),
        )
        .toBe(true);

    const cachedState = await page.evaluate(() => {
        window.dispatchEvent(new CustomEvent("turbo:before-cache"));
        const element = document.querySelector('[data-modal-target="modal"]');

        return {
            state: element.dataset.state,
            hidden: element.hidden,
            inert: element.hasAttribute("inert"),
            topLayer: element.matches(":popover-open"),
            scrollLocked: document.body.classList.contains("overflow-hidden"),
        };
    });

    expect(cachedState).toEqual({
        state: "closed",
        hidden: true,
        inert: true,
        topLayer: false,
        scrollLocked: false,
    });
});

test("reduced motion closes a modal without waiting for CSS motion", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; }
            [data-slot="modal-positioner"] { opacity: 0; transition: opacity 10s linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; }
            @media (prefers-reduced-motion: reduce) { [data-slot="modal-positioner"] { transition: none !important; animation: none !important; } }
        </style>
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(async () => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        await new Promise((resolve) => setTimeout(resolve, 0));
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.open({ currentTarget: document.querySelector("#open") });
        await controller.close();
    });

    const modal = page.locator('[data-modal-target="modal"]');
    await expect(modal).toHaveAttribute("data-state", "closed");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect
        .poll(async () =>
            page.locator('[data-modal-target="dialog"]').evaluate((element) => element.getAnimations().length),
        )
        .toBe(0);
});

async function browserControllerScript(path) {
    // Inline helper modules alongside the controller — ES `import` is not valid
    // inside a regular <script>, so the harness concatenates the source instead.
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8")).replace(
        "export function isComposing",
        "function isComposing",
    );

    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export class FocusTrap", "class FocusTrap");

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function activateTopOverlay", "function activateTopOverlay")
        .replace("export function isTopOverlay", "function isTopOverlay")
        .replace("export function overlayPosition", "function overlayPosition");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8")).replace(
        "export function createTopLayer",
        "function createTopLayer",
    );

    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8")).replace(
        "export function createPresence",
        "function createPresence",
    );

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_stack\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

    const frameOverlay = (await readFile("resources/js/controllers/_frame_overlay.js", "utf8")).replace(
        "export function createFrameOverlay",
        "function createFrameOverlay",
    );
    const presentation = (await readFile("resources/js/controllers/_overlay_presentation.js", "utf8")).replace(
        "export function createOverlayPresentation",
        "function createOverlayPresentation",
    );
    const host = (await readFile("resources/js/controllers/_overlay_host.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_overlay_return\.js";\s*/, "")
        .replace('import("@hotwired/turbo")', "Promise.resolve({ StreamActions: window.Turbo?.StreamActions })")
        .replace("export function createOverlayHost", "function createOverlayHost")
        .replace("export function dismissOverlayInstance", "function dismissOverlayInstance");
    const overlayReturn = (await readFile("resources/js/controllers/_overlay_return.js", "utf8")).replace(
        /export function /g,
        "function ",
    );

    const source = (await readFile(path, "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_presentation\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_host\.js";\s*/, "")
        .replace("export default class ModalController extends Controller", "class ModalController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${composition}
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${presence}
        ${overlay}
        ${frameOverlay}
        ${presentation}
        ${overlayReturn}
        ${host}
        ${source}
        window.ModalController = ModalController;
    `;
}

async function browserOverlayControllerScript() {
    const base = await browserControllerScript("resources/js/controllers/modal_controller.js");
    const actionReplay = (await readFile("resources/js/controllers/_action_replay.js", "utf8")).replace(
        /^export /gm,
        "",
    );
    const alertDialog = (await readFile("resources/js/controllers/alert_dialog_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_action_replay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(
            "export default class AlertDialogController extends Controller",
            "class AlertDialogController extends Controller",
        );
    const drawer = (await readFile("resources/js/controllers/drawer_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_presentation\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_host\.js";\s*/, "")
        .replace(
            "export default class DrawerController extends Controller",
            "class DrawerController extends Controller",
        );
    const sheet = (await readFile("resources/js/controllers/sheet_controller.js", "utf8"))
        .replace('import DrawerController from "./drawer_controller.js";', "")
        .replace(
            "export default class SheetController extends DrawerController",
            "class SheetController extends DrawerController",
        );

    return `
        ${base.replace("window.ModalController = ModalController;", "")}
        ${actionReplay}
        ${alertDialog}
        ${drawer}
        ${sheet}
        window.ModalController = ModalController;
        window.AlertDialogController = AlertDialogController;
        window.DrawerController = DrawerController;
        window.SheetController = SheetController;
    `;
}

async function mountFrameOverlay(
    page,
    family,
    { beforeOverlay = "", loadingTemplate = "", overlayAttributes = "" } = {},
) {
    await page.setContent(`
        <div
            id="${family}-shell"
            data-controller="${family}"
            data-${family}-lock-scroll-class="overflow-hidden"
        >
            ${beforeOverlay}
            <div
                data-hotwire-overlay-labels
                data-slot="${family}-overlay"
                data-${family}-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                ${overlayAttributes}
                hidden inert
            >
                <div data-${family}-target="backdrop"></div>
                <div data-${family}-target="dialog">
                    <turbo-frame id="${family}-frame" data-${family}-target="dynamicContent"></turbo-frame>
                </div>
            </div>
            ${loadingTemplate === "" ? "" : `<template data-${family}-target="loadingTemplate">${loadingTemplate}</template>`}
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("sheet", window.SheetController);
        window.StimulusApplication.register("drawer", window.DrawerController);
    });
}
