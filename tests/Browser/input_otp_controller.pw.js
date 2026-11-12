import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture, packageSourceEntrypoint } from "../../scripts/css_build_contract.js";

const css = {};
test.beforeAll(async () => {
    for (const preset of ["nova", "bloom"]) {
        css[preset] = await compileCssFixture(packageSourceEntrypoint(`../../vendor/emaia/laravel-hotwire/resources/css/presets/${preset}.css`));
    }
});

async function mount(page, preset = "nova", enhance = true) {
    await page.setContent(`
        <style>${css[preset]}</style>
        <form id="challenge">
            <label for="code">Authenticator code</label>
            <span data-slot="input-otp" data-controller="input-otp" data-action="turbo:morph-element->input-otp#refresh">
                <input id="code" name="code" type="text" maxlength="6" minlength="6" pattern="[0-9]{6}"
                    required autocomplete="one-time-code" inputmode="numeric" data-slot="input-otp-input"
                    data-input-otp-target="input"
                    data-action="input->input-otp#input compositionend->input-otp#input paste->input-otp#paste focus->input-otp#render blur->input-otp#render pointerdown->input-otp#placeCaret pointermove->input-otp#extendSelection pointerup->input-otp#endSelection pointercancel->input-otp#endSelection keyup->input-otp#render select->input-otp#render">
                <span data-slot="input-otp-slots" data-input-otp-target="slots" aria-hidden="true" hidden dir="ltr">
                    ${Array.from({ length: 6 }, () => '<span data-slot="input-otp-slot" data-input-otp-target="slot"></span>').join("")}
                </span>
            </span>
            <button type="reset">Reset</button>
            <button>Verify</button>
        </form>
    `);
    if (!enhance) return;
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    const source = await readFile("resources/js/controllers/input_otp_controller.js", "utf8");
    await page.addScriptTag({ content: source
        .replace('import { Controller } from "@hotwired/stimulus";', "const { Controller } = window.Stimulus;")
        .replace("export default class extends Controller", "window.InputOtpController = class extends Controller") });
    await page.evaluate(() => {
        window.app = window.Stimulus.Application.start();
        window.app.register("input-otp", window.InputOtpController);
        window.completions = 0;
        document.addEventListener("input-otp:complete", () => window.completions++);
    });
    await expect(page.locator('[data-slot="input-otp"]')).toHaveAttribute("data-enhanced", "true");
}

for (const preset of ["nova", "bloom"]) {
    test(`${preset}: invalid Field controls keep their hit area and error outline within the digit row`, async ({ page }) => {
        await mount(page, preset);
        await page.locator('[data-slot="input-otp"]').evaluate(node => {
            const field = document.createElement("div");
            field.dataset.slot = "field";
            field.style.width = "600px";
            node.before(field);
            field.append(node);
            node.querySelector("input").setAttribute("aria-invalid", "true");
        });
        for (const width of [640, 1280]) {
            await page.setViewportSize({ width, height: 720 });
            for (const orientation of ["vertical", "horizontal", "responsive"]) {
                await page.locator('[data-slot="field"]').evaluate((node, orientation) => node.dataset.orientation = orientation, orientation);
                const bounds = await otpBounds(page.getByRole("textbox"));
                expect(Math.abs(bounds.root - bounds.digits)).toBeLessThanOrEqual(1);
                expect(Math.abs(bounds.input - bounds.digits)).toBeLessThanOrEqual(1);
            }
        }
        await page.locator('[data-slot="field"]').evaluate(node => node.style.width = "100px");
        const bounds = await otpBounds(page.getByRole("textbox"));
        expect(bounds.root).toBeLessThanOrEqual(100);
        expect(Math.abs(bounds.input - bounds.digits)).toBeLessThanOrEqual(1);
    });

    test(`${preset}: native fallback and enhanced focus remain usable`, async ({ page }) => {
        await mount(page, preset, false);
        const input = page.getByRole("textbox", { name: "Authenticator code" });
        await expect(input).toBeVisible();
        await expect(page.locator('[data-slot="input-otp-slots"]')).toBeHidden();
        await input.fill("001234");
        expect(await input.evaluate(node => new FormData(node.form).get("code"))).toBe("001234");

        await mount(page, preset);
        await page.getByText("Authenticator code", { exact: true }).click();
        await expect(input).toBeFocused();
        await expect(page.getByRole("textbox")).toHaveCount(1);
        await expect(page.locator('[data-slot="input-otp-slot"][data-active]')).toHaveCount(1);
        const boxes = await page.locator('[data-slot="input-otp-slot"]').evaluateAll(nodes => nodes.map(node => {
            const r = node.getBoundingClientRect();
            return { x: r.x, y: r.y, width: r.width, height: r.height };
        }));
        expect(boxes.every(box => box.width > 0 && box.height > 0 && box.y === boxes[0].y)).toBe(true);
        expect(boxes[1].x).toBeGreaterThanOrEqual(boxes[0].x + boxes[0].width);
        const overlay = await input.boundingBox();
        expect(overlay.x).toBeLessThanOrEqual(boxes[0].x);
        expect(overlay.x + overlay.width).toBeGreaterThanOrEqual(boxes[5].x + boxes[5].width);

        await page.emulateMedia({ forcedColors: "active" });
        await expect(input).toHaveCSS("opacity", "1");
        await expect(page.locator('[data-slot="input-otp-slots"]')).toBeHidden();
    });
}

