import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

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
