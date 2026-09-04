import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("opens when dynamic content is inserted and closes cleanly through the public API", async ({ page }) => {
    await page.setContent(`
        <div data-controller="modal">
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                data-modal-lock-scroll-class="overflow-hidden"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <turbo-frame id="modal-frame" data-modal-target="dynamicContent"></turbo-frame>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    const frame = page.locator("#modal-frame");
    const modal = page.locator('[data-modal-target="modal"]');

    await frame.evaluate((element) => {
        const content = document.createElement("div");
        content.textContent = "Loaded content";
        element.appendChild(content);
    });

    await expect(modal).toHaveAttribute("data-state", "open");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(frame).toContainText("Loaded content");

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");

        controller.close();
    });

    await expect(modal).toHaveAttribute("data-state", "closed");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect(frame).toBeEmpty();
});

for (const family of ["modal", "sheet", "drawer"]) {
    test(`frame content names the ${family} before it opens`, async ({ page }) => {
        await mountFrameOverlay(page, family);

        const frame = page.locator(`#${family}-frame`);
        const overlay = page.locator(`[data-${family}-target="modal"]`);
        await frame.evaluate((element, identifier) => {
            element.innerHTML = `
                <h2 data-slot="${identifier}-title">Loaded title</h2>
                <p data-slot="${identifier}-description">Loaded description</p>
            `;
        }, family);

        await expect(overlay).toHaveAttribute("data-state", "open");
        await expect(overlay).toHaveAttribute("aria-labelledby", `${family}-shell-title`);
        await expect(overlay).toHaveAttribute("aria-describedby", `${family}-shell-description`);
        await expect(page.locator(`#${family}-shell-title`)).toHaveText("Loaded title");
        await expect(page.locator(`#${family}-shell-description`)).toHaveText("Loaded description");

        await frame.evaluate((element) => element.replaceChildren());

        await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
        await expect(overlay).not.toHaveAttribute("aria-describedby", /.+/);
    });
}

for (const family of ["modal", "sheet", "drawer"]) {
    test(`${family} applies each initial focus strategy`, async ({ page }) => {
        const strategies = ["auto", "dialog", "first-focusable", "none"];
        await page.setContent(
            strategies
                .map(
                    (strategy) => `
            <div
                id="${family}-${strategy}"
                data-controller="${family}"
                data-${family}-initial-focus-value="${strategy}"
                data-${family}-lock-scroll-class="overflow-hidden"
            >
                <button id="${family}-${strategy}-trigger" data-action="${family}#open">Open</button>
                <div
                    id="${family}-${strategy}-overlay"
                    data-${family}-target="modal"
                    data-state="closed"
                    data-motion="none"
                    role="dialog"
                    tabindex="-1"
                    hidden inert
                >
                    <div data-${family}-target="backdrop"></div>
                    <div data-${family}-target="dialog">
                        <input id="${family}-${strategy}-first" />
                        <button id="${family}-${strategy}-autofocus" autofocus>Explicit</button>
                    </div>
                </div>
            </div>
        `,
                )
                .join(""),
        );
        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate(() => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register("modal", window.ModalController);
            window.StimulusApplication.register("sheet", window.SheetController);
            window.StimulusApplication.register("drawer", window.DrawerController);
        });

        const expected = {
            auto: `#${family}-auto-autofocus`,
            dialog: `#${family}-dialog-overlay`,
            "first-focusable": `#${family}-first-focusable-first`,
            none: `#${family}-none-trigger`,
        };

        for (const strategy of strategies) {
            await page.locator(`#${family}-${strategy}-trigger`).click();
            await expect(page.locator(expected[strategy])).toBeFocused();

            if (strategy === "none") {
                await page.keyboard.press("Tab");
                await expect(page.locator(`#${family}-none-first`)).toBeFocused();
            }

            await page.evaluate(
                ({ family: identifier, strategy: value }) => {
                    const root = document.querySelector(`#${identifier}-${value}`);
                    const controller = window.StimulusApplication.getControllerForElementAndIdentifier(
                        root,
                        identifier,
                    );
                    controller.close();
                },
                { family, strategy },
            );
        }
    });
}