async function otpBounds(input) {
    return input.evaluate(node => {
        const root = node.closest('[data-slot="input-otp"]');
        const slots = [...root.querySelectorAll('[data-slot="input-otp-slot"]')];
        return {
            root: root.getBoundingClientRect().width,
            input: node.getBoundingClientRect().width,
            digits: slots.at(-1).getBoundingClientRect().right - slots[0].getBoundingClientRect().left,
        };
    });
}

test("native editing, formatted paste, selection and reset keep slots synchronized", async ({ page }) => {
    await mount(page);
    const input = page.getByRole("textbox");
    await input.focus();
    await page.keyboard.type("001234");
    await expect(input).toHaveValue("001234");
    expect(await page.evaluate(() => window.completions)).toBe(1);
    await page.keyboard.press("Home");
    await page.keyboard.press("ArrowRight");
    await page.keyboard.press("Shift+ArrowRight");
    await expect(page.locator('[data-selected]')).toHaveCount(1);
    await page.keyboard.type("9");
    await expect(input).toHaveValue("091234");
    await page.keyboard.press("Backspace");
    await expect(input).toHaveValue("01234");

    await input.selectText();
    await input.evaluate(node => {
        const clipboardData = new DataTransfer();
        clipboardData.setData("text/plain", "000 123");
        node.dispatchEvent(new ClipboardEvent("paste", { bubbles: true, cancelable: true, clipboardData }));
    });
    await expect(input).toHaveValue("000123");
    await expect(page.locator('[data-slot="input-otp-slots"]')).toHaveText("000123");
    expect(await input.evaluate(node => new FormData(node.form).getAll("code"))).toEqual(["000123"]);

    await page.getByRole("button", { name: "Reset" }).click();
    await expect(input).toHaveValue("");
    await expect(page.locator('[data-slot="input-otp-slots"]')).toHaveText("");
    await input.fill("123");
    expect(await input.evaluate(node => node.checkValidity())).toBe(false);
});

test("reconnect and Turbo morph synchronize state without completing or duplicating listeners", async ({ page }) => {
    await mount(page);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    const root = page.locator('[data-slot="input-otp"]');
    await root.evaluate(node => {
        const next = node.cloneNode(true);
        next.removeAttribute("data-enhanced");
        next.querySelector('[data-input-otp-target="slots"]').hidden = true;
        for (const slot of next.querySelectorAll('[data-input-otp-target="slot"]')) slot.textContent = "";
        next.querySelector("input").setAttribute("value", "009876");
        window.Turbo.morphElements(node, next);
    });
    await expect(root).toHaveAttribute("data-enhanced", "true");
    await expect(page.locator('[data-slot="input-otp-slots"]')).toBeVisible();
    await expect(page.getByRole("textbox")).toHaveValue("009876");
    await expect(page.locator('[data-slot="input-otp-slots"]')).toHaveText("009876");
    expect(await page.evaluate(() => window.completions)).toBe(0);

    await root.evaluate(node => node.removeAttribute("data-controller"));
    await expect(root).not.toHaveAttribute("data-enhanced");
    await root.evaluate(node => node.setAttribute("data-controller", "input-otp"));
    await expect(root).toHaveAttribute("data-enhanced", "true");
    await page.getByRole("textbox").fill("001234");
    expect(await page.evaluate(() => window.completions)).toBe(1);
});

