import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("processes oembed elements appended inside a connected root", async ({ page }) => {
    await page.setContent('<article id="content" data-controller="oembed"><p>Existing content</p></article>');
    await installController(page);

    const sameController = await page.locator("#content").evaluate(async (root) => {
        const controller = window.app.getControllerForElementAndIdentifier(root, "oembed");

        root.insertAdjacentHTML("beforeend", '<oembed url="https://vimeo.com/123456789"></oembed>');

        await new Promise((resolve) => setTimeout(resolve, 0));

        return controller === window.app.getControllerForElementAndIdentifier(root, "oembed");
    });

    expect(sameController).toBe(true);
    await expect(page.locator('#content [data-slot="oembed-frame"]')).toHaveAttribute(
        "src",
        "https://player.vimeo.com/video/123456789",
    );
    await expect(page.locator("#content oembed")).toHaveCount(0);
});

test("processes oembed elements added while Turbo morph preserves the root", async ({ page }) => {
    await page.setContent('<article id="content" data-controller="oembed"><p>Old content</p></article>');
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await installController(page);

    const identity = await page.locator("#content").evaluate(async (root) => {
        const controller = window.app.getControllerForElementAndIdentifier(root, "oembed");
        const replacement = document.createElement("article");
        replacement.id = "content";
        replacement.dataset.controller = "oembed";
        replacement.innerHTML = `
            <figure>
                <oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed>
            </figure>
        `;

        window.Turbo.morphChildren(root, replacement);
        await new Promise((resolve) => setTimeout(resolve, 0));

        return {
            rootPreserved: document.querySelector("#content") === root,
            controllerPreserved: controller === window.app.getControllerForElementAndIdentifier(root, "oembed"),
        };
    });

    expect(identity).toEqual({ rootPreserved: true, controllerPreserved: true });
    await expect(page.locator('#content [data-slot="oembed-frame"]')).toHaveAttribute(
        "src",
        "https://www.youtube.com/embed/dQw4w9WgXcQ",
    );
    await expect(page.locator("#content oembed")).toHaveCount(0);
});

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("oembed", window.OembedController);
    });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/oembed_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class OembedController extends Controller")
        .concat("\nwindow.OembedController = OembedController;\n");
}