test("initial focus skips hidden candidates and follows value changes", async ({ page }) => {
    await page.setContent(`
        <div
            id="modal-root"
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <details><button id="collapsed">Collapsed</button></details>
                    <button id="hidden" style="display: none">Hidden</button>
                    <button id="visible">Visible</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await expect(page.locator("#visible")).toBeFocused();

    await page.evaluate(async () => {
        const root = document.querySelector("#modal-root");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.close();
        root.dataset.modalInitialFocusValue = "dialog";
    });
    await page.locator("#trigger").click();

    await expect(page.locator('[data-modal-target="modal"]')).toBeFocused();
});

test("the focus trap ignores invalid contenteditable candidates", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="first">First</button>
                    <button id="last">Last</button>
                    <div contenteditable="inherit">Not an editing host</div>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await page.locator("#last").focus();
    await page.keyboard.press("Tab");

    await expect(page.locator("#first")).toBeFocused();
});

test("wrapping focus scrolls the destination control into view", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div id="scroller" data-modal-target="dialog" style="height: 200px; overflow: auto">
                    <button id="first">First</button>
                    <div style="height: 2000px"></div>
                    <button id="last">Last</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await page.keyboard.press("Shift+Tab");

    await expect(page.locator("#last")).toBeFocused();
    await expect.poll(() => page.locator("#scroller").evaluate((element) => element.scrollTop)).toBeGreaterThan(0);
});

test("content-visibility auto does not remove controls from the focus trap", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="first-focusable"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog" style="position: relative; height: 2400px">
                    <div style="position: absolute; top: 1600px; content-visibility: auto; contain-intrinsic-size: 100px">
                        <button id="deferred">Deferred</button>
                    </div>
                    <button id="fallback">Fallback</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();

    await expect(page.locator("#deferred")).toBeFocused();
});

test("Tab from a programmatically focused descendant follows native order", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="dialog"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="trigger" data-action="modal#open">Open</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                tabindex="-1"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="first">First</button>
                    <div id="programmatic" tabindex="-1">Programmatic</div>
                    <button id="next">Next</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await page.locator("#programmatic").focus();
    await page.keyboard.press("Tab");

    await expect(page.locator("#next")).toBeFocused();
});

test("Tab from a negative-tabindex autofocus target stays inside the modal", async ({ page }) => {
    await page.setContent(`
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="trigger" data-action="modal#open">Open</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" role="dialog" tabindex="-1" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="first">First</button>
                    <button id="last">Last</button>
                    <button id="autofocus" tabindex="-1" autofocus>Autofocus</button>
                </div>
            </div>
        </div>
        <button id="outside">Outside</button>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#trigger").click();
    await expect(page.locator("#autofocus")).toBeFocused();
    await page.keyboard.press("Tab");

    await expect(page.locator("#first")).toBeFocused();
});

test("a pre-rendered open modal with none does not honor native autofocus", async ({ page }) => {
    await page.setContent(`
        <button id="outside">Outside</button>
        <div
            data-controller="modal"
            data-modal-initial-focus-value="none"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <div
                data-modal-target="modal"
                data-state="open"
                data-motion="none"
                role="dialog"
                tabindex="-1"
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="autofocus" autofocus>Autofocus</button>
                </div>
            </div>
        </div>
    `);
    await page.locator("#outside").focus();
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await expect(page.locator("#outside")).toBeFocused();
});

test("a pre-rendered open modal applies an explicit strategy over existing focus", async ({ page }) => {
    await page.setContent(`
        <div
            data-controller="modal"
            data-modal-initial-focus-value="dialog"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <div
                data-modal-target="modal"
                data-state="open"
                data-motion="none"
                role="dialog"
                tabindex="-1"
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="focused">Focused before connect</button>
                </div>
            </div>
        </div>
    `);
    await page.locator("#focused").focus();
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await expect(page.locator('[data-modal-target="modal"]')).toBeFocused();
});

test("frame labels replace ambiguous authored ids with collision-free ids", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<span id="page-title">Layout title</span>',
    });

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Initial title</h2>';
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Initial title");

    await frame.evaluate((element) => {
        element.innerHTML = '<h2 id="page-title" data-slot="modal-title">Updated title</h2>';
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(overlay).toHaveAttribute("data-hotwire-overlay-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Updated title");
    await expect(page.locator("#page-title")).toHaveCount(1);
    await expect(page.locator("#page-title")).toHaveText("Layout title");
});

test("frame labels resync after relevant attribute mutations", async ({ page }) => {
    await mountFrameOverlay(page, "modal");

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Account settings</h2>';
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");

    await page.locator("#modal-shell-title").evaluate((title) => {
        title.id = "renamed-title";
    });
    await expect(overlay).toHaveAttribute("aria-labelledby", "renamed-title");

    await page.locator("#renamed-title").evaluate((title) => {
        title.removeAttribute("data-slot");
    });
    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);

    await page.getByText("Account settings").evaluate((title) => {
        title.setAttribute("data-slot", "modal-title");
    });
    await expect(overlay).toHaveAttribute("aria-labelledby", "renamed-title");
});

test("frame labels move from loading content to the response without claiming nested overlays", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<button id="modal-shell-title">Open</button>',
        loadingTemplate: '<h2 data-slot="modal-title">Loading post</h2>',
    });

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.dispatchEvent("turbo:before-fetch-request");

    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title-2");
    await expect(page.locator("#modal-shell-title-2")).toHaveText("Loading post");
    await expect(overlay).toHaveAttribute("data-state", "open");

    await frame.evaluate((element) => {
        const replacement = document.createElement("turbo-frame");
        replacement.id = element.id;
        replacement.innerHTML = `
            <div role="dialog">
                <h2 data-slot="modal-title">Nested title</h2>
            </div>
            <h2 id="response-title" data-slot="modal-title">Edit post</h2>
        `;
        element.replaceWith(replacement);
        replacement.dispatchEvent(new CustomEvent("turbo:frame-render", { bubbles: true }));
    });

    await expect(overlay).toHaveAttribute("aria-labelledby", "response-title");
    await expect(page.locator("#response-title")).toHaveText("Edit post");
    await expect(page.getByText("Nested title")).not.toHaveAttribute("id", /.+/);

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal").clearContent();
    });

    await expect(frame).toBeEmpty();
    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
});