test("clicking a visible digit replaces that digit rather than the hidden input geometry", async ({ page }) => {
    await mount(page);
    const input = page.getByRole("textbox");
    await input.fill("001234");
    const box = await page.locator('[data-slot="input-otp-slot"]').nth(3).boundingBox();
    await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
    await page.keyboard.type("9");
    await expect(input).toHaveValue("001934");
});

test("pointer focus highlights only the intended digit before the click completes", async ({ page }) => {
    await mount(page);
    const input = page.getByRole("textbox");
    await input.fill("001234");
    await page.getByRole("button", { name: "Verify", exact: true }).focus();

    for (const index of [2, 0, 4, 1]) {
        const box = await page.locator('[data-slot="input-otp-slot"]').nth(index).boundingBox();
        await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
        await page.mouse.down();
        try {
            const active = await page.locator('[data-slot="input-otp"]').evaluate(async node => {
                await new Promise(requestAnimationFrame);
                await new Promise(requestAnimationFrame);
                return [...node.querySelectorAll('[data-slot="input-otp-slot"]')]
                    .flatMap((slot, index) => slot.hasAttribute("data-active") ? [index] : []);
            });
            expect(active).toEqual([index]);
        } finally {
            await page.mouse.up();
        }
        expect(await input.evaluate(node => [node.selectionStart, node.selectionEnd])).toEqual([index, index + 1]);
    }
});

test("dragging and Shift-click select native text across visible digits", async ({ page }) => {
    await mount(page);
    const input = page.getByRole("textbox");
    await input.fill("001234");
    const slots = page.locator('[data-slot="input-otp-slot"]');
    const from = await slots.nth(1).boundingBox();
    const to = await slots.nth(4).boundingBox();
    await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
    await page.mouse.down();
    await page.mouse.move(to.x + to.width / 2, to.y + to.height / 2, { steps: 6 });
    await page.mouse.up();
    expect(await input.evaluate(node => [node.selectionStart, node.selectionEnd])).toEqual([1, 5]);
    await expect(page.locator('[data-selected]')).toHaveCount(4);

    const first = await slots.first().boundingBox();
    await page.keyboard.down("Shift");
    await page.mouse.click(first.x + first.width / 2, first.y + first.height / 2);
    await page.keyboard.up("Shift");
    expect(await input.evaluate(node => [node.selectionStart, node.selectionEnd, node.selectionDirection])).toEqual([0, 2, "backward"]);
    await page.keyboard.press("Backspace");
    await expect(input).toHaveValue("1234");
});

test("pointer cancellation and disconnect release active selection capture", async ({ page }) => {
    await mount(page);
    const input = page.getByRole("textbox");
    await input.fill("001234");
    const root = page.locator('[data-slot="input-otp"]');
    const box = await root.boundingBox();
    for (const action of ["cancel", "disconnect"]) {
        await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
        await page.mouse.down();
        const captured = await root.evaluate((node, action) => {
            const controller = window.app.getControllerForElementAndIdentifier(node, "input-otp");
            const { input, id } = controller.pointerSelection;
            const before = input.hasPointerCapture(id);
            if (action === "cancel") input.dispatchEvent(new PointerEvent("pointercancel", { pointerId: id, bubbles: true }));
            else controller.disconnect();
            return { before, after: input.hasPointerCapture(id), selection: controller.pointerSelection };
        }, action);
        await page.mouse.up();
        expect(captured).toEqual({ before: true, after: false, selection: null });
    }
});

test.describe("touch selection", () => {
    test.use({ hasTouch: true });

    test("tapping a digit selects it for replacement without consuming the value", async ({ page }) => {
        await mount(page);
        const input = page.getByRole("textbox");
        await input.fill("001234");
        const box = await page.locator('[data-slot="input-otp-slot"]').nth(2).boundingBox();
        await page.touchscreen.tap(box.x + box.width / 2, box.y + box.height / 2);
        await expect(input).toBeFocused();
        expect(await input.evaluate(node => [node.selectionStart, node.selectionEnd])).toEqual([2, 3]);
        await page.keyboard.type("9");
        await expect(input).toHaveValue("009234");
    });
});
