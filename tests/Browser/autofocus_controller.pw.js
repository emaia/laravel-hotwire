import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { execFile } from "node:child_process";
import { createServer } from "node:http";
import { promisify } from "node:util";

const run = promisify(execFile);
let richTextBundle;

test("an invalid email receives focus after Enter replaces its Turbo Frame", async ({ page }) => {
    let invalid = false;
    await page.route("http://focus.test/**", async (route) => {
        if (route.request().method() === "POST") {
            invalid = true;
        }
        const frame = `<turbo-frame id="customer"><form action="/customer" method="post" novalidate
            data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
            <label>Name <input name="name" value="Ada"></label>
            <label>Email <input name="email" type="email" value="invalid" ${invalid ? 'aria-invalid="true"' : ""}></label>
            <button>Save</button>
        </form></turbo-frame>`;
        return route.fulfill({
            status: invalid ? 422 : 200,
            contentType: "text/html",
            body: `<!doctype html><html><body>${frame}</body></html>`,
        });
    });
    await page.goto("http://focus.test/customer");
    await startAutofocus(page);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    const email = page.getByRole("textbox", { name: "Email", exact: true });

    await email.press("Enter");

    await expect(email).toHaveAttribute("aria-invalid", "true");
    await expect(email).toBeFocused();
    await page.keyboard.type("x");
    await expect(email).toHaveValue(/x/);
});

test("first-invalid skips ineligible errors and focuses a composite field's interactive control", async ({ page }) => {
    await page.setContent(`<form id="external"></form><form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
        <input aria-invalid="false">
        <div aria-invalid="true">Error wrapper</div>
        <input aria-invalid="true" type="hidden">
        <input aria-invalid="true" disabled>
        <button aria-invalid="true" aria-disabled="true">Disabled action</button>
        <input aria-invalid="true" form="external">
        <fieldset disabled><input aria-invalid="true"></fieldset>
        <div hidden><input aria-invalid="true"></div>
        <div inert><input aria-invalid="true"></div>
        <div aria-hidden="true"><input aria-invalid="true"></div>
        <input aria-invalid="true" style="display:none">
        <input aria-invalid="true" style="visibility:hidden">
        <input aria-invalid="true" tabindex="-1">
        ${richTextMarkup("hidden-editor").replace('data-slot="rich-text"', 'data-slot="rich-text" hidden')}
        <select aria-invalid="true" hidden tabindex="-1"><option>Transport</option></select>
        <button type="button" aria-invalid="true" aria-haspopup="listbox">Choose customer</button>
        <input aria-invalid="true" name="later">
    </form>`);
    await startAutofocus(page);

    await expect(page.getByRole("button", { name: "Choose customer" })).toBeFocused();
});

test("first-invalid leaves valid forms alone instead of applying their initial autofocus", async ({ page }) => {
    await page.setContent(`<turbo-frame id="editor"><form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
        <input id="valid" aria-invalid="false">
    </form></turbo-frame>`);
    await startAutofocus(page);

    await page.locator("#valid").evaluate((element) => element.setAttribute("autofocus", ""));
    await page
        .locator("#editor")
        .evaluate((frame) => frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true })));

    await expect(page.locator("#valid")).not.toBeFocused();
});

for (const active of ["#inside", "#outside", "#other-dialog"]) {
    test(`first-invalid preserves established focus on ${active}`, async ({ page }) => {
        await page.setContent(`<input id="outside">
            <div role="dialog" tabindex="-1" id="other-dialog"></div>
            <turbo-frame id="editor"><form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
                <input id="inside"><input aria-invalid="true" name="email">
            </form></turbo-frame>`);
        await page.locator(active).focus();

        await startAutofocus(page);
        await page
            .locator("#editor")
            .evaluate((frame) => frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true })));

        await expect(page.locator(active)).toBeFocused();
    });
}

test("first-invalid recovers focus from its own dialog but ignores unrelated Frame loads", async ({ page }) => {
    await page.setContent(`<div role="dialog" tabindex="-1" id="dialog">
        <turbo-frame id="editor"><form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
            <input id="email">
        </form></turbo-frame>
    </div><turbo-frame id="other"></turbo-frame>`);
    await page.locator("#dialog").focus();
    await startAutofocus(page);
    await page.locator("#email").evaluate((element) => element.setAttribute("aria-invalid", "true"));

    await page
        .locator("#other")
        .evaluate((frame) => frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true })));
    await expect(page.locator("#dialog")).toBeFocused();
    await page
        .locator("#editor")
        .evaluate((frame) => frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true })));

    await expect(page.locator("#email")).toBeFocused();
});