test("frame labels do not override an authored accessible name", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        overlayAttributes: 'aria-label="Authored dialog"',
    });

    const frame = page.locator("#modal-frame");
    const overlay = page.locator('[data-modal-target="modal"]');
    await frame.evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Loaded title</h2>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-label", "Authored dialog");
    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
});

test("frame labels preserve an authored labelledby reference", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<span id="app-title">Authored title</span>',
        overlayAttributes: 'aria-labelledby="app-title"',
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Loaded title</h2>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-labelledby", "app-title");
    await expect(page.locator("#app-title")).toHaveText("Authored title");
});

test("frame labels do not override an authored accessible description", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        overlayAttributes: 'aria-description="Authored description"',
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<p data-slot="modal-description">Loaded description</p>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-description", "Authored description");
    await expect(overlay).not.toHaveAttribute("aria-describedby", /.+/);
});

test("frame labels preserve an authored describedby reference", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        beforeOverlay: '<span id="app-description">Authored description</span>',
        overlayAttributes: 'aria-describedby="app-description"',
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<p data-slot="modal-description">Loaded description</p>';
    });

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-describedby", "app-description");
    await expect(page.locator("#app-description")).toHaveText("Authored description");
});

test("frame labels resume generated ownership after a morph removes authored semantics", async ({ page }) => {
    await mountFrameOverlay(page, "modal", {
        overlayAttributes: 'aria-label="Authored dialog"',
    });
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });

    await page.locator('[data-modal-target="modal"]').evaluate((overlay) => {
        const replacement = overlay.cloneNode(true);
        replacement.removeAttribute("aria-label");
        replacement.querySelector("turbo-frame").innerHTML = '<h2 data-slot="modal-title">Morphed title</h2>';

        window.Turbo.morphElements(overlay, replacement);
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).not.toHaveAttribute("aria-label", /.+/);
    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(overlay).toHaveAttribute("data-hotwire-overlay-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Morphed title");
});

test("frame labels reacquire a replaced overlay target", async ({ page }) => {
    await mountFrameOverlay(page, "modal");
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = '<h2 data-slot="modal-title">Initial title</h2>';
    });
    await expect(page.locator('[data-modal-target="modal"]')).toHaveAttribute("aria-labelledby", "modal-shell-title");

    await page.locator('[data-modal-target="modal"]').evaluate((element) => {
        const replacement = element.cloneNode(true);
        replacement.removeAttribute("aria-labelledby");
        replacement.removeAttribute("data-hotwire-overlay-labelledby");
        replacement.querySelector('[data-slot="modal-title"]').removeAttribute("id");
        element.replaceWith(replacement);
    });

    const replacement = page.locator('[data-modal-target="modal"]');
    await expect(replacement).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await expect(page.locator("#modal-shell-title")).toHaveText("Initial title");
});

