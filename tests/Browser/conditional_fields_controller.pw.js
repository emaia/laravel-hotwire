import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("unless switches actions and excludes hidden controls from submission", async ({ page }) => {
    await page.setContent(`
        <form data-controller="conditional-fields">
            <label><input type="radio" name="status" value="accepted">Accepted</label>
            <label><input type="radio" name="status" value="completed">Completed</label>
            <fieldset id="save" data-conditional-fields-target="dependent" data-unless-status="completed">
                <input name="draft" value="Keep working">
                <button name="action" value="save">Save</button>
            </fieldset>
            <fieldset id="complete" data-conditional-fields-target="dependent" data-when-status="completed" hidden disabled>
                <input name="resolution" required>
                <button name="action" value="complete">Complete</button>
            </fieldset>
        </form>
    `);
    await installController(page);
    await page.evaluate(() => {
        document.querySelector("form").addEventListener("submit", (event) => {
            event.preventDefault();
            window.submittedFields = Object.fromEntries(new FormData(event.target, event.submitter));
        });
    });

    await expect(page.getByRole("button", { name: "Save", exact: true })).toBeVisible();
    await expect(page.getByRole("button", { name: "Complete", exact: true })).toBeHidden();
    await expect(page.locator('[name="resolution"]')).toBeDisabled();
    expect(await page.locator("form").evaluate((form) => form.checkValidity())).toBe(true);

    await page.getByRole("button", { name: "Save", exact: true }).click();
    expect(await page.evaluate(() => window.submittedFields)).toEqual({ draft: "Keep working", action: "save" });

    await page.getByRole("radio", { name: "Completed", exact: true }).check();
    await expect(page.getByRole("button", { name: "Save", exact: true })).toBeHidden();
    await expect(page.locator('[name="draft"]')).toBeDisabled();
    await expect(page.getByRole("button", { name: "Complete", exact: true })).toBeVisible();
    await expect(page.locator('[name="resolution"]')).toBeEnabled();
    expect(await page.locator("form").evaluate((form) => form.checkValidity())).toBe(false);

    await page.locator('[name="resolution"]').fill("Resolved");
    await page.getByRole("button", { name: "Complete", exact: true }).click();
    expect(await page.evaluate(() => window.submittedFields)).toEqual({
        status: "completed",
        resolution: "Resolved",
        action: "complete",
    });

    await page.getByRole("radio", { name: "Accepted", exact: true }).check();
    await expect(page.getByRole("button", { name: "Save", exact: true })).toBeVisible();
    await expect(page.locator('[name="resolution"]')).toBeDisabled();
});

test("when and unless preserve disabled states and stay scoped", async ({ page }) => {
    await page.setContent(`
        <section id="first" data-controller="conditional-fields">
            <input name="plan" value="pro">
            <label><input type="checkbox" name="archived" checked>Archived</label>
            <div data-conditional-fields-target="dependent" data-when-plan="pro" data-unless-archived=":checked" hidden>
                <button>Continue</button>
                <button disabled>Restricted</button>
            </div>
        </section>
        <section id="second" data-controller="conditional-fields">
            <input name="archived" type="checkbox">
            <div data-conditional-fields-target="dependent" data-unless-archived=":checked">
                <button>Independent</button>
            </div>
        </section>
    `);
    await installController(page);

    await expect(page.getByRole("button", { name: "Independent" })).toBeEnabled();
    await expect(page.getByRole("button", { name: "Continue" })).toBeHidden();

    await page.getByRole("checkbox", { name: "Archived", exact: true }).uncheck();

    await expect(page.getByRole("button", { name: "Continue" })).toBeVisible();
    await expect(page.getByRole("button", { name: "Continue" })).toBeEnabled();
    await expect(page.getByRole("button", { name: "Restricted" })).toBeDisabled();
    await expect(page.getByRole("button", { name: "Independent" })).toBeEnabled();
});

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    const source = (await readFile("resources/js/controllers/conditional_fields_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace(
            "export default class extends Controller",
            "window.ConditionalFieldsController = class extends Controller",
        );
    await page.addScriptTag({ content: source });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("conditional-fields", window.ConditionalFieldsController);
    });
    await expect
        .poll(() => page.evaluate(() => window.StimulusApplication.controllers.length))
        .toBe(await page.locator('[data-controller="conditional-fields"]').count());
}