for (const laterError of [false, true]) {
    test(`a delayed invalid Rich Text receives focus${laterError ? " before a later invalid field" : " without another error"}`, async ({
        page,
    }) => {
        await page.setContent(`<form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
            ${richTextMarkup("content")}
            ${laterError ? '<input id="later" aria-invalid="true">' : ""}
        </form>`);
        await startRichTextAutofocus(page);
        await page.evaluate(() => {
            window.focusedIds = [];
            document.addEventListener("focusin", (event) => window.focusedIds.push(event.target.id));
        });
        expect(await page.evaluate(() => document.activeElement === document.body)).toBe(true);

        await page.evaluate(() => window.registerRichText());

        const editor = page.locator(".ProseMirror");
        await expect(editor).toHaveAttribute("aria-invalid", "true");
        await expect(editor).toBeFocused();
        await page.keyboard.type("Corrected content");
        await expect(editor).toHaveText("Corrected content");
        expect(await page.evaluate(() => window.focusedIds)).not.toContain("later");
    });
}

test("an editor that becomes ready out of order cannot overtake the first invalid editor", async ({ page }) => {
    await page.setContent(`<form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
        ${richTextMarkup("first", "first-editor")}
        ${richTextMarkup("second", "second-editor")}
    </form>`);
    await startRichTextAutofocus(page);

    await page.evaluate(() => window.registerRichText("second-editor"));
    await expect(page.locator("#second .ProseMirror")).toBeVisible();
    expect(await page.evaluate(() => document.activeElement === document.body)).toBe(true);
    await page.evaluate(() => window.registerRichText("first-editor"));

    await expect(page.locator("#first .ProseMirror")).toBeFocused();
});

test("a native error before an invalid Rich Text keeps its earlier place in focus order", async ({ page }) => {
    await page.setContent(`<form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
        <input id="earlier" aria-invalid="true">${richTextMarkup("content")}
    </form>`);
    await startRichTextAutofocus(page);
    await expect(page.locator("#earlier")).toBeFocused();

    await page.evaluate(() => window.registerRichText());
    await expect(page.locator(".ProseMirror")).toHaveAttribute("aria-invalid", "true");

    await expect(page.locator("#earlier")).toBeFocused();
});

for (const chosen of ["#later", "#outside"]) {
    test(`Rich Text readiness preserves focus chosen on ${chosen} while loading`, async ({ page }) => {
        await page.setContent(`<input id="outside"><form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
            ${richTextMarkup("content")}
            <input id="later" aria-invalid="true">
        </form>`);
        await startRichTextAutofocus(page);
        await page.locator(chosen).focus();

        await page.evaluate(() => window.registerRichText());
        await expect(page.locator(".ProseMirror")).toBeVisible();

        await expect(page.locator(chosen)).toBeFocused();
    });
}

test("Rich Text recovery during an in-place morph does not start a new error-focus attempt", async ({ page }) => {
    const formMarkup = (
        invalid,
    ) => `<form id="editor-form" data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
        ${invalid ? richTextMarkup("content") : richTextMarkup("content").replaceAll(' aria-invalid="true"', "")}
    </form>`;
    await page.setContent(formMarkup(false));
    await startRichTextAutofocus(page);
    await page.evaluate(() => window.registerRichText());
    await expect(page.locator(".ProseMirror")).toBeVisible();
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

    await page.evaluate((html) => {
        const replacement = document.createElement("template");
        replacement.innerHTML = html;
        window.Turbo.morphElements(document.getElementById("editor-form"), replacement.content.firstElementChild);
    }, formMarkup(true));

    await expect(page.locator(".ProseMirror")).toHaveAttribute("aria-invalid", "true");
    expect(await page.evaluate(() => document.activeElement === document.body)).toBe(true);
});