test("frame removal clears managed references after close", async ({ page }) => {
    await mountFrameOverlay(page, "modal");
    await page.locator("#modal-frame").evaluate((element) => {
        element.innerHTML = `
            <h2 data-slot="modal-title">Loaded title</h2>
            <p data-slot="modal-description">Loaded description</p>
        `;
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await expect(overlay).toHaveAttribute("aria-labelledby", "modal-shell-title");
    await page.locator("#modal-frame").evaluate((element) => element.remove());
    await page.evaluate(async () => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.close();
    });

    await expect(overlay).not.toHaveAttribute("aria-labelledby", /.+/);
    await expect(overlay).not.toHaveAttribute("aria-describedby", /.+/);
    await expect(overlay).not.toHaveAttribute("data-hotwire-overlay-labelledby", /.+/);
    await expect(overlay).not.toHaveAttribute("data-hotwire-overlay-describedby", /.+/);
});

test("static overlay names survive close and reopen", async ({ page }) => {
    await page.setContent(`
        <div id="static-modal" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div
                data-hotwire-overlay-labels
                data-hotwire-overlay-labelledby="static-modal-title"
                data-slot="modal-overlay"
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                aria-labelledby="static-modal-title"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <h2 id="static-modal-title" data-slot="modal-title">Static title</h2>
                    <button id="close" data-action="modal#close">Close</button>
                </div>
            </div>
        </div>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    const overlay = page.locator('[data-modal-target="modal"]');
    await page.locator("#open").click();
    await page.locator("#close").click();
    await page.locator("#open").click();

    await expect(overlay).toHaveAttribute("data-state", "open");
    await expect(overlay).toHaveAttribute("aria-labelledby", "static-modal-title");
    await expect(page.locator("#static-modal-title")).toHaveText("Static title");
});

test("Turbo morph updates modal content without overwriting its presence state", async ({ page }) => {
    await page.setContent(`
        <div id="modal-shell" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div id="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <p id="modal-content">Initial content</p>
                    <p id="server-status" data-state="stale">Initial status</p>
                    <button data-action="modal#close">Close</button>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open").click();

    const modal = page.locator("#modal-overlay");
    await expect(modal).toHaveAttribute("data-state", "open");
    await expect.poll(async () => modal.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await page.evaluate(() => {
        const replacement = document.createElement("div");
        replacement.id = "modal-overlay";
        replacement.setAttribute("data-modal-target", "modal");
        replacement.setAttribute("data-state", "closed");
        replacement.setAttribute("data-motion", "none");
        replacement.setAttribute("hidden", "");
        replacement.setAttribute("inert", "");
        replacement.innerHTML = `
            <div data-modal-target="backdrop"></div>
            <div data-modal-target="dialog">
                <p id="modal-content">Morphed open content</p>
                <p id="server-status" data-state="updated" hidden>Updated status</p>
                <button data-action="modal#close">Close</button>
            </div>
        `;

        window.Turbo.morphElements(document.querySelector("#modal-overlay"), replacement);
    });

    await expect(modal).toHaveAttribute("data-state", "open");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(modal).not.toHaveAttribute("inert", "");
    await expect(modal).toContainText("Morphed open content");
    await expect(page.locator("#server-status")).toHaveAttribute("data-state", "updated");
    await expect(page.locator("#server-status")).toHaveAttribute("hidden", "");
    await expect.poll(async () => modal.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.evaluate(async () => {
        const root = document.querySelector("#modal-shell");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.close();

        const replacement = document.createElement("div");
        replacement.id = "modal-overlay";
        replacement.setAttribute("data-modal-target", "modal");
        replacement.setAttribute("data-state", "open");
        replacement.setAttribute("data-motion", "none");
        replacement.innerHTML = `
            <div data-modal-target="backdrop"></div>
            <div data-modal-target="dialog">
                <p id="modal-content">Morphed closed content</p>
                <p id="server-status" data-state="final">Final status</p>
                <button data-action="modal#close">Close</button>
            </div>
        `;

        window.Turbo.morphElements(document.querySelector("#modal-overlay"), replacement);
    });

    await expect(modal).toHaveAttribute("data-state", "closed");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect(modal).toHaveAttribute("inert", "");
    await expect(modal).toContainText("Morphed closed content");
    await expect(page.locator("#server-status")).toHaveAttribute("data-state", "final");
    await expect(page.locator("#server-status")).not.toHaveAttribute("hidden", "");
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
});

test("Turbo Frame morph preserves an open drawer while updating its content", async ({ page }) => {
    await page.setContent(`
        <turbo-frame id="drawer-frame">
            <div id="drawer-shell" data-controller="drawer" data-drawer-lock-scroll-class="overflow-hidden">
                <button id="open-drawer" data-action="drawer#open">Open</button>
                <div id="drawer-overlay" data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-drawer-target="backdrop"></div>
                    <div data-drawer-target="dialog"><p id="drawer-content">Initial drawer</p></div>
                </div>
            </div>
        </turbo-frame>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("drawer", window.DrawerController);
    });

    await page.locator("#open-drawer").click();

    const drawer = page.locator("#drawer-overlay");
    await expect(drawer).toHaveAttribute("data-state", "open");

    await page.evaluate(() => {
        const replacement = document.createElement("turbo-frame");
        replacement.id = "drawer-frame";
        replacement.innerHTML = `
            <div id="drawer-shell" data-controller="drawer" data-drawer-lock-scroll-class="overflow-hidden">
                <button id="open-drawer" data-action="drawer#open">Open</button>
                <div id="drawer-overlay" data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-drawer-target="backdrop"></div>
                    <div data-drawer-target="dialog"><p id="drawer-content">Morphed drawer</p></div>
                </div>
            </div>
        `;

        window.Turbo.morphChildren(document.querySelector("#drawer-frame"), replacement);
    });

    await expect(drawer).toHaveAttribute("data-state", "open");
    await expect(drawer).not.toHaveAttribute("hidden", "");
    await expect(drawer).not.toHaveAttribute("inert", "");
    await expect(drawer).toContainText("Morphed drawer");
    await expect.poll(async () => drawer.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);
});

test("tabs from the modal close button into native accordion summaries", async ({ page }) => {
    await page.setContent(`
        <style>.hidden { display: none; }</style>
        <div data-controller="modal" data-modal-initial-focus-value="first-focusable">
            <button id="open-modal" data-action="modal#open">Open modal</button>
            <div
                data-modal-target="modal"
                data-state="closed"
                data-motion="none"
                data-modal-lock-scroll-class="overflow-hidden"
                hidden inert
            >
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <button id="close-modal" type="button" data-action="modal#close">Close</button>
                    <section data-controller="accordion" data-accordion-type-value="single">
                        <details data-accordion-target="item" data-value="billing">
                            <summary id="billing-summary">Billing</summary>
                            <section>Billing answers.</section>
                        </details>
                    </section>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-modal").click();
    await expect(page.locator("#close-modal")).toBeFocused();

    await page.keyboard.press("Tab");
    await expect(page.locator("#billing-summary")).toBeFocused();
});

test("nested modal overlay enters the browser top layer", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
            [data-hotwire-top-layer][popover][data-slot="modal-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-backdrop"] { position: absolute; inset: 0; }
            [data-slot="modal-positioner"] { position: relative; z-index: 1; }
            .hidden { pointer-events: none; }
            .visible { pointer-events: auto; }
            .dialog-hidden { opacity: 0; transform: scale(.8); }
            .dialog-visible { opacity: 1; transform: scale(1); }
        </style>
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <section style="width: 260px; height: 140px; overflow: hidden; transform: scale(.95); border-radius: 16px; background: white;">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div id="inner" data-controller="modal">
                            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                                <div data-slot="modal-positioner" data-modal-target="dialog">
                                    <button id="inner-close" data-action="modal#close">Close inner</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-outer").click();
    await page.evaluate(() => {
        const root = document.querySelector("#inner");
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");

        controller.open({ target: document.querySelector("#open-inner") });
    });

    const innerOverlay = page.locator("#inner [data-modal-target='modal']");

    await expect(innerOverlay).toHaveAttribute("popover", "manual");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => innerOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);
    await expect.poll(async () => innerOverlay.evaluate((element) => {
        const rect = element.getBoundingClientRect();

        return { width: Math.round(rect.width), height: Math.round(rect.height) };
    })).toEqual({ width: 1280, height: 720 });
});

test("nested modal and alert dialog close one layer at a time with Escape", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
            [data-hotwire-top-layer][popover]:is([data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"]) { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-backdrop"], [data-slot="alert-dialog-backdrop"] { position: absolute; inset: 0; }
            [data-slot="modal-positioner"], [data-slot="alert-dialog-panel"] { position: relative; z-index: 1; background: white; }
            .hidden { pointer-events: none; }
            .visible { pointer-events: auto; }
            .dialog-hidden { opacity: 0; transform: scale(.8); }
            .dialog-visible { opacity: 1; transform: scale(1); }
        </style>
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <button id="outer-close" data-action="modal#close">Close outer</button>
                    <div id="inner" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" data-modal-lock-scroll-class="overflow-hidden" hidden inert>
                            <div data-slot="modal-backdrop" data-modal-target="backdrop"></div>
                            <div data-slot="modal-positioner" data-modal-target="dialog">
                                <button id="inner-close" data-action="modal#close">Close inner</button>
                                <div id="confirm" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                                    <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                                    <div data-slot="alert-dialog-overlay" data-alert-dialog-target="modal" data-state="closed" data-motion="none" data-action="click->alert-dialog#clickOutside" hidden inert>
                                        <div data-slot="alert-dialog-backdrop" data-alert-dialog-target="backdrop"></div>
                                        <div data-slot="alert-dialog-panel" data-alert-dialog-target="dialog">
                                            <button id="cancel" data-action="alert-dialog#cancel">Cancel</button>
                                            <button id="confirm-action" data-action="alert-dialog#confirm">Confirm</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    await page.locator("#open-outer").click();
    await page.locator("#open-inner").click();
    await page.locator("#delete").click();

    const outerOverlay = page.locator("#outer > [data-modal-target='modal']");
    const innerOverlay = page.locator("#inner > [data-modal-target='modal']");
    const alertOverlay = page.locator("#confirm [data-alert-dialog-target='modal']");

    await expect(alertOverlay).not.toHaveAttribute("hidden", "");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await page.evaluate(() => {
        const overlay = document.querySelector("#outer > [data-modal-target='modal']");
        const replacement = overlay.cloneNode(true);
        const overlays = [
            replacement,
            ...replacement.querySelectorAll('[data-modal-target="modal"], [data-alert-dialog-target="modal"]'),
        ];

        for (const element of overlays) {
            element.dataset.state = "closed";
            element.hidden = true;
            element.setAttribute("inert", "");
            element.removeAttribute("popover");
            element.removeAttribute("data-hotwire-top-layer");
            element.removeAttribute("data-hotwire-top-layer-popover");
        }

        replacement.querySelector("#outer-close").textContent = "Morphed outer close";
        window.Turbo.morphElements(overlay, replacement);
    });

    await expect(page.locator("#outer-close")).toHaveText("Morphed outer close");
    await expect(alertOverlay).toHaveAttribute("data-state", "open");
    await expect(innerOverlay).toHaveAttribute("data-state", "open");
    await expect(outerOverlay).toHaveAttribute("data-state", "open");
    await expect(alertOverlay).not.toHaveAttribute("hidden", "");
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);

    await outerOverlay.locator(':scope > [data-modal-target="backdrop"]').evaluate((element) => {
        element.replaceWith(element.cloneNode(true));
    });
    await page.waitForTimeout(0);

    await expect.poll(async () => page.evaluate(() => {
        const target = document.elementFromPoint(window.innerWidth / 2, window.innerHeight / 2);

        return target?.closest('[data-slot="alert-dialog-overlay"]') !== null;
    })).toBe(true);

    await page.keyboard.press("Escape");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
    await expect(page.locator("#delete")).toBeFocused();
    await expect(innerOverlay).not.toHaveAttribute("hidden", "");
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.keyboard.press("Escape");
    await expect(innerOverlay).toHaveAttribute("hidden", "");
    await expect(page.locator("#open-inner")).toBeFocused();
    await expect(outerOverlay).not.toHaveAttribute("hidden", "");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

    await page.keyboard.press("Escape");
    await expect(outerOverlay).toHaveAttribute("hidden", "");
    await expect(page.locator("#open-outer")).toBeFocused();
    await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
});

for (const identifier of ["drawer", "sheet"]) {
    test(`${identifier} and alert dialog close one layer at a time with Escape`, async ({ page }) => {
        await page.setContent(`
            <style>
                [hidden] { display: none !important; }
                [data-slot$="-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; }
                [data-hotwire-top-layer][popover][data-slot$="-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
                [data-slot$="-backdrop"] { position: absolute; inset: 0; }
                [data-slot="${identifier}-popup"], [data-slot="alert-dialog-panel"] { position: relative; z-index: 1; background: white; }
            </style>
            <div id="parent" data-controller="${identifier}" data-${identifier}-lock-scroll-class="overflow-hidden">
                <button id="open-parent" data-action="${identifier}#open">Open ${identifier}</button>
                <div data-slot="${identifier}-overlay" data-${identifier}-target="modal" data-state="closed" data-motion="none" hidden inert>
                    <div data-slot="${identifier}-backdrop" data-${identifier}-target="backdrop" data-action="click->${identifier}#clickOutside"></div>
                    <div data-slot="${identifier}-popup" data-${identifier}-target="dialog">
                        <button id="parent-first">First</button>
                        <div id="confirm" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                            <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                            <div data-slot="alert-dialog-overlay" data-alert-dialog-target="modal" data-state="closed" data-motion="none" data-action="click->alert-dialog#clickOutside" hidden inert>
                                <div data-slot="alert-dialog-backdrop" data-alert-dialog-target="backdrop"></div>
                                <div data-slot="alert-dialog-panel" data-alert-dialog-target="dialog">
                                    <button id="cancel" data-action="alert-dialog#cancel">Cancel</button>
                                    <button data-action="alert-dialog#confirm">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);

        await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
        await page.addScriptTag({ content: await browserOverlayControllerScript() });
        await page.evaluate((controllerIdentifier) => {
            window.StimulusApplication = window.Stimulus.Application.start();
            window.StimulusApplication.register(controllerIdentifier, controllerIdentifier === "drawer" ? window.DrawerController : window.SheetController);
            window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
        }, identifier);

        await page.locator("#open-parent").click();
        await page.locator("#delete").click();

        const parentOverlay = page.locator(`#parent > [data-${identifier}-target="modal"]`);
        const alertOverlay = page.locator('#confirm [data-alert-dialog-target="modal"]');

        await expect(parentOverlay).toHaveAttribute("data-state", "open");
        await expect(alertOverlay).toHaveAttribute("data-state", "open");
        await expect.poll(async () => alertOverlay.evaluate((element) => element.matches(":popover-open"))).toBe(true);
        await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

        await page.keyboard.press("Escape");
        await expect(alertOverlay).toHaveAttribute("hidden", "");
        await expect(page.locator("#delete")).toBeFocused();
        await expect(parentOverlay).not.toHaveAttribute("hidden", "");
        await expect(page.locator("body")).toHaveClass(/overflow-hidden/);

        await page.locator("#parent-first").focus();
        await page.keyboard.press("Shift+Tab");
        await expect(page.locator("#delete")).toBeFocused();

        await page.keyboard.press("Escape");
        await expect(parentOverlay).toHaveAttribute("hidden", "");
        await expect(page.locator("#open-parent")).toBeFocused();
        await expect(page.locator("body")).not.toHaveClass(/overflow-hidden/);
    });
}

test("Escape during nested overlay entry closes the entering overlay", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            #confirm [data-alert-dialog-target="dialog"] { opacity: 0; transition: opacity 100ms linear; }
            #confirm [data-alert-dialog-target="modal"][data-state="open"] [data-alert-dialog-target="dialog"] { opacity: 1; }
        </style>
        <div id="parent" data-controller="sheet" data-sheet-lock-scroll-class="overflow-hidden">
            <button id="open-parent" data-action="sheet#open">Open sheet</button>
            <div data-sheet-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-sheet-target="backdrop"></div>
                <div data-sheet-target="dialog">
                    <div id="confirm" data-controller="alert-dialog" data-alert-dialog-lock-scroll-class="overflow-hidden">
                        <button id="delete" data-action="click->alert-dialog#intercept">Delete</button>
                        <div data-alert-dialog-target="modal" data-state="closed" data-motion="default" hidden inert>
                            <div data-alert-dialog-target="backdrop"></div>
                            <div data-alert-dialog-target="dialog">
                                <button data-action="alert-dialog#cancel">Cancel</button>
                                <button data-action="alert-dialog#confirm">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("sheet", window.SheetController);
        window.StimulusApplication.register("alert-dialog", window.AlertDialogController);
    });

    const parentOverlay = page.locator('#parent > [data-sheet-target="modal"]');
    const alertOverlay = page.locator('#confirm [data-alert-dialog-target="modal"]');

    await page.locator("#open-parent").click();
    await expect(parentOverlay).toHaveAttribute("data-state", "open");

    const tabPrevented = await page.locator("#delete").evaluate((trigger) => {
        trigger.click();
        const tab = new KeyboardEvent("keydown", { key: "Tab", shiftKey: true, bubbles: true, cancelable: true });
        trigger.dispatchEvent(tab);
        trigger.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));

        return tab.defaultPrevented;
    });

    expect(tabPrevented).toBe(true);
    await expect(alertOverlay).toHaveAttribute("data-state", "closed");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
    await expect(parentOverlay).toHaveAttribute("data-state", "open");
    await expect(parentOverlay).not.toHaveAttribute("hidden", "");

    await page.locator("#delete").click();
    await expect(alertOverlay).toHaveAttribute("data-state", "open");
    await expect(alertOverlay).not.toHaveAttribute("data-presence", "entering");

    const exitTabPrevented = await page.evaluate(() => {
        document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
        const trigger = document.querySelector("#delete");
        const tab = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
        trigger.dispatchEvent(tab);

        return tab.defaultPrevented;
    });

    expect(exitTabPrevented).toBe(true);
    await expect(alertOverlay).toHaveAttribute("data-state", "closed");
    await expect(alertOverlay).toHaveAttribute("hidden", "");
});

