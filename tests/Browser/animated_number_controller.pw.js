import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("animation updates the existing text without waking child-list observers", async ({ page }) => {
    await page.setContent(`
        <div id="reveal-region">
            <span data-controller="animated-number" data-animated-number-lazy-value="true"
                data-animated-number-end-value="100" data-animated-number-duration-value="1000">100</span>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    const source = (await readFile("resources/js/controllers/animated_number_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class", "window.AnimatedNumber = class");
    await page.addScriptTag({ content: source });

    const samples = await page.evaluate(async () => {
        const app = new window.Stimulus.Application();
        app.register("animated-number", window.AnimatedNumber);
        await app.start();
        const region = document.querySelector("#reveal-region");
        const counter = region.querySelector("span");
        const controller = app.getControllerForElementAndIdentifier(counter, "animated-number");

        // Drive the actual controller through both a changed and an unchanged integer.
        let frame;
        window.requestAnimationFrame = (callback) => { frame = callback; return 1; };
        const text = counter.firstChild;
        const observer = new MutationObserver(() => {});
        observer.observe(region, { subtree: true, childList: true, characterData: true });
        controller.animate();

        return [100, 101, 120, 1100].map((timestamp) => {
            frame(timestamp);
            return {
                text: counter.textContent,
                sameNode: counter.firstChild === text,
                mutations: observer.takeRecords().map(({ type }) => type),
            };
        });
    });

    expect(samples).toEqual([
        { text: "0", sameNode: true, mutations: ["characterData"] },
        { text: "0", sameNode: true, mutations: [] },
        { text: "2", sameNode: true, mutations: ["characterData"] },
        { text: "100", sameNode: true, mutations: ["characterData"] },
    ]);
});
