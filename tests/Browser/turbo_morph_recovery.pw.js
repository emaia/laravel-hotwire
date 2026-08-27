import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("Turbo morph identity follows stable and volatile host IDs", async ({ page }) => {
    await page.setContent(`
        <div id="stable-host"><span>old stable content</span></div>
        <div id="volatile-parent">
            <section id="volatile-old"><span>old volatile content</span></section>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

    const identity = await page.evaluate(() => {
        const stableHost = document.querySelector("#stable-host");
        stableHost.wrapperInstance = { mounted: true };

        const stableReplacement = document.createElement("div");
        stableReplacement.id = "stable-host";
        stableReplacement.innerHTML = "<span>new stable content</span>";
        window.Turbo.morphElements(stableHost, stableReplacement);

        const volatileHost = document.querySelector("#volatile-old");
        volatileHost.wrapperInstance = { mounted: true };

        const parentReplacement = document.createElement("div");
        parentReplacement.id = "volatile-parent";
        parentReplacement.innerHTML = `
            <section id="volatile-new"><span>new volatile content</span></section>
        `;
        window.Turbo.morphChildren(
            document.querySelector("#volatile-parent"),
            parentReplacement,
        );

        return {
            stable: document.querySelector("#stable-host") === stableHost
                && stableHost.wrapperInstance?.mounted === true,
            volatile: document.querySelector("#volatile-new") === volatileHost
                || document.querySelector("#volatile-new").wrapperInstance?.mounted === true,
        };
    });

    expect(identity.stable).toBe(true);
    expect(identity.volatile).toBe(false);
});

test("morph recovery runs once after a real Turbo morph finishes", async ({ page }) => {
    await page.setContent(`
        <div id="stable-host"><canvas id="old-canvas"></canvas></div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ content: await morphRecoveryScript() });

    const result = await page.evaluate(async () => {
        const host = document.querySelector("#stable-host");
        const oldCanvas = document.querySelector("#old-canvas");
        let recoveries = 0;

        const detach = window.attachMorphRecovery({ element: host }, {
            isStale: () => !document.contains(oldCanvas),
            recover: () => recoveries++,
        });

        const replacement = document.createElement("div");
        replacement.id = "stable-host";
        replacement.innerHTML = '<canvas id="new-canvas"></canvas>';
        window.Turbo.morphElements(host, replacement);

        await new Promise((resolve) => setTimeout(resolve, 0));
        detach();

        return {
            recoveries,
            replacementPresent: host.querySelector("#new-canvas") !== null,
        };
    });

    expect(result).toEqual({ recoveries: 1, replacementPresent: true });
});

async function morphRecoveryScript() {
    const source = await readFile("resources/js/controllers/_turbo_morph_recovery.js", "utf8");

    return source
        .replace("export function attachMorphRecovery", "function attachMorphRecovery")
        .concat("\nwindow.attachMorphRecovery = attachMorphRecovery;\n");
}
