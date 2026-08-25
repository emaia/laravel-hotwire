import { expect, test } from "@playwright/test";
import { createServer } from "node:http";
import { readFile } from "node:fs/promises";

// A unit test cannot reach this: the gap where the viewport is detached exists only because Turbo's
// renderer awaits the body swap between detaching the permanent element and putting it back.
test("renders a flash toast that arrives during a Drive navigation", async ({ page }) => {
    const server = createServer((request, response) => {
        const url = new URL(request.url, "http://localhost");

        if (url.pathname === "/done") {
            html(response, layout(`
                <h1>Done</h1>
                <div data-slot="toast-trigger"
                     data-turbo-temporary
                     data-controller="toast"
                     data-toast-message-value="Renamed to &quot;Q3 report&quot;"
                     data-toast-type-value="success"></div>
            `));

            return;
        }

        html(response, layout(`<a id="save" href="/done">Save</a>`));
    });

    await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
    const origin = `http://127.0.0.1:${server.address().port}`;

    try {
        await page.goto(`${origin}/start`);
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await controllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("toaster", window.ToasterController);
            window.StimulusApplication.register("toast", window.ToastController);
        });
        await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

        const viewport = page.locator("#toaster");
        await expect.poll(async () => viewport.evaluate((element) =>
            Boolean(window.StimulusApplication.getControllerForElementAndIdentifier(element, "toaster")),
        )).toBe(true);

        // Tag the viewport so a preserved element is distinguishable from a replaced one.
        await viewport.evaluate((element) => { element.dataset.hostToken = "original"; });

        await page.locator("#save").click();
        await expect(page.locator("h1")).toHaveText("Done");

        await expect(viewport).toHaveAttribute("data-host-token", "original");
        // The quotes arrive encoded; a backslash-escaped value would have been cut at the first one.
        await expect(page.locator('[data-slot="toast-title"]')).toHaveText('Renamed to "Q3 report"');
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

function layout(content) {
    return `
        <!doctype html>
        <html>
            <body>
                ${content}
                <div id="toaster" data-slot="toaster" data-turbo-permanent data-controller="toaster"></div>
            </body>
        </html>
    `;
}

async function controllerScript() {
    const read = async (path) => readFile(`resources/js/controllers/${path}`, "utf8");
    const strip = (source) => source.replace(/export function /g, "function ");

    const presence = strip(await read("_presence.js"));
    const topLayer = strip(await read("_top_layer.js"));
    const toaster = strip(await read("_toaster.js")).replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "");

    // Destructured once: declaring Controller per controller is a duplicate binding, and a SyntaxError.
    const controller = async (path, name) =>
        (await read(path))
            .replace(/import \{[^}]*\} from "@hotwired\/stimulus";\s*/, "")
            .replace(/import \{[^}]*\} from "\.\/_[a-z_]+\.js";\s*/g, "")
            .replace("export default class extends Controller", `class ${name} extends Controller`)
            .concat(`\nwindow.${name} = ${name};\n`);

    return [
        "const { Controller } = window.Stimulus;",
        presence,
        topLayer,
        toaster,
        await controller("toast_controller.js", "ToastController"),
        await controller("toaster_controller.js", "ToasterController"),
    ].join("\n");
}
