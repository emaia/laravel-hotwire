import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test.use({ reducedMotion: "no-preference" });

test("reveals content through CSS when JavaScript never connects", async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, reducedMotion: "no-preference" });
    const page = await context.newPage();

    try {
        await page.setContent(
            await fixture(
                `
            <section data-slot="reveal" data-controller="reveal" data-reveal-children>
                <article id="css-only">Content</article>
            </section>
        `,
                "300ms",
                "1000ms",
            ),
        );

        await expect(page.locator("#css-only")).toHaveCSS("opacity", "0");
        await expect(page.locator("#css-only")).toHaveCSS("opacity", "1", { timeout: 2000 });
        await expect(page.locator("#css-only")).toBeVisible();
    } finally {
        await context.close();
    }
});

test("raw controller markup receives Nova rise motion", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");
    const nova = await readFile("resources/css/presets/nova.css", "utf8");

    await page.setContent(`
        <style>${structural}\n${nova}</style>
        <section data-controller="reveal">
            <article id="raw" data-reveal-item>Raw markup</article>
        </section>
    `);

    await expect(page.locator("#raw")).toHaveCSS("animation-name", "hotwire-reveal-rise");
    await expect(page.locator("#raw")).toHaveCSS("animation-fill-mode", "backwards");
});

test("structural fallback keeps animation name and fill mode unambiguous", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");

    await page.setContent(`
        <style>${structural}</style>
        <section data-controller="reveal">
            <article id="fallback" data-reveal-item>Fallback</article>
        </section>
    `);

    await expect(page.locator("#fallback")).toHaveCSS("animation-name", "hotwire-reveal-rise");
    await expect(page.locator("#fallback")).toHaveCSS("animation-fill-mode", "backwards");
});

test("Sidebar Reveal integration receives its selected Nova motion", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");
    const nova = await readFile("resources/css/presets/nova.css", "utf8");

    await page.setContent(`
        <style>${structural}\n${nova}</style>
        <div data-slot="sidebar-container" data-controller="reveal" data-motion="flat">
            <div id="sidebar-item" data-reveal-item>Navigation</div>
        </div>
    `);

    await expect(page.locator("#sidebar-item")).toHaveCSS("animation-name", "hotwire-reveal-flat");
});

test("data-reveal=off neutralises the cascade without touching the markup", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section data-slot="reveal" data-controller="reveal">
            <article id="item" data-reveal-item>
                Content
                <span data-slot="progress-indicator"></span>
            </article>
            <article id="armed" data-reveal-item data-reveal-armed>Waiting for a scroll</article>
        </section>
    `,
            "300ms",
        ),
    );

    // The fixture overrides --reveal-animation, so this is the cascade running at all.
    await expect(page.locator("#item")).toHaveCSS("animation-name", "test-reveal");
    await expect(page.locator("#item span")).toHaveCSS("animation-name", "hotwire-reveal-progress");

    await page.evaluate(() => document.documentElement.setAttribute("data-reveal", "off"));

    await expect(page.locator("#item")).toHaveCSS("animation-name", "none");
    await expect(page.locator("#item span")).toHaveCSS("animation-name", "none");
    // Silencing the motion alone would leave an armed item hidden, waiting on a scroll that no
    // longer reveals anything.
    await expect(page.locator("#armed")).toHaveCSS("opacity", "1");
});

test("names the Sidebar for the transition only while Turbo is rendering", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");

    await page.setContent(`
        <style>${structural}</style>
        <div data-slot="sidebar-container" data-side="left" data-controller="reveal" data-reveal-scope="document">
            <div id="nav" data-reveal-item>Navigation</div>
        </div>
    `);
    await installController(page);

    const sidebar = page.locator('[data-slot="sidebar-container"]');

    // A view transition name is global. Left on, the chrome becomes its own layer in transitions
    // that are not navigation — a theme toggle, say — and animates apart from the root.
    await expect(sidebar).toHaveCSS("view-transition-name", "none");

    await page.evaluate(() => document.dispatchEvent(new CustomEvent("turbo:before-render")));
    await expect(sidebar).toHaveCSS("view-transition-name", "hotwire-sidebar-left");

    await page.evaluate(() => document.dispatchEvent(new CustomEvent("turbo:load")));
    await expect(sidebar).toHaveCSS("view-transition-name", "none");
});

test("drops the render marker when a render never lands", async ({ page }) => {
    const structural = await readFile("resources/css/structural.css", "utf8");

    await page.setContent(`
        <style>${structural}</style>
        <div data-slot="sidebar-container" data-side="left" data-controller="reveal" data-reveal-scope="document">
            <div id="nav" data-reveal-item>Navigation</div>
        </div>
    `);
    await installController(page);

    const sidebar = page.locator('[data-slot="sidebar-container"]');

    await page.evaluate(() => document.dispatchEvent(new CustomEvent("turbo:before-render")));
    await expect(sidebar).toHaveCSS("view-transition-name", "hotwire-sidebar-left");

    // Left set by an aborted visit, the marker would keep the chrome named for every later
    // transition — the very thing it exists to prevent.
    await expect(sidebar).toHaveCSS("view-transition-name", "none", { timeout: 4000 });
});

test("collapsed desktop Sidebar labels suppress Reveal without changing expanded or mobile labels", async ({
    page,
}) => {
    const structural = await readFile("resources/css/structural.css", "utf8");
    const nova = await readFile("resources/css/presets/nova.css", "utf8");

    await page.setViewportSize({ width: 1024, height: 768 });
    await page.setContent(`
        <style>${structural}\n${nova}</style>
        <div data-slot="sidebar" data-collapsible="icon">
            <div id="collapsed-label" data-slot="sidebar-group-label" data-reveal-item>Collapsed</div>
        </div>
        <div data-slot="sidebar" data-collapsible="">
            <div id="expanded-label" data-slot="sidebar-group-label" data-reveal-item>Expanded</div>
        </div>
    `);

    await expect(page.locator("#collapsed-label")).toHaveCSS("animation-name", "none");
    await expect(page.locator("#expanded-label")).toHaveCSS("animation-name", "hotwire-reveal-rise");

    await page.setViewportSize({ width: 600, height: 768 });
    await expect(page.locator("#collapsed-label")).toHaveCSS("animation-name", "hotwire-reveal-rise");
});

test("backwards fill releases transform and filter after the cascade", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section data-slot="reveal" data-controller="reveal">
            <article id="host" data-reveal-item style="margin-top: 200px">
                <div id="fixed" style="position: fixed; top: 0; left: 0">Fixed</div>
            </article>
        </section>
    `,
            "120ms",
        ),
    );
    await installController(page);

    await expect(page.locator("#host")).toHaveCSS("transform", "none", { timeout: 1000 });
    await expect(page.locator("#host")).toHaveCSS("filter", "none");
    await expect.poll(() => page.locator("#fixed").evaluate((element) => element.getBoundingClientRect().top)).toBe(0);
});

