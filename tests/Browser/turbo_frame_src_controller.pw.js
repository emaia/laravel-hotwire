import { expect, test } from "@playwright/test";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";

test("keeps a lazy frame form open on its source URL after validation fails", async ({ page }) => {
    let showErrors = false;
    const server = createServer((request, response) => {
        const url = new URL(request.url, "http://localhost");

        if (request.method === "POST" && url.pathname === "/tasks") {
            const frameSource = request.headers["x-turbo-frame-src"];
            showErrors = true;
            response.writeHead(303, {
                location: typeof frameSource === "string" ? frameSource : "/missing-frame-source",
            });
            response.end();

            return;
        }

        if (url.pathname === "/tasks/create") {
            html(response, frameResponse(showErrors));

            return;
        }

        if (url.pathname === "/host") {
            html(response, `
                <!doctype html>
                <html>
                    <body>
                        <h1>Tasks</h1>
                        <turbo-frame
                            id="modal"
                            src="/tasks/create?status=in_progress"
                            loading="lazy"
                            style="display: block; min-height: 4rem"
                        >Loading...</turbo-frame>
                    </body>
                </html>
            `);

            return;
        }

        response.writeHead(404);
        response.end("Not found");
    });
    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    const address = server.address();
    const origin = `http://127.0.0.1:${address.port}`;

    try {
        await page.goto(`${origin}/host`);
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("turbo--frame-src", window.FrameSrcController);
        });
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

        const frame = page.locator("turbo-frame#modal");
        await expect(frame.locator("form")).toBeVisible();

        const submission = page.waitForRequest((request) =>
            request.method() === "POST" && new URL(request.url()).pathname === "/tasks"
        );
        await frame.getByRole("button", { name: "Create task" }).click();
        const submissionRequest = await submission;

        expect(submissionRequest.headers()["x-turbo-frame-src"])
            .toBe(`${origin}/tasks/create?status=in_progress`);

        await expect(frame.locator("[role=alert]")).toHaveText("The title field is required.");
        await expect(frame.locator("form")).toBeVisible();
        await expect(page.getByRole("heading", { name: "Tasks" })).toBeVisible();
        expect(page.url()).toBe(`${origin}/host`);
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

function frameResponse(showErrors) {
    return `
        <!doctype html>
        <html>
            <body>
                <turbo-frame id="modal">
                    <form
                        data-controller="turbo--frame-src"
                        method="post"
                        action="/tasks?status=in_progress"
                    >
                        <label>Title <input name="title"></label>
                        ${showErrors ? '<p role="alert">The title field is required.</p>' : ""}
                        <button type="submit">Create task</button>
                    </form>
                </turbo-frame>
            </body>
        </html>
    `;
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/turbo/frame_src_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class FrameSrcController extends Controller")
        .concat("\nwindow.FrameSrcController = FrameSrcController;\n");
}