test("a modal nested in another modal animates after entering the top layer", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
            [data-slot="modal-overlay"][data-state="open"] { pointer-events: auto; }
            [data-hotwire-top-layer][popover][data-slot="modal-overlay"] { margin: 0; width: 100vw; height: 100dvh; max-width: none; max-height: none; border: 0; padding: 0; background: transparent; overflow: visible; }
            [data-slot="modal-positioner"] { position: relative; z-index: 1; opacity: 0; transform: scale(.8); transition: opacity 400ms linear, transform 400ms linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; transform: scale(1); }
            [data-slot="modal-overlay"][data-motion="none"] > [data-slot="modal-positioner"] { transition: none !important; }
        </style>
        <div id="outer" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open-outer" data-action="modal#open">Open outer</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog">
                    <div id="inner" data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
                        <button id="open-inner" data-action="modal#open">Open inner</button>
                        <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                            <div data-modal-target="backdrop"></div>
                            <div data-slot="modal-positioner" data-modal-target="dialog">
                                <button id="close-inner" data-action="modal#close">Close inner</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open-outer").click();
    await page.locator("#open-inner").click();

    const innerOverlay = page.locator("#inner > [data-modal-target='modal']");
    const innerDialog = page.locator("#inner > [data-modal-target='modal'] > [data-modal-target='dialog']");

    await expect(innerOverlay).toHaveAttribute("data-state", "open");
    await expect.poll(async () => innerDialog.evaluate((element) => element.getAnimations().some((animation) => animation.playState === "running"))).toBe(true);
    await expect.poll(async () => innerDialog.evaluate((element) => parseFloat(getComputedStyle(element).opacity))).toBeLessThan(1);
});

