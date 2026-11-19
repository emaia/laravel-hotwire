import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { execFile } from "node:child_process";
import { promisify } from "node:util";
import { compileCssFixture, minifyCssWithVite, replacePresetImport } from "../../scripts/css_build_contract.js";

const run = promisify(execFile);
let fixture;

test.beforeAll(async () => {
    fixture = (await run(process.env.PHP_BINARY ?? "php", ["scripts/run_textarea_fixture.php"])).stdout;
});

for (const preset of ["nova", "bloom"]) {
    test.describe(`${preset} Textarea`, () => {
        let css;

        test.beforeAll(async () => {
            css = await minifyCssWithVite(
                await compileCssFixture(
                    `${replacePresetImport(await readFile("stubs/resources/css/app.css", "utf8"), preset)}
                    @source inline("text-end");`,
                ),
            );
        });

        test("contains the counter in one control frame", async ({ page }) => {
            const root = await mount(page, css, "default");
            const { wrapper, textarea, counter } = controls(root);
            expect((await surface(wrapper)).border).toBeGreaterThan(0);
            expect((await surface(textarea)).border).toBe(0);
            await expectContainedCounter(wrapper, textarea, counter);
        });

        test("keeps live countdown and auto-resize inside the frame", async ({ page }) => {
            const root = await mount(page, css, "auto");
            const { wrapper, textarea, counter } = controls(root);
            await installControllers(page);
            const live = root.locator('[data-char-counter-target="counter"]');
            await expect(live).toHaveText("996");
            const initialHeight = (await textarea.boundingBox()).height;
            const value = "Another line\n".repeat(30);
            await textarea.fill(value);
            await expect(live).toHaveText(String(1000 - value.length));
            expect((await textarea.boundingBox()).height).toBeGreaterThan(initialHeight);
            await expectContainedCounter(wrapper, textarea, counter);
            await textarea.fill("");
            await expect(live).toHaveText("1,000");
            await expect.poll(async () => (await textarea.boundingBox()).height).toBeLessThan(initialHeight + 1);
            await expectContainedCounter(wrapper, textarea, counter);
        });

        test("puts keyboard focus and invalid treatment on the counted surface, not two frames", async ({ page }) => {
            const root = await mount(page, css, "default");
            const { wrapper, textarea } = controls(root);
            const before = await surface(wrapper);
            await textarea.focus();
            await expect(textarea).toBeFocused();
            expect(changedIndicator(before, await surface(wrapper))).toBe(true);
            expect((await surface(textarea)).outline).toBe(0);
            expect((await surface(textarea)).shadow).toBe("");
            await textarea.blur();
            const valid = await surface(wrapper);
            await textarea.evaluate((element) => element.setAttribute("aria-invalid", "true"));
            await expect.poll(async () => changedIndicator(valid, await surface(wrapper))).toBe(true);
            expect((await surface(textarea)).shadow).toBe("");
        });

        test("preserves plain Textarea focus and applies disabled opacity only once", async ({ page }) => {
            let root = await mount(page, css, "plain");
            await expect(root.locator('[data-slot="textarea-wrapper"]')).toHaveCount(0);
            const plain = root.locator("textarea");
            const before = await surface(plain);
            await plain.focus();
            expect(changedIndicator(before, await surface(plain))).toBe(true);
            root = await mount(page, css, "plain-disabled");
            const disabledOpacity = (await surface(root.locator("textarea"))).opacity;
            root = await mount(page, css, "disabled");
            const { wrapper, textarea } = controls(root);
            await expect(textarea).toBeDisabled();
            expect((await surface(wrapper)).opacity).toBe(disabledOpacity);
            expect((await surface(textarea)).opacity).toBe(1);
        });

        test("lets InputGroup own the border and states in light and dark themes", async ({ page }) => {
            for (const theme of ["light", "dark"]) {
                const root = await mount(page, css, "input-group");
                await page.locator("html").evaluate((element, value) => (element.dataset.theme = value), theme);
                const group = root.locator('[data-slot="input-group"]');
                const { wrapper, textarea, counter } = controls(root);
                expect((await surface(group)).border).toBeGreaterThan(0);
                expect((await surface(wrapper)).border).toBe(0);
                expect((await surface(textarea)).border).toBe(0);
                await expect(wrapper).toHaveCSS("background-color", "rgba(0, 0, 0, 0)");
                const outerBefore = await surface(group);
                const innerBefore = await surface(wrapper);
                await textarea.focus();
                expect(changedIndicator(outerBefore, await surface(group))).toBe(true);
                expect(changedIndicator(innerBefore, await surface(wrapper))).toBe(false);
                await textarea.evaluate((element) => {
                    element.setAttribute("aria-invalid", "true");
                    element.disabled = true;
                });
                expect((await surface(group)).opacity).toBeLessThan(1);
                expect((await surface(wrapper)).opacity).toBe(1);
                expect((await surface(textarea)).opacity).toBe(1);
                expect((await surface(wrapper)).shadow).toBe(innerBefore.shadow);
                await expectContainedCounter(wrapper, textarea, counter);
            }
        });

        test("retains standalone counted Textarea focus when forced colors suppress shadow rings", async ({ page }) => {
            await page.emulateMedia({ forcedColors: "active" });
            const root = await mount(page, css, "default");
            const { wrapper, textarea } = controls(root);
            await textarea.focus();
            expect((await surface(wrapper)).outline).toBeGreaterThan(0);
        });

        test("wraps long counter content without covering the editor or escaping a narrow RTL surface", async ({
            page,
        }) => {
            await page.setViewportSize({ width: 320, height: 900 });
            for (const direction of ["ltr", "rtl"]) {
                const root = await mount(page, css, "long-counter");
                await root.evaluate((element, value) => (element.dir = value), direction);
                const { wrapper, textarea, counter } = controls(root);
                await expectContainedCounter(wrapper, textarea, counter);
                const wrapping = await counter.evaluate((element) => ({
                    height: element.clientHeight,
                    lineHeight: parseFloat(getComputedStyle(element).lineHeight),
                    overflow: element.scrollWidth > element.clientWidth,
                }));
                expect(wrapping.height).toBeGreaterThan(wrapping.lineHeight);
                expect(wrapping.overflow).toBe(false);
                expect(await page.locator("body").evaluate((element) => element.scrollWidth)).toBeLessThanOrEqual(320);
            }
        });

        test("keeps the footer in flow after a native control resize inside a dialog", async ({ page }) => {
            const root = await mount(page, css, "default");
            await root.evaluate((section) => {
                const dialog = document.createElement("dialog");
                dialog.style.width = "min(90vw, 36rem)";
                section.replaceWith(dialog);
                dialog.append(section);
                dialog.showModal();
            });
            const { wrapper, textarea, counter } = controls(root);
            await textarea.evaluate((element) => (element.style.height = "240px"));
            await expectContainedCounter(wrapper, textarea, counter);
            await expect(textarea).toHaveCSS("resize", "vertical");
        });

        test("allows later component-layer overrides without scope proximity or important", async ({ page }) => {
            const root = await mount(page, css, "default");
            await page.addStyleTag({
                content: `@layer components {
                [data-slot="textarea-wrapper"] { border-width: 3px; }
                [data-slot="textarea-counter"] { margin-block-start: 12px; }
            }`,
            });
            const { wrapper, textarea, counter } = controls(root);
            expect((await surface(wrapper)).border).toBe(3);
            await expect(counter).toHaveCSS("margin-block-start", "12px");
            await counter.evaluate((element) => element.classList.add("text-end"));
            await expect(counter).toHaveCSS("text-align", "end");
            await expectContainedCounter(wrapper, textarea, counter);
        });

        test("aligns editor and footer content with inline addons in either direction", async ({ page }) => {
            for (const direction of ["ltr", "rtl"]) {
                for (const align of ["inline-start", "inline-end"]) {
                    for (const kind of ["text", "icon"]) {
                        const root = await mount(page, css, `${align}-${kind}`);
                        await root.evaluate((element, value) => (element.dir = value), direction);
                        const { textarea, counter } = controls(root);
                        const padding = (element) => {
                            const style = getComputedStyle(element);
                            return [style.paddingInlineStart, style.paddingInlineEnd];
                        };
                        expect(await counter.evaluate(padding)).toEqual(await textarea.evaluate(padding));
                    }
                }
            }
        });

        test("keeps footer text selectable without refocusing the editor", async ({ page }) => {
            for (const interaction of ["double-click", "drag", "padding"]) {
                await page.goto("about:blank");
                const root = await mount(page, css, "auto");
                const { textarea, counter } = controls(root);
                await installControllers(page);
                await textarea.focus();
                await textarea.press("End");
                await page.keyboard.type(" edited");
                await textarea.evaluate((element) => {
                    window.footerRefocuses = 0;
                    element.addEventListener("focus", () => window.footerRefocuses++);
                });

                if (interaction === "padding") {
                    const box = await counter.boundingBox();
                    await counter.click({ position: { x: box.width - 3, y: box.height - 3 } });
                } else {
                    const text = interaction === "double-click" ? "characters" : "characters left";
                    const box = await counter.evaluate((element, text) => {
                        const node = [...element.childNodes].find(
                            (node) => node.nodeType === Node.TEXT_NODE && node.textContent.includes(text),
                        );
                        const start = node.textContent.indexOf(text);
                        const range = document.createRange();
                        range.setStart(node, start);
                        range.setEnd(node, start + text.length);
                        const rect = range.getBoundingClientRect();
                        return { x: rect.x, y: rect.y, width: rect.width, height: rect.height };
                    }, text);
                    const y = box.y + box.height / 2;
                    if (interaction === "double-click") {
                        await page.mouse.dblclick(box.x + box.width / 2, y);
                    } else {
                        await page.mouse.move(box.x + 1, y);
                        await page.mouse.down();
                        await page.mouse.move(box.x + box.width - 1, y, { steps: 12 });
                        await page.mouse.up();
                    }
                    expect(await page.evaluate(() => getSelection().toString())).toBe(text);
                }

                await expect(textarea).not.toBeFocused();
                expect(await page.evaluate(() => window.footerRefocuses)).toBe(0);
            }
        });
    });
}

