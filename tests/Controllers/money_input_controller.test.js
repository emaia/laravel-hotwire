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

test.serial("shifts decimal digits from the right when using fractional currency input", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-fraction-value="4"
            data-money-input-eager-value="true"
        />
    `);

    const input = document.querySelector("input");
    pressKeys(input, "99999");
    await wait(0);

    expect(input.value).toContain("R$");
    expect(normalizeSpaces(input.value)).toBe("R$ 9,9999");
});

test.serial("does not accumulate visible leading zeroes while typing fractional values", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-fraction-value="4"
            data-money-input-eager-value="true"
        />
    `);

    const input = document.querySelector("input");
    pressKeys(input, "1999");
    await wait(0);

    expect(normalizeSpaces(input.value)).toBe("R$ 0,1999");
});

test.serial("ignores letters in right-aligned fractional mode", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-fraction-value="4"
            data-money-input-eager-value="true"
        />
    `);

    const input = document.querySelector("input");
    pressKeys(input, "1a9b");
    await wait(0);

    expect(normalizeSpaces(input.value)).toBe("R$ 0,0019");
});

test.serial("replaces the full value when the input content is fully selected", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-fraction-value="4"
            value="123456"
        />
    `);

    const input = document.querySelector("input");
    input.select();
    pressKeys(input, "1999");
    await wait(0);

    expect(normalizeSpaces(input.value)).toBe("R$ 0,1999");
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

test.serial("stops handling keyboard formatting after disconnect", async () => {
    await setup(`<input data-controller="money-input" />`);

    const controller = mounted.controller;

    controller.disconnect();

    const input = document.querySelector("input");
    pressKeys(input, "123");
    await wait(0);

    expect(input.value).toBe("");
});

test.serial("interprets the initial value attribute as minor units", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="de-DE"
            data-money-input-currency-value="EUR"
            value="156795"
        />
    `);

    const input = document.querySelector("input");
    await wait(0);

    expect(normalizeSpaces(input.value)).toBe("1.567,95 €");
});

test.serial("renders empty when initial value is empty", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
        />
    `);

    const input = document.querySelector("input");
    await wait(0);

    expect(input.value).toBe("");
});

test.serial("syncs the raw value (minor units) to a hidden input identified by hiddenIdValue while typing", async () => {
    await setup(`
        <input type="hidden" id="price-raw" />
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-hidden-id-value="price-raw"
        />
    `);

    const visible = document.querySelector('[data-controller="money-input"]');
    const hidden = document.getElementById("price-raw");
    type(visible, "1234,50");
    await wait(0);

    expect(visible.value).toContain("1.234,50");
    expect(hidden.value).toBe("123450");
});

test.serial("syncs minor units to a hidden input on keystrokes in eager mode", async () => {
    await setup(`
        <input type="hidden" id="price-raw-eager" />
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-fraction-value="2"
            data-money-input-eager-value="true"
            data-money-input-hidden-id-value="price-raw-eager"
        />
    `);

    const visible = document.querySelector('[data-controller="money-input"]');
    const hidden = document.getElementById("price-raw-eager");
    pressKeys(visible, "199");
    await wait(0);

    expect(normalizeSpaces(visible.value)).toBe("R$ 1,99");
    expect(hidden.value).toBe("199");
});

test.serial("syncs minor units to a hidden input on initial connect when input has a value", async () => {
    await setup(`
        <input type="hidden" id="price-raw-initial" />
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-hidden-id-value="price-raw-initial"
            value="123450"
        />
    `);

    const hidden = document.getElementById("price-raw-initial");
    await wait(0);

    expect(hidden.value).toBe("123450");
});

test.serial("syncs minor units with fraction zero", async () => {
    await setup(`
        <input type="hidden" id="price-raw-int" />
        <input
            data-controller="money-input"
            data-money-input-fraction-value="0"
            data-money-input-hidden-id-value="price-raw-int"
        />
    `);

    const visible = document.querySelector('[data-controller="money-input"]');
    const hidden = document.getElementById("price-raw-int");
    type(visible, "1234");
    await wait(0);

    expect(visible.value).toBe("1,234");
    expect(hidden.value).toBe("1234");
});

test.serial("clears the hidden input when the visible value is cleared", async () => {
    await setup(`
        <input type="hidden" id="price-raw-clear" value="999" />
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-money-input-hidden-id-value="price-raw-clear"
        />
    `);

    const visible = document.querySelector('[data-controller="money-input"]');
    const hidden = document.getElementById("price-raw-clear");
    pressKeys(visible, "55");
    await wait(0);
    visible.dispatchEvent(new KeyboardEvent("keydown", { key: "Delete", bubbles: true, cancelable: true }));
    await wait(0);

    expect(visible.value).toBe("");
    expect(hidden.value).toBe("");
});

test.serial("dispatches money-input:change with masked, unmasked (minor units) and completed", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
        />
    `);

    const input = document.querySelector("input");
    const events = [];
    input.addEventListener("money-input:change", (event) => events.push(event.detail));

    type(input, "1234,50");
    await wait(0);

    expect(events.length).toBeGreaterThan(0);

    const last = events[events.length - 1];
    expect(normalizeSpaces(last.masked)).toContain("1.234,50");
    expect(last.unmasked).toBe("123450");
    expect(last.completed).toBe(true);
});

test.serial("dispatches money-input:change with empty unmasked value and completed=false when cleared", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            value="100"
        />
    `);

    const input = document.querySelector("input");
    const events = [];
    input.addEventListener("money-input:change", (event) => events.push(event.detail));

    input.dispatchEvent(new KeyboardEvent("keydown", { key: "Delete", bubbles: true, cancelable: true }));
    await wait(0);

    const last = events[events.length - 1];
    expect(last.masked).toBe("");
    expect(last.unmasked).toBe("");
    expect(last.completed).toBe(false);
});

test.serial("places the negative sign before the currency prefix", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="en-US"
            data-money-input-currency-value="USD"
            value="-199"
        />
    `);

    const input = document.querySelector("input");
    await wait(0);

    expect(input.value).toBe("-$1.99");
});

test.serial("places the negative sign before the number for suffix locales", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="de-DE"
            data-money-input-currency-value="EUR"
            value="-15679"
        />
    `);

    const input = document.querySelector("input");
    await wait(0);

    expect(normalizeSpaces(input.value)).toBe("-156,79 €");
});

test.serial("preserves the underlying value when locale and currency change at runtime", async () => {
    await setup(`
        <input
            data-controller="money-input"
            data-money-input-locale-value="en-US"
            data-money-input-currency-value="USD"
            value="123456"
        />
    `);

    const input = document.querySelector("input");
    await wait(0);

    expect(input.value).toBe("$1,234.56");

    mounted.root.dataset.moneyInputLocaleValue = "de-DE";
    mounted.root.dataset.moneyInputCurrencyValue = "EUR";
    mounted.controller.localeValueChanged();
    mounted.controller.currencyValueChanged();
    await wait(0);

    expect(normalizeSpaces(input.value)).toBe("1.234,56 €");
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
    await wait(0);

    type(input, "100,00");
    await wait(0);

    expect(input.value).toContain("R$");
    expect(input.value).toContain("100,00");
});

function type(input, value) {
    input.value = value;
    dispatchEvent(input, "input");
}

function pressKeys(input, value) {
    for (const key of value) {
        input.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
    }
}

function normalizeSpaces(value) {
    return value.replace(/\u00A0/g, " ");
}

async function setup(html) {
    mounted = await mountController("money-input", MoneyInputController, html);
}