test("fade motion preserves fixed descendants during its delay and animation", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section data-slot="reveal" data-controller="reveal" style="--reveal-animation: test-fade">
            <article id="host" data-reveal-item style="margin-top: 200px">
                <div id="fixed" style="position: fixed; top: 0; left: 0">Fixed</div>
            </article>
        </section>
    `,
            "300ms",
            "300ms",
            "@keyframes test-fade { from { opacity: 0 } to { opacity: 1 } }",
        ),
    );
    await installController(page);

    await page.waitForTimeout(400);
    await expect(page.locator("#host")).toHaveCSS("transform", "none");
    await expect(page.locator("#host")).toHaveCSS("filter", "none");
    expect(await page.locator("#fixed").evaluate((element) => element.getBoundingClientRect().top)).toBe(0);
});

test("scroll trigger observes each offscreen item and settles after release", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section id="reveal" data-slot="reveal" data-controller="reveal"
            data-reveal-trigger-value="scroll" data-reveal-root-margin-value="0px">
            <article id="visible" data-reveal-item>Visible</article>
            <div style="height: 1300px"></div>
            <article id="pending" data-reveal-item>Pending</article>
        </section>
    `,
            "120ms",
        ),
    );
    await installController(page);

    await expect(page.locator("#visible")).not.toHaveAttribute("data-reveal-armed", "");
    await expect(page.locator("#pending")).toHaveAttribute("data-reveal-armed", "");
    await expect(page.locator("#pending")).toHaveCSS("opacity", "0");

    await page.locator("#pending").scrollIntoViewIfNeeded();

    await expect(page.locator("#pending")).not.toHaveAttribute("data-reveal-armed", "");
    await expect(page.locator("#pending")).toHaveCSS("opacity", "1", { timeout: 1000 });
    await expect(page.locator("#reveal")).toHaveAttribute("data-reveal-state", "done");
});

test("scroll trigger releases an item taller than the viewport", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <div style="height: 120vh"></div>
        <section id="reveal" data-slot="reveal" data-controller="reveal"
            data-reveal-trigger-value="scroll" data-reveal-root-margin-value="0px">
            <article id="tall" data-reveal-item style="height: 700vh">Tall content</article>
        </section>
        <div style="height: 120vh"></div>
    `,
            "120ms",
        ),
    );
    await installController(page);

    await expect(page.locator("#tall")).toHaveAttribute("data-reveal-armed", "");
    await page.locator("#tall").evaluate((element) => element.scrollIntoView());

    await expect(page.locator("#tall")).not.toHaveAttribute("data-reveal-armed", "", { timeout: 2000 });
    await expect(page.locator("#tall")).toHaveCSS("opacity", "1", { timeout: 1000 });
});

test("settle ignores infinite descendant animations", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section id="reveal" data-slot="reveal" data-controller="reveal">
            <article data-reveal-item>
                <span style="animation: pulse 20ms infinite alternate">Loading</span>
            </article>
        </section>
    `,
            "120ms",
            "0ms",
            "@keyframes pulse { from { opacity: .8 } to { opacity: 1 } }",
        ),
    );
    await installController(page);

    await expect(page.locator("#reveal")).toHaveAttribute("data-reveal-state", "done", { timeout: 1000 });
    expect(
        await page
            .locator("#reveal span")
            .evaluate((element) =>
                element
                    .getAnimations()
                    .some((animation) => animation.effect.getComputedTiming().iterations === Infinity),
            ),
    ).toBe(true);
});

