import { expect, test } from "@playwright/test";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";

test("preserves active frame state during an external morph and releases it afterward", async ({ page }) => {
    let revision = 1;
    const server = createServer((request, response) => {
        const url = new URL(request.url, "http://localhost");

        if (url.pathname.startsWith("/revision/")) {
            revision = Number(url.pathname.split("/").at(-1));
            response.writeHead(204);
            response.end();

            return;
        }

        if (url.pathname === "/host") {
            html(response, hostPage(revision));

            return;
        }

        response.writeHead(404);
        response.end("Not found");
    });
    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    const origin = `http://127.0.0.1:${server.address().port}`;

    try {
        await page.goto(`${origin}/host`);
        await loadRuntime(page);

        const frame = page.locator("turbo-frame#editor-frame");
        const form = frame.locator("form");
        const draft = frame.locator("textarea");

        await expect(frame).toHaveAttribute("data-turbo-permanent", "");
        await draft.fill("Unsaved local draft");
        await draft.evaluate((element) => element.setSelectionRange(7, 7));
        await frame.evaluate((element) => { element.dataset.hostToken = "original"; });
        await form.evaluate((element) => {
            window.initialMorphGuard = window.StimulusApplication
                .getControllerForElementAndIdentifier(element, "turbo--morph-guard");
        });

        await refreshFromServer(page, 2);

        await expect(page.locator("#outside-revision")).toHaveText("Outside revision 2");
        await expect(frame.locator("#frame-revision")).toHaveText("Frame revision 1");
        await expect(frame).toHaveAttribute("data-host-token", "original");
        await expect(draft).toHaveValue("Unsaved local draft");
        await expect(draft).toBeFocused();
        expect(await draft.evaluate((element) => element.selectionStart)).toBe(7);
        expect(await form.evaluate((element) => window.initialMorphGuard === window.StimulusApplication
            .getControllerForElementAndIdentifier(element, "turbo--morph-guard"))).toBe(true);

        await form.evaluate((element) => element.removeAttribute("data-controller"));
        await expect(frame).not.toHaveAttribute("data-turbo-permanent");

        await refreshFromServer(page, 3);

        await expect(page.locator("#outside-revision")).toHaveText("Outside revision 3");
        await expect(frame.locator("#server-replacement")).toHaveText("Server replacement 3");
        await expect(frame.locator("form")).toHaveCount(0);
        await expect(frame).not.toHaveAttribute("data-turbo-permanent");
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

test("keeps the transient marker out of cache and reacquires it after restoration", async ({ page }) => {
    const server = createServer((request, response) => {
        const url = new URL(request.url, "http://localhost");

        if (url.pathname === "/host") {
            html(response, hostPage(1));

            return;
        }

        if (url.pathname === "/other") {
            html(response, `
                <!doctype html>
                <html>
                    <body><h1>Other page</h1></body>
                </html>
            `);

            return;
        }

        response.writeHead(404);
        response.end("Not found");
    });
    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    const origin = `http://127.0.0.1:${server.address().port}`;

    try {
        await page.goto(`${origin}/host`);
        await loadRuntime(page);

        const frame = page.locator("turbo-frame#editor-frame");
        await expect(frame).toHaveAttribute("data-turbo-permanent", "");

        await page.evaluate(() => window.Turbo.session.view.cacheSnapshot());
        await expect(frame).toHaveAttribute("data-turbo-permanent", "");
        await page.evaluate(() => {
            const meta = document.createElement("meta");
            meta.name = "turbo-cache-control";
            meta.content = "no-cache";
            document.head.append(meta);
        });

        await page.locator("#away").click();
        await expect(page.getByRole("heading", { name: "Other page" })).toBeVisible();
        expect(await page.evaluate(() => {
            const snapshots = Object.values(window.Turbo.session.view.snapshotCache.snapshots);
            const cachedFrame = snapshots
                .map((snapshot) => snapshot.element.querySelector("#editor-frame"))
                .find(Boolean);

            return cachedFrame?.hasAttribute("data-turbo-permanent") ?? null;
        })).toBe(false);

        await page.goBack();
        await expect(frame).toHaveAttribute("data-turbo-permanent", "");
        const form = frame.locator("form[data-controller~='turbo--morph-guard']");
        await expect(form).toHaveCount(1);

        await form.evaluate((element) => element.removeAttribute("data-controller"));
        await expect(frame).not.toHaveAttribute("data-turbo-permanent");
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

async function refreshFromServer(page, revision) {
    await page.evaluate(async (nextRevision) => {
        await fetch(`/revision/${nextRevision}`, { method: "POST" });
        window.Turbo.renderStreamMessage('<turbo-stream action="refresh" method="morph"></turbo-stream>');
    }, revision);
}

function html(response, body) {
    response.writeHead(200, {
        "cache-control": "no-store",
        "content-type": "text/html; charset=utf-8",
    });
    response.end(body);
}

function hostPage(revision) {
    const frameContent = revision < 3
        ? `
            <form data-controller="turbo--morph-guard">
                <p id="frame-revision">Frame revision ${revision}</p>
                <label>Draft <textarea>Server draft ${revision}</textarea></label>
            </form>
        `
        : '<p id="server-replacement">Server replacement 3</p>';

    return `
        <!doctype html>
        <html>
            <head>
                <meta name="turbo-refresh-method" content="morph">
            </head>
            <body>
                <a id="away" href="/other">Leave</a>
                <p id="outside-revision">Outside revision ${revision}</p>
                <turbo-frame id="editor-frame">${frameContent}</turbo-frame>
            </body>
        </html>
    `;
}

async function loadRuntime(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("turbo--morph-guard", window.MorphGuardController);
    });
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/turbo/morph_guard_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class MorphGuardController extends Controller")
        .concat("\nwindow.MorphGuardController = MorphGuardController;\n");
}
