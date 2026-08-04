import { expect, test } from "@playwright/test";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";

test("navigating inside an open modal host runs one View Transition on the persistent frame", async ({ page }) => {
    const server = createServer((request, response) => {
        const url = new URL(request.url, "http://localhost");

        if (url.pathname === "/tasks/1") {
            html(response, framePayload(`
                <h2>Task detail</h2>
                <a id="edit" href="/tasks/1/edit">Go to edit</a>
            `));

            return;
        }

        if (url.pathname === "/tasks/1/edit") {
            html(response, framePayload("<h2>Edit form</h2>"));

            return;
        }

        if (url.pathname === "/host") {
            html(response, `
                <!doctype html>
                <html>
                    <body>
                        <a id="open" href="/tasks/1" data-turbo-frame="modal">Open task</a>

                        <div data-slot="modal">
                            <turbo-frame
                                id="modal"
                                data-controller="turbo--view-transition"
                                data-turbo--view-transition-skip-initial-value="true"
                                style="display: block; min-height: 2rem"
                            ></turbo-frame>
                        </div>
                    </body>
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
        // Count transitions before any page script runs, delegating to the real
        // implementation so Turbo's renderer is exercised end to end.
        await page.addInitScript(() => {
            window.viewTransitionCount = 0;
            const native = document.startViewTransition?.bind(document);

            document.startViewTransition = (callback) => {
                window.viewTransitionCount++;

                if (native) return native(callback);

                callback();

                return {
                    finished: Promise.resolve(),
                    ready: Promise.resolve(),
                    updateCallbackDone: Promise.resolve(),
                };
            };
        });

        await page.goto(`${origin}/host`);
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("turbo--view-transition", window.ViewTransitionController);
        });
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

        const frame = page.locator("turbo-frame#modal");
        await expect.poll(async () => frame.evaluate((element) =>
            Boolean(window.StimulusApplication.getControllerForElementAndIdentifier(element, "turbo--view-transition")),
        )).toBe(true);

        // Tag the host node so a replaced element is distinguishable from a preserved one.
        await frame.evaluate((element) => { element.dataset.hostToken = "original"; });

        await page.locator("#open").click();
        await expect(frame).toContainText("Task detail");

        // Filling the empty host is the overlay's opening; it keeps the overlay motion.
        expect(await page.evaluate(() => window.viewTransitionCount)).toBe(0);

        await page.locator("#edit").click();
        await expect(frame).toContainText("Edit form");
        await expect(page.locator("#edit")).toHaveCount(0);

        expect(await page.evaluate(() => window.viewTransitionCount)).toBe(1);
        await expect(frame).toHaveAttribute("data-host-token", "original");
        await expect(frame).toHaveAttribute("data-controller", "turbo--view-transition");
        expect(await page.locator("turbo-frame#modal").count()).toBe(1);
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

function html(response, body) {
    response.writeHead(200, {
        "cache-control": "no-store",
        "content-type": "text/html; charset=utf-8",
    });
    response.end(body);
}

function framePayload(content) {
    return `
        <!doctype html>
        <html>
            <body>
                <turbo-frame id="modal">${content}</turbo-frame>
            </body>
        </html>
    `;
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/turbo/view_transition_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class ViewTransitionController extends Controller")
        .concat("\nwindow.ViewTransitionController = ViewTransitionController;\n");
}