test("rapid reopen cancels stale modal exit teardown", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; }
            [data-slot="modal-positioner"] { opacity: 0; transition: opacity 300ms linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; }
        </style>
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog"><button data-action="modal#close">Close</button></div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open").click();
    const modal = page.locator('[data-modal-target="modal"]');
    await expect(modal).toHaveAttribute("data-state", "open");
    await page.waitForTimeout(350);

    await page.evaluate(() => {
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        controller.close();
        const modal = document.querySelector('[data-modal-target="modal"]');
        const replacement = modal.cloneNode(true);
        replacement.removeAttribute("data-presence");
        replacement.querySelector("button").textContent = "Morphed close";
        window.Turbo.morphElements(modal, replacement);
        window.presenceDuringMorph = modal.dataset.presence;
        controller.open({ currentTarget: document.querySelector("#open") });
    });
    await page.waitForTimeout(350);

    expect(await page.evaluate(() => window.presenceDuringMorph)).toBe("leaving");
    await expect(modal).toContainText("Morphed close");
    await expect(modal).toHaveAttribute("data-state", "open");
    await expect(modal).not.toHaveAttribute("hidden", "");
    await expect(modal).not.toHaveAttribute("inert", "");
    await expect(page.locator("body")).toHaveClass(/overflow-hidden/);
});