test("a ready editor in another form does not focus a waiting form, and disconnect removes the ready listener", async ({
    page,
}) => {
    await page.setContent(`<form data-controller="autofocus" data-autofocus-strategy-value="first-invalid">
        ${richTextMarkup("waiting", "waiting-editor")}
        <input id="later" aria-invalid="true">
    </form><form>${richTextMarkup("other", "other-editor")}</form>`);
    await startRichTextAutofocus(page);

    await page.evaluate(() => window.registerRichText("other-editor"));
    await expect(page.locator("#other .ProseMirror")).toBeVisible();
    expect(await page.evaluate(() => document.activeElement === document.body)).toBe(true);
    await page.evaluate(() => {
        window.focusApplication.unload("autofocus");
        window.registerRichText("waiting-editor");
    });

    await expect(page.locator("#waiting .ProseMirror")).toBeVisible();
    expect(await page.evaluate(() => document.activeElement === document.body)).toBe(true);
});

for (const submitWith of ["Enter", "click"]) {
    test(`same-page validation morph preserves the form without re-running error focus after ${submitWith}`, async ({
        page,
    }) => {
        let invalid = false;
        const server = createServer((request, response) => {
            if (request.method === "POST") {
                invalid = true;
                response.writeHead(303, { location: "/customer" });
                response.end();
                return;
            }
            response.writeHead(200, { "content-type": "text/html", "cache-control": "no-store" });
            response.end(`<!doctype html><html><head><meta name="turbo-refresh-method" content="morph"></head><body>
            <form id="customer" data-controller="autofocus" data-autofocus-strategy-value="first-invalid" action="/customer" method="post" novalidate>
                <label>Email <input id="email" name="email" value="invalid" ${invalid ? 'aria-invalid="true"' : ""}></label>
                <button id="save">Save</button>
            </form></body></html>`);
        });
        await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
        try {
            await page.goto(`http://127.0.0.1:${server.address().port}/customer`);
            await startAutofocus(page);
            await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
            await page.evaluate(() => {
                window.originalForm = document.getElementById("customer");
                window.originalController = window.application.controllers[0];
                window.frameLoads = 0;
                document.addEventListener("turbo:frame-load", () => window.frameLoads++);
            });

            if (submitWith === "Enter") {
                await page.locator("#email").press("Enter");
            } else {
                await page.getByRole("button", { name: "Save" }).click();
            }

            await expect(page.locator("#email")).toHaveAttribute("aria-invalid", "true");
            await expect(page.locator("#save")).toBeEnabled();
            expect(
                await page.evaluate(() => ({
                    activeTag: document.activeElement.tagName,
                    activeId: document.activeElement.id,
                    formPreserved: document.getElementById("customer") === window.originalForm,
                    controllerPreserved: window.application.controllers[0] === window.originalController,
                    frameLoads: window.frameLoads,
                })),
            ).toEqual({
                activeTag: submitWith === "Enter" ? "INPUT" : "BODY",
                activeId: submitWith === "Enter" ? "email" : "",
                formPreserved: true,
                controllerPreserved: true,
                frameLoads: 0,
            });
        } finally {
            await new Promise((resolve) => server.close(resolve));
        }
    });
}

function richTextMarkup(id, identifier = "rich-text") {
    return `<div id="${id}" data-slot="rich-text" data-controller="${identifier}" aria-invalid="true">
        <textarea name="${id}" data-${identifier}-target="input" hidden aria-invalid="true"></textarea>
        <div data-slot="rich-text-editor" data-${identifier}-target="editor"></div>
    </div>`;
}

async function startRichTextAutofocus(page) {
    richTextBundle ??= run(
        "bun",
        ["build", "tests/Browser/fixtures/rich_text_autofocus.js", "--target=browser", "--format=iife"],
        { maxBuffer: 10 * 1024 * 1024 },
    );
    await page.addScriptTag({ content: (await richTextBundle).stdout });
    await expect.poll(() => page.evaluate(() => window.focusApplication.controllers.length)).toBe(1);
}

async function startAutofocus(page) {
    const events = (await readFile("resources/js/controllers/_frame_events.js", "utf8")).replace(/^export /gm, "");
    const controller = (await readFile("resources/js/controllers/autofocus_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace(/import \{[^}]*\} from "\.\/_frame_events\.js";\s*/, "")
        .replace("export default class extends Controller", "class AutofocusController extends Controller");
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({
        content: `${events}\n${controller}\nwindow.application = window.Stimulus.Application.start(); window.application.register("autofocus", AutofocusController);`,
    });
    await expect.poll(() => page.evaluate(() => window.application.controllers.length)).toBe(1);
}
