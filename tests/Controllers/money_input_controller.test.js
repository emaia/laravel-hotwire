import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import MoneyInputController from "../../resources/js/controllers/money_input_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("formats a plain number with default locale and 2 decimals", async () => {
    await setup(`<input data-controller="money-input" />`);

    const input = document.querySelector("input");
    type(input, "1234567.89");
    await wait(0);

    expect(input.value).toBe("1,234,567.89");
});

test.serial("derives prefix from a currency code (USD)", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-currency-value="USD"
        />
    `);

    const input = document.querySelector("input");
    type(input, "1500");
    await wait(0);

    expect(input.value.startsWith("$")).toBe(true);
    expect(input.value).toContain("1,500");
});

test.serial("formats Brazilian Real with pt-BR locale", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
        />
    `);

    const input = document.querySelector("input");
    type(input, "1234,50");
    await wait(0);

    expect(input.value).toContain("R$");
    expect(input.value).toContain("1.234,50");
});

test.serial("places the symbol as suffix for locales that put it after the value", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="de-DE"
            data-money-input-currency-value="EUR"
        />
    `);

    const input = document.querySelector("input");
    type(input, "1234,50");
    await wait(0);

    expect(input.value.trim().endsWith("€")).toBe(true);
    expect(input.value).toContain("1.234,50");
});

test.serial("manual prefix overrides currency", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-currency-value="USD"
            data-money-input-prefix-value="USD "
        />
    `);

    const input = document.querySelector("input");
    type(input, "100.00");
    await wait(0);

    expect(input.value).toBe("USD 100.00");
});

test.serial("respects a custom fraction count", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-fraction-value="0"
        />
    `);

    const input = document.querySelector("input");
    type(input, "1234");
    await wait(0);

    expect(input.value).toBe("1,234");
});

test.serial("blocks negative values when unsigned", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-unsigned-value="true"
        />
    `);

    const input = document.querySelector("input");
    type(input, "-150");
    await wait(0);

    expect(input.value.startsWith("-")).toBe(false);
});

test.serial("destroys the mask on disconnect", async () => {
    await setup(`<input data-controller="money-input" />`);

    const controller = mounted.controller;
    expect(controller.mask).not.toBeNull();

    controller.disconnect();
    expect(controller.mask).toBeNull();
});

test.serial("rebuilds the mask when currency value changes at runtime", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-currency-value="USD"
        />
    `);

    const input = document.querySelector("input");
    mounted.root.dataset.moneyInputLocaleValue = "pt-BR";
    mounted.root.dataset.moneyInputCurrencyValue = "BRL";
    mounted.controller.localeValueChanged();
    mounted.controller.currencyValueChanged();

    type(input, "100,00");
    await wait(0);

    expect(input.value).toContain("R$");
    expect(input.value).toContain("100,00");
});

function type(input, value) {
    input.value = value;
    dispatchEvent(input, "input");
}

async function setup(html) {
    mounted = await mountController("money-input", MoneyInputController, html);
}