test("Turbo cache synchronously closes a modal during motion", async ({ page }) => {
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; }
            [data-slot="modal-positioner"] { opacity: 0; transition: opacity 10s linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; }
        </style>
        <div
            data-controller="modal"
            data-action="turbo:before-cache@window->modal#closeForCache"
            data-modal-lock-scroll-class="overflow-hidden"
        >
            <button id="open" data-action="modal#open">Open</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
    });

    await page.locator("#open").click();
    const modal = page.locator('[data-modal-target="modal"]');
    const dialog = page.locator('[data-modal-target="dialog"]');
    await expect(modal).toHaveAttribute("data-state", "open");
    await expect.poll(async () => dialog.evaluate((element) => element.getAnimations().some((animation) => (animation.currentTime ?? 0) > 100))).toBe(true);

    const cachedState = await page.evaluate(() => {
        window.dispatchEvent(new CustomEvent("turbo:before-cache"));
        const element = document.querySelector('[data-modal-target="modal"]');

        return {
            state: element.dataset.state,
            hidden: element.hidden,
            inert: element.hasAttribute("inert"),
            topLayer: element.matches(":popover-open"),
            scrollLocked: document.body.classList.contains("overflow-hidden"),
        };
    });

    expect(cachedState).toEqual({
        state: "closed",
        hidden: true,
        inert: true,
        topLayer: false,
        scrollLocked: false,
    });
});

