import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

// A kebab-case identifier makes an invalid dataset key: writing dataset["workspace-panelOpenValue"]
// throws SyntaxError in the browser and reading it yields undefined, while happy-dom quietly
// normalises both. The Bun suite therefore cannot see this regression.
test("a hyphenated provider identifier toggles and preserves state across a Turbo render", async ({ page }) => {
    await page.setContent(fixture());
    await installController(page);

    const root = page.locator("#root");
    const trigger = page.locator("#trigger");
    const panel = page.locator("#panel");

    await expect(root).toHaveAttribute("data-state", "expanded");
    await expect(root).toHaveAttribute("data-workspace-panel-open-value", "true");

    await trigger.click();

    await expect(root).toHaveAttribute("data-state", "collapsed");
    await expect(root).toHaveAttribute("data-workspace-panel-open-value", "false");
    await expect(panel).toHaveAttribute("data-state", "collapsed");
    await expect(panel).toHaveAttribute("data-collapsible", "icon");
    await expect(trigger).toHaveAttribute("aria-expanded", "false");

    const preserved = await page.evaluate((markup) => {
        const newBody = document.createElement("body");
        newBody.innerHTML = markup;
        window.dispatchEvent(new CustomEvent("turbo:before-render", { detail: { newBody } }));

        return {
            root: newBody.querySelector("#root").getAttribute("data-state"),
            openValue: newBody.querySelector("#root").getAttribute("data-workspace-panel-open-value"),
            panel: newBody.querySelector("#panel").getAttribute("data-state"),
        };
    }, fixture());

    expect(preserved).toEqual({ root: "collapsed", openValue: "false", panel: "collapsed" });
});

function fixture() {
    return `
        <div id="root"
             data-controller="workspace-panel"
             data-slot="sidebar-wrapper"
             data-state="expanded"
             data-workspace-panel-open-value="true"
             data-workspace-panel-cookie-name-value="panel_state"
             data-action="turbo:before-render@window->workspace-panel#preserveStateForRender">
            <button id="trigger"
                    type="button"
                    data-slot="sidebar-trigger"
                    aria-expanded="true"
                    data-action="click->workspace-panel#toggle">Toggle</button>
            <div id="panel"
                 data-slot="sidebar"
                 data-sidebar-collapsible="icon"
                 data-state="expanded"
                 data-collapsible=""></div>
        </div>
    `;
}

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });

    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("workspace-panel", window.SidebarController);
    });
}

async function browserControllerScript() {
    // ES `import` is not valid inside a regular <script>, so the helper is concatenated instead.
    // The fixture carries no overlay targets, so createOverlay is never reached.
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");

    const controller = (await readFile("resources/js/controllers/sidebar_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace("export default class extends Controller", "class SidebarController extends Controller");

    return [
        composition,
        "function createOverlay() { throw new Error('overlay not installed in this fixture'); }",
        controller,
        "window.SidebarController = SidebarController;",
    ].join("\n");
}
