import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import InputMaskController from "../../resources/js/controllers/input_mask_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("applies a static pattern mask", async () => {
    await setup(`
        <input
            data-controller="input-mask"
            data-input-mask-mask-value="(##) #####-####"
        />
    `);

    const input = document.querySelector("input");
    type(input, "21999998888");
    await wait(0);

    expect(input.value).toBe("(21) 99999-8888");
});

test.serial("switches between dynamic masks based on input length", async () => {
    await setup(`
        <input
            data-controller="input-mask"
            data-input-mask-mask-value='["###.###.###-##", "##.###.###/####-##"]'
        />
    `);

    const input = document.querySelector("input");

    type(input, "12345678901");
    await wait(0);
    expect(input.value).toBe("123.456.789-01");

    type(input, "12345678000190");
    await wait(0);
    expect(input.value).toBe("12.345.678/0001-90");
});

test.serial("applies the mask from right to left when reversed", async () => {
    await setup(`
        <input
            data-controller="input-mask"
            data-input-mask-mask-value="#.###.###"
            data-input-mask-reversed-value="true"
        />
    `);

    const input = document.querySelector("input");
    type(input, "1234567");
    await wait(0);

    expect(input.value).toBe("1.234.567");
});

test.serial("destroys the mask on disconnect", async () => {
    await setup(`<input data-controller="input-mask" data-input-mask-mask-value="###" />`);

    const controller = mounted.controller;
    expect(controller.mask).not.toBeNull();

    controller.disconnect();
    expect(controller.mask).toBeNull();
});

test.serial("accepts custom tokens that merge with Maska defaults", async () => {
    const tokens = JSON.stringify({
        L: { pattern: "[A-Z]", transform: undefined },
    });

    await setup(`
        <input
            data-controller="input-mask"
            data-input-mask-mask-value="LL-##"
            data-input-mask-tokens-value='${tokens}'
        />
    `);

    const input = document.querySelector("input");
    type(input, "AB12");
    await wait(0);

    expect(input.value).toBe("AB-12");
});

test.serial("rebuilds the mask when the mask value changes at runtime", async () => {
    await setup(`<input data-controller="input-mask" data-input-mask-mask-value="###" />`);

    const input = document.querySelector("input");
    mounted.root.dataset.inputMaskMaskValue = "##-##";
    mounted.controller.maskValueChanged();

    type(input, "12345");
    await wait(0);

    expect(input.value).toBe("12-34");
});

function type(input, value) {
    input.value = value;
    dispatchEvent(input, "input");
}

async function setup(html) {
    mounted = await mountController("input-mask", InputMaskController, html);
}