test("settle waits for an initially hidden container to render", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section id="reveal" data-slot="reveal" data-controller="reveal" hidden>
            <article data-reveal-item>Deferred</article>
        </section>
    `,
            "180ms",
        ),
    );
    await installController(page);

    await page.waitForTimeout(700);
    await expect(page.locator("#reveal")).not.toHaveAttribute("data-reveal-state", "done");
    await page.locator("#reveal").evaluate((element) => {
        element.hidden = false;
    });

    await expect(page.locator("#reveal")).toHaveAttribute("data-reveal-state", "done", { timeout: 1000 });
});

test("settle waits for CSS visibility to expose the container", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
        <section id="reveal" data-slot="reveal" data-controller="reveal" style="visibility: hidden">
            <article data-reveal-item>Deferred</article>
        </section>
    `,
            "180ms",
        ),
    );
    await installController(page);

    await page.waitForTimeout(700);
    await expect(page.locator("#reveal")).not.toHaveAttribute("data-reveal-state", "done");
    await page.locator("#reveal").evaluate((element) => {
        element.style.visibility = "visible";
    });

    await expect(page.locator("#reveal")).toHaveAttribute("data-reveal-state", "done", { timeout: 1000 });
});

test("nested render scope remains independent from booted document chrome", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
            <section data-slot="reveal" data-controller="reveal" data-reveal-scope="document">
                <article data-reveal-item>Chrome</article>
                <section data-slot="reveal" data-controller="reveal" data-reveal-scope="render">
                    <article id="inner" data-reveal-item>Content</article>
                </section>
            </section>
        `,
            "300ms",
        ),
    );
    await page.locator("html").evaluate((element) => {
        element.dataset.revealBooted = "";
    });

    await expect(page.locator("#inner")).toHaveCSS("animation-name", "test-reveal");
});

test("direct-child document chrome does not suppress a nested render cascade", async ({ page }) => {
    await page.setContent(
        await fixture(
            `
            <section data-slot="reveal" data-controller="reveal" data-reveal-children
                data-reveal-scope="document" data-reveal-state="done">
                <section data-slot="reveal" data-controller="reveal" data-reveal-scope="render">
                    <article id="inner" data-reveal-item>
                        <span id="progress" data-slot="progress-indicator" style="--progress-value: 75%"></span>
                    </article>
                </section>
            </section>
        `,
            "300ms",
        ),
    );
    await page.locator("html").evaluate((element) => {
        element.dataset.revealBooted = "";
    });

    await expect(page.locator("#inner")).toHaveCSS("animation-name", "test-reveal");
    await expect(page.locator("#inner")).toHaveCSS("--reveal-stagger", "20ms");
    await expect(page.locator("#progress")).toHaveCSS("animation-name", "hotwire-reveal-progress");
});

test.describe("reduced motion", () => {
    test.use({ reducedMotion: "reduce" });

    test("keeps offscreen scroll items visible and unarmed", async ({ page }) => {
        await page.emulateMedia({ reducedMotion: "reduce" });
        await page.setContent(
            await fixture(
                `
            <section data-slot="reveal" data-controller="reveal" data-reveal-trigger-value="scroll">
                <div style="height: 1300px"></div>
                <article id="pending" data-reveal-item>Pending</article>
            </section>
        `,
                "300ms",
            ),
        );
        await installController(page);

        await expect(page.locator("#pending")).not.toHaveAttribute("data-reveal-armed", "");
        await expect(page.locator("#pending")).toHaveCSS("animation-name", "none");
        await expect(page.locator("#pending")).toHaveCSS("opacity", "1");
    });
});

async function fixture(markup, duration, delay = "0ms", extraCss = "") {
    const structural = await readFile("resources/css/structural.css", "utf8");

    return `
        <style>
            ${structural}
            @keyframes test-reveal {
                from { opacity: 0; filter: blur(2px); transform: translateY(20px); }
                to { opacity: 1; filter: blur(0); transform: translateY(0); }
            }
            [data-slot="reveal"] {
                --reveal-animation: test-reveal;
                --reveal-duration: ${duration};
                --reveal-delay: ${delay};
                --reveal-stagger: 20ms;
            }
            ${extraCss}
        </style>
        ${markup}
    `;
}

async function installController(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await browserControllerScript() });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("reveal", window.RevealController);
    });
}

async function browserControllerScript() {
    const source = await readFile("resources/js/controllers/reveal_controller.js", "utf8");

    return source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "class RevealController extends Controller")
        .concat("\nwindow.RevealController = RevealController;\n");
}
