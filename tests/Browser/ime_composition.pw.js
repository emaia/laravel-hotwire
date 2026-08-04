import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";

test("Auto Submit processes a real IME commit after compositionend", async ({ page }) => {
    await page.setContent(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input id="search" name="search" data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);
    await trackSubmissions(page, "#search");
    await installControllers(page);

    const input = page.locator("#search");
    await input.focus();
    const client = await page.context().newCDPSession(page);

    await setComposition(client, "東京");
    await expect(input).toHaveValue("東京");
    expect(await submissions(page)).toEqual([]);

    await client.send("Input.insertText", { text: "東京" });

    await expect.poll(() => submissions(page)).toEqual([{ value: "東京" }]);
});

test("Money Input formats a real IME commit before Auto Submit runs", async ({ page }) => {
    await page.setContent(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input type="hidden" id="amount-raw">
            <input
                id="amount"
                name="amount"
                data-controller="money-input"
                data-money-input-hidden-id-value="amount-raw"
                data-action="input->auto-submit#debouncedSubmit"
            >
        </form>
    `);
    await trackSubmissions(page, "#amount", "#amount-raw");
    await installControllers(page);
    expect(await submissions(page)).toEqual([]);

    const input = page.locator("#amount");
    await input.focus();
    const client = await page.context().newCDPSession(page);

    await setComposition(client, "12");
    await expect(input).toHaveValue("12");
    await expect(page.locator("#amount-raw")).toHaveValue("");
    expect(await submissions(page)).toEqual([]);

    await client.send("Input.insertText", { text: "12" });

    await expect(input).toHaveValue("12.00");
    await expect(page.locator("#amount-raw")).toHaveValue("1200");
    await expect.poll(() => submissions(page)).toEqual([{ value: "12.00", raw: "1200" }]);
});

test("Money Input programmatic renders stay silent", async ({ page }) => {
    await page.setContent(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input type="hidden" id="amount-raw" value="100">
            <input
                id="amount"
                name="amount"
                value="100"
                data-controller="money-input"
                data-money-input-hidden-id-value="amount-raw"
                data-action="input->auto-submit#debouncedSubmit"
            >
        </form>
    `);
    await trackSubmissions(page, "#amount", "#amount-raw");
    await installControllers(page);

    await expect(page.locator("#amount")).toHaveValue("1.00");
    await expect(page.locator("#amount-raw")).toHaveValue("100");
    expect(await submissions(page)).toEqual([]);

    await page.locator("#amount").evaluate((input) => {
        input.value = "250000";
        document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    });

    await expect(page.locator("#amount")).toHaveValue("2,500.00");
    await expect(page.locator("#amount-raw")).toHaveValue("250000");
    expect(await submissions(page)).toEqual([]);

    await page.locator("#amount").evaluate((input) => {
        input.dataset.moneyInputLocaleValue = "pt-BR";
    });

    await expect(page.locator("#amount")).toHaveValue("2.500,00");
    await expect(page.locator("#amount-raw")).toHaveValue("250000");
    expect(await submissions(page)).toEqual([]);
});

async function installControllers(page) {
    await page.addScriptTag({ path: "node_modules/@hotwired/stimulus/dist/stimulus.umd.js" });
    await page.addScriptTag({ content: await controllerBundle() });
    await page.evaluate(() => {
        window.StimulusApplication = window.Stimulus.Application.start();
        window.StimulusApplication.register("auto-submit", window.AutoSubmitController);
        window.StimulusApplication.register("money-input", window.MoneyInputController);
    });
}

async function trackSubmissions(page, inputSelector, rawSelector = null) {
    await page.evaluate(({ inputSelector, rawSelector }) => {
        window.imeSubmissions = [];
        document.querySelector("form").requestSubmit = () => {
            window.imeSubmissions.push({
                value: document.querySelector(inputSelector).value,
                ...(rawSelector ? { raw: document.querySelector(rawSelector).value } : {}),
            });
        };
    }, { inputSelector, rawSelector });
}

async function submissions(page) {
    return page.evaluate(() => window.imeSubmissions);
}

async function setComposition(client, text) {
    await client.send("Input.imeSetComposition", {
        text,
        selectionStart: text.length,
        selectionEnd: text.length,
        replacementStart: 0,
        replacementEnd: 0,
    });
}

async function controllerBundle() {
    const composition = (await readFile("resources/js/controllers/_composition.js", "utf8"))
        .replace("export function isComposing", "function isComposing");
    const autoSubmit = (await readFile("resources/js/controllers/auto_submit_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export default class extends Controller", "class AutoSubmitController extends Controller");
    const moneyInput = (await readFile("resources/js/controllers/money_input_controller.js", "utf8"))
        .replace('import { Controller } from "@hotwired/stimulus";', "")
        .replace(/import \{[^}]*\} from "\.\/_composition\.js";\s*/, "")
        .replace("export default class extends Controller", "class MoneyInputController extends Controller");

    return `
        const { Controller } = window.Stimulus;
        ${composition}
        ${autoSubmit}
        ${moneyInput}
        window.AutoSubmitController = AutoSubmitController;
        window.MoneyInputController = MoneyInputController;
    `;
}
