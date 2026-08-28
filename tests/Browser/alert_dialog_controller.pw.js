import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("a replaced external-form trigger submits once with its captured action", async ({ page }) => {
    await page.setContent(`
        <form id="item-form" action="/items" method="get">
            <input name="query" value="current">
        </form>

        <div id="confirmation" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div
                id="trigger-zone"
                data-controller="probe"
                data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept click->probe#track"
            >
                <button
                    id="delete-item"
                    type="submit"
                    form="item-form"
                    formaction="/items/42"
                    formmethod="post"
                    name="intent"
                    value="destroy"
                    data-turbo-frame="items"
                >Delete</button>
            </div>

            <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-alert-dialog-target="backdrop"></div>
                <div data-alert-dialog-target="dialog">
                    <button id="cancel" type="button" data-action="alert-dialog#cancel">Cancel</button>
                    <button id="confirm" type="button" data-action="alert-dialog#confirm">Confirm</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.triggerClicks = 0;
        window.zoneClicks = [];
        window.submissions = [];
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
        window.StimulusApplication.register("probe", window.ProbeController);

        document.querySelector("#delete-item").addEventListener("click", () => window.triggerClicks++);
        document.addEventListener("submit", (event) => {
            event.preventDefault();
            window.submissions.push({
                form: event.target.id,
                name: event.submitter?.name,
                value: event.submitter?.value,
                action: event.submitter?.getAttribute("formaction"),
                method: event.submitter?.getAttribute("formmethod"),
                frame: event.submitter?.getAttribute("data-turbo-frame"),
            });
        });
    });

    await page.locator("#delete-item").click();
    await page.evaluate(() => {
        const form = document.querySelector("#item-form");
        const trigger = document.querySelector("#delete-item");

        form.replaceWith(form.cloneNode(true));
        trigger.replaceWith(trigger.cloneNode(true));
    });
    await page.locator("#confirm").click();

    await expect.poll(() => page.evaluate(() => window.submissions)).toEqual([{
        form: "item-form",
        name: "intent",
        value: "destroy",
        action: "/items/42",
        method: "post",
        frame: "items",
    }]);
    expect(await page.evaluate(() => window.triggerClicks)).toBe(1);
    expect(await page.evaluate(() => window.zoneClicks)).toEqual([{ trusted: true }]);
    await expect(page.locator("#delete-item")).toBeFocused();
});

test("a replayed link keeps inherited Turbo opt-out context", async ({ page }) => {
    await page.setContent(`
        <div id="confirmation" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-turbo="false" data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                <a id="trigger" href="#confirmed">Continue</a>
            </div>

            <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-alert-dialog-target="backdrop"></div>
                <div data-alert-dialog-target="dialog">
                    <button type="button" data-action="alert-dialog#cancel">Cancel</button>
                    <button id="confirm" type="button" data-action="alert-dialog#confirm">Confirm</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.turboClicks = 0;
        document.addEventListener("turbo:click", () => window.turboClicks++);
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    await page.locator("#trigger").click();
    await page.locator("#confirm").click();

    await expect.poll(() => page.evaluate(() => window.location.hash)).toBe("#confirmed");
    expect(await page.evaluate(() => window.turboClicks)).toBe(0);
});

test("a replayed Turbo method link keeps its request method", async ({ page }) => {
    await page.route("https://example.test/**", async (route) => {
        if (route.request().url().endsWith("/current")) {
            await route.fulfill({ contentType: "text/html", body: "<!doctype html><html><body></body></html>" });
        } else {
            await route.fulfill({ status: 204 });
        }
    });
    await page.goto("https://example.test/current");
    await page.setContent(`
        <div id="confirmation" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                <a id="trigger" href="/items/42" data-turbo-method="delete">Delete</a>
            </div>

            <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-alert-dialog-target="backdrop"></div>
                <div data-alert-dialog-target="dialog">
                    <button type="button" data-action="alert-dialog#cancel">Cancel</button>
                    <button id="confirm" type="button" data-action="alert-dialog#confirm">Confirm</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    await page.locator("#trigger").click();
    const requestPromise = page.waitForRequest((request) => request.url().endsWith("/items/42"));
    await page.locator("#confirm").click();
    const request = await requestPromise;

    expect(request.method()).toBe("DELETE");
});

test("a generic button action runs once only after confirmation", async ({ page }) => {
    await page.setContent(`
        <div id="confirmation" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                <button id="trigger" type="button" data-controller="probe" data-action="click->probe#execute">Archive</button>
            </div>

            <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-alert-dialog-target="backdrop"></div>
                <div data-alert-dialog-target="dialog">
                    <button type="button" data-action="alert-dialog#cancel">Cancel</button>
                    <button id="confirm" type="button" data-action="alert-dialog#confirm">Confirm</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.executions = [];
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
        window.StimulusApplication.register("probe", window.ProbeController);
    });

    await page.locator("#trigger").click();
    expect(await page.evaluate(() => window.executions)).toEqual([]);

    await page.locator("#confirm").click();

    await expect.poll(() => page.evaluate(() => window.executions)).toEqual([{ trusted: false }]);
});

test("a replaced submitter retains Turbo busy state", async ({ page }) => {
    await page.route("https://example.test/**", async (route) => {
        if (route.request().url().endsWith("/current")) {
            await route.fulfill({ contentType: "text/html", body: "<!doctype html><html><body></body></html>" });
        } else {
            await new Promise((resolve) => setTimeout(resolve, 500));
            await route.fulfill({ contentType: "text/html", body: "<!doctype html><html><body>Saved</body></html>" });
        }
    });
    await page.goto("https://example.test/current");
    await page.setContent(`
        <form id="item-form" action="/items/42" method="post"></form>
        <div id="confirmation" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
            <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
                <button
                    id="trigger"
                    type="submit"
                    form="item-form"
                    data-turbo-submits-with="Deleting..."
                >Delete</button>
            </div>

            <div data-alert-dialog-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-alert-dialog-target="backdrop"></div>
                <div data-alert-dialog-target="dialog">
                    <button type="button" data-action="alert-dialog#cancel">Cancel</button>
                    <button id="confirm" type="button" data-action="alert-dialog#confirm">Confirm</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    await page.locator("#trigger").click();
    await page.locator("#trigger").evaluate((trigger) => trigger.replaceWith(trigger.cloneNode(true)));
    await page.locator("#confirm").click();

    await expect(page.locator("#trigger")).toBeDisabled();
    await expect(page.locator("#trigger")).toHaveText("Deleting...");
});

async function browserControllerScript() {
    const files = [
        "_composition.js",
        "_focus_trap.js",
        "_overlay_stack.js",
        "_top_layer.js",
        "_presence.js",
        "_overlay.js",
        "_action_replay.js",
    ];
    const sources = await Promise.all(files.map(async (file) => {
        const source = await readFile(`resources/js/controllers/${file}`, "utf8");

        return source
            .replace(/^import .*;\s*$/gm, "")
            .replace(/^export /gm, "");
    }));
    const controller = (await readFile("resources/js/controllers/alert_dialog_controller.js", "utf8"))
        .replace(/^import .*;\s*$/gm, "")
        .replace("export default class AlertDialogController extends Controller", "class AlertDialogController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${sources.join("\n")}
        ${controller}
        class ProbeController extends Controller {
            track(event) {
                window.zoneClicks.push({ trusted: event.isTrusted });
            }

            execute(event) {
                window.executions.push({ trusted: event.isTrusted });
            }
        }
        window.AlertDialogController = AlertDialogController;
        window.ProbeController = ProbeController;
    `;
}