async function mount(page, css, name) {
    await page.setContent(`<head><style>${css}</style></head><body>${fixture}</body>`);
    await page
        .locator("body")
        .evaluate((body, value) => body.replaceChildren(body.querySelector(`[data-case="${value}"]`)), name);
    return page.locator(`[data-case="${name}"]`);
}

function controls(root) {
    const wrapper = root.locator('[data-slot="textarea-wrapper"]');
    return {
        wrapper,
        textarea: wrapper.locator('[data-slot="textarea"]'),
        counter: wrapper.locator('[data-slot="textarea-counter"]'),
    };
}

async function expectContainedCounter(wrapper, textarea, counter) {
    const [frame, editor, footer] = await Promise.all([
        wrapper.boundingBox(),
        textarea.boundingBox(),
        counter.boundingBox(),
    ]);
    expect(footer.y).toBeGreaterThanOrEqual(bottom(editor));
    expect(bottom(footer)).toBeLessThanOrEqual(bottom(frame));
    expect(footer.x).toBeGreaterThanOrEqual(frame.x);
    expect(footer.x + footer.width).toBeLessThanOrEqual(frame.x + frame.width);
}

async function surface(locator) {
    return locator.evaluate((element) => {
        const css = getComputedStyle(element);
        // Zero-width Tailwind shadow layers can change color without painting another focus ring.
        const shadow = (css.boxShadow.replace(/[a-z]+\([^)]*\)/gi, "").match(/-?[\d.]+px/g) ?? [])
            .map(parseFloat)
            .filter((value) => value !== 0)
            .join(",");
        return {
            border: parseFloat(css.borderTopWidth),
            outline: css.outlineStyle === "none" ? 0 : parseFloat(css.outlineWidth),
            shadow,
            opacity: parseFloat(css.opacity),
        };
    });
}

function changedIndicator(before, after) {
    return before.shadow !== after.shadow || before.outline !== after.outline;
}

function bottom(box) {
    return box.y + box.height;
}

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    for (const [file, name] of [
        ["char_counter", "CharacterCounter"],
        ["auto_resize", "AutoResize"],
    ]) {
        const source = (await readFile(`resources/js/controllers/${file}_controller.js`, "utf8"))
            .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
            .replace("export default class extends Controller", `window.${name} = class extends Controller`);
        await page.addScriptTag({ content: `(() => { ${source} })();` });
    }
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("char-counter", window.CharacterCounter);
        window.app.register("auto-resize", window.AutoResize);
    });
    await page.waitForFunction(() =>
        ["char-counter", "auto-resize"].every((identifier) =>
            [...document.querySelectorAll(`[data-controller~="${identifier}"]`)].every((element) =>
                window.app.getControllerForElementAndIdentifier(element, identifier),
            ),
        ),
    );
}