test("reduced motion closes a modal without waiting for CSS motion", async ({ page }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.setContent(`
        <style>
            [hidden] { display: none !important; }
            [data-slot="modal-overlay"] { position: fixed; inset: 0; display: flex; }
            [data-slot="modal-positioner"] { opacity: 0; transition: opacity 10s linear; }
            [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] { opacity: 1; }
            @media (prefers-reduced-motion: reduce) { [data-slot="modal-positioner"] { transition: none !important; animation: none !important; } }
        </style>
        <div data-controller="modal" data-modal-lock-scroll-class="overflow-hidden">
            <button id="open" data-action="modal#open">Open</button>
            <div data-slot="modal-overlay" data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-slot="modal-positioner" data-modal-target="dialog"><button>Close</button></div>
            </div>
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript("resources/js/controllers/modal_controller.js") });
    await page.evaluate(async () => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        await new Promise((resolve) => setTimeout(resolve, 0));
        const root = document.querySelector('[data-controller~="modal"]');
        const controller = window.StimulusApplication.getControllerForElementAndIdentifier(root, "modal");
        await controller.open({ currentTarget: document.querySelector("#open") });
        await controller.close();
    });

    const modal = page.locator('[data-modal-target="modal"]');
    await expect(modal).toHaveAttribute("data-state", "closed");
    await expect(modal).toHaveAttribute("hidden", "");
    await expect.poll(async () => page.locator('[data-modal-target="dialog"]').evaluate((element) => element.getAnimations().length)).toBe(0);
});

async function browserControllerScript(path) {
    // Inline helper modules alongside the controller — ES `import` is not valid
    // inside a regular <script>, so the harness concatenates the source instead.
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");

    const focusTrap = (await readFile("resources/js/controllers/_focus_trap.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export class FocusTrap", "class FocusTrap");

    const overlayStack = (await readFile("resources/js/controllers/_overlay_stack.js", "utf8"))
        .replace("export function registerOverlay", "function registerOverlay")
        .replace("export function unregisterOverlay", "function unregisterOverlay")
        .replace("export function activateTopOverlay", "function activateTopOverlay")
        .replace("export function isTopOverlay", "function isTopOverlay")
        .replace("export function overlayPosition", "function overlayPosition");

    const topLayer = (await readFile("resources/js/controllers/_top_layer.js", "utf8"))
        .replace("export function createTopLayer", "function createTopLayer");

    const presence = (await readFile("resources/js/controllers/_presence.js", "utf8"))
        .replace("export function createPresence", "function createPresence");

    const overlay = (await readFile("resources/js/controllers/_overlay.js", "utf8"))
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay_stack\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_presence\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_top_layer\.js";\s*/, "")
        .replace("export function createOverlay", "function createOverlay");

    const frameOverlay = (await readFile("resources/js/controllers/_frame_overlay.js", "utf8"))
        .replace("export function createFrameOverlay", "function createFrameOverlay");

    const source = (await readFile(path, "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_focus_trap\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace("export default class ModalController extends Controller", "class ModalController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${composition}
        ${focusTrap}
        ${overlayStack}
        ${topLayer}
        ${presence}
        ${overlay}
        ${frameOverlay}
        ${source}
        window.ModalController = ModalController;
    `;
}

async function browserOverlayControllerScript() {
    const base = await browserControllerScript("resources/js/controllers/modal_controller.js");
    const actionReplay = (await readFile("resources/js/controllers/_action_replay.js", "utf8"))
        .replace(/^export /gm, "");
    const alertDialog = (await readFile("resources/js/controllers/alert_dialog_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_action_replay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace("export default class AlertDialogController extends Controller", "class AlertDialogController extends Controller");
    const drawer = (await readFile("resources/js/controllers/drawer_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_overlay\.js";\s*/, "")
        .replace(/import \{[^}]*\} from "\.\/_frame_overlay\.js";\s*/, "")
        .replace("export default class DrawerController extends Controller", "class DrawerController extends Controller");
    const sheet = (await readFile("resources/js/controllers/sheet_controller.js", "utf8"))
        .replace('import DrawerController from "./drawer_controller.js";', "")
        .replace("export default class SheetController extends DrawerController", "class SheetController extends DrawerController");

    return `
        ${base.replace("window.ModalController = ModalController;", "")}
        ${actionReplay}
        ${alertDialog}
        ${drawer}
        ${sheet}
        window.ModalController = ModalController;
        window.AlertDialogController = AlertDialogController;
        window.DrawerController = DrawerController;
        window.SheetController = SheetController;
    `;
}

async function mountFrameOverlay(page, family, {
    beforeOverlay = "",
    loadingTemplate = "",
    overlayAttributes = "",
} = {}) {
    await page.setContent(`
        <div
            id="${family}-shell"
            data-controller="${family}"
            data-${family}-lock-scroll-class="overflow-hidden"
        >
            ${beforeOverlay}
            <div
                data-hotwire-overlay-labels
                data-slot="${family}-overlay"
                data-${family}-target="modal"
                data-state="closed"
                data-motion="none"
                role="dialog"
                ${overlayAttributes}
                hidden inert
            >
                <div data-${family}-target="backdrop"></div>
                <div data-${family}-target="dialog">
                    <turbo-frame id="${family}-frame" data-${family}-target="dynamicContent"></turbo-frame>
                </div>
            </div>
            ${loadingTemplate === "" ? "" : `<template data-${family}-target="loadingTemplate">${loadingTemplate}</template>`}
        </div>
    `);

    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserOverlayControllerScript() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("modal", window.ModalController);
        window.StimulusApplication.register("sheet", window.SheetController);
        window.StimulusApplication.register("drawer", window.DrawerController);
    });
}
