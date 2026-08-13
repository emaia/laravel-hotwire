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
