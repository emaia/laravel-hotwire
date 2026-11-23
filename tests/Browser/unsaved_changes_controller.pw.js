import { expect, test as base } from "@playwright/test";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";

const test = base.extend({
    origin: async ({}, use) => {
        const server = createServer((request, response) => {
            const url = new URL(request.url, "http://localhost");
            const mode = url.searchParams.get("mode") || "stream";
            response.setHeader("content-type", "text/html");
            if (url.pathname === "/save") {
                if (mode === "network") {
                    request.socket.destroy();
                } else if (mode === "success") {
                    response.writeHead(303, { location: "/saved" });
                    response.end();
                } else if (mode === "frame-morph") {
                    response.writeHead(422);
                    response.end(formHTML(mode, true));
                } else {
                    response.writeHead(422, { "content-type": "text/vnd.turbo-stream.html" });
                    response.end(
                        '<turbo-stream action="update" target="errors"><template><p role="alert">Title was rejected.</p></template></turbo-stream>',
                    );
                }
                return;
            }
            response.end(
                url.pathname === "/edit"
                    ? `<!doctype html><html><body>
                    <a href="/menu" data-turbo-prefetch="false">Menu</a>
                    ${formHTML(mode)}
                   </body></html>`
                    : "<!doctype html><html><body><h1>Saved</h1></body></html>",
            );
        });
        await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
        try {
            await use(`http://127.0.0.1:${server.address().port}`);
        } finally {
            await new Promise((resolve) => server.close(resolve));
        }
    },
});

test("keeps warning after browser validation blocks a submit button click", async ({ page, origin }) => {
    await mount(page, origin);
    await page.locator('[name="title"]').fill("Unsaved draft");
    await page.getByRole("button", { name: "Save" }).click();

    expect(await page.locator("form").evaluate((form) => form.checkValidity())).toBe(false);
    expect(await page.evaluate(() => window.submissionsStarted)).toBe(0);
    await expectNavigationWarning(page);
});

for (const mode of ["stream", "frame-morph", "network"]) {
    test(`rearms warning after ${mode} failure without replacing the form`, async ({ page, origin }) => {
        await mount(page, origin, mode);
        await fillValidForm(page);
        await page.getByRole("button", { name: "Save" }).click();

        await expect.poll(() => page.evaluate(() => window.submissionResult)).toBe("failed");
        if (mode !== "network") {
            await expect(page.locator('[role="alert"]')).toHaveText("Title was rejected.");
        }
        expect(await page.evaluate(() => document.querySelector("form") === window.originalForm)).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    window.StimulusApplication.getControllerForElementAndIdentifier(
                        document.querySelector("form"),
                        "unsaved-changes",
                    ) === window.originalController,
            ),
        ).toBe(true);
        expect(await page.evaluate(() => window.originalController.allow)).toBe(false);
        expect(
            await page.evaluate(() => window.originalController.formChanges(window.originalForm).length),
        ).toBeGreaterThan(0);
        await expectNavigationWarning(page);
    });
}

for (const method of ["button", "enter", "requestSubmit"]) {
    test(`allows successful Turbo navigation after submission via ${method}`, async ({ page, origin }) => {
        await mount(page, origin, "success");
        await fillValidForm(page);
        const dialogs = [];
        page.on("dialog", async (dialog) => {
            dialogs.push(dialog.message());
            await dialog.dismiss();
        });

        if (method === "button") {
            await page.getByRole("button", { name: "Save" }).click();
        } else if (method === "enter") {
            await page.locator('[name="required"]').press("Enter");
        } else {
            await page.locator("form").evaluate((form) => form.requestSubmit());
        }

        await expect(page).toHaveURL(`${origin}/saved`);
        await expect(page.getByRole("heading")).toHaveText("Saved");
        expect(dialogs).toEqual([]);
    });
}

async function mount(page, origin, mode = "stream") {
    await page.goto(`${origin}/edit?mode=${mode}`);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    const source = (await readFile("resources/js/controllers/unsaved_changes_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace(
            "export default class extends Controller",
            "window.UnsavedChangesController = class extends Controller",
        );
    await page.addScriptTag({ content: source });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("unsaved-changes", window.UnsavedChangesController);
        window.originalForm = document.querySelector("form");
        window.submissionsStarted = 0;
        document.addEventListener("turbo:submit-start", () => window.submissionsStarted++);
        document.addEventListener("turbo:submit-end", (event) => {
            window.submissionResult = event.detail.success ? "success" : "failed";
        });
        document.addEventListener("turbo:before-frame-render", (event) => {
            // Exercise a real morph that retains the form and its controller,
            // rather than hiding the bug behind disconnect/connect.
            event.detail.render = (current, incoming) => window.Turbo.morphChildren(current, incoming);
        });
    });
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await expect
        .poll(() =>
            page.evaluate(() => {
                window.originalController = window.StimulusApplication.getControllerForElementAndIdentifier(
                    window.originalForm,
                    "unsaved-changes",
                );
                return Boolean(window.originalController);
            }),
        )
        .toBe(true);
}

function formHTML(mode, errors = false) {
    return `<turbo-frame id="editor">
        <form id="edit-form" data-controller="unsaved-changes" method="post" action="/save?mode=${mode}"${mode === "success" ? ' data-turbo-frame="_top"' : ""}>
            <div id="fields" data-turbo-permanent>
                <input name="title" value="Original">
                <input name="required" required>
            </div>
            <div id="errors">${errors ? '<p role="alert">Title was rejected.</p>' : ""}</div>
            <button type="submit">Save</button>
        </form>
    </turbo-frame>`;
}

async function fillValidForm(page) {
    await page.locator('[name="title"]').fill("Unsaved draft");
    await page.locator('[name="required"]').fill("Present");
}

async function expectNavigationWarning(page) {
    const currentURL = page.url();
    const dialog = page.waitForEvent("dialog", { timeout: 5000 });
    const navigation = page.getByRole("link", { name: "Menu" }).click();
    const warning = await dialog;
    expect(warning.type()).toBe("confirm");
    await warning.dismiss();
    await navigation;
    await expect(page).toHaveURL(currentURL);
}
