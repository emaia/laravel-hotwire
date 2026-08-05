import { afterEach, expect, test } from "bun:test";

import { dispatchEvent, mountController } from "../../resources/js/helpers/test_stimulus.js";
import SliderController from "../../resources/js/controllers/slider_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("sets the visual fill from the initial value", async () => {
    await mount(`<input type="range" min="0" max="100" value="25">`);

    expect(fill()).toBe(25);
});

test.serial("calculates fill relative to a non-zero minimum", async () => {
    await mount(`<input type="range" min="-10" max="10" value="0">`);

    expect(fill()).toBe(50);
});

test.serial("uses native range defaults", async () => {
    await mount(`<input type="range">`);

    expect(fill()).toBe(50);
});

test.serial("updates fill through the input action", async () => {
    await mount(`<input type="range" min="0" max="100" value="25" data-action="input->slider#update">`);

    mounted.root.value = "75";
    dispatchEvent(mounted.root, "input");

    expect(fill()).toBe(75);
});

test.serial("clamps fill to the visual range", async () => {
    await mount(`<input type="range" min="0" max="100" value="25">`);

    Object.defineProperty(mounted.root, "valueAsNumber", { configurable: true, value: 150 });
    mounted.controller.update();
    expect(fill()).toBe(100);

    Object.defineProperty(mounted.root, "valueAsNumber", { configurable: true, value: -25 });
    mounted.controller.update();
    expect(fill()).toBe(0);
});

test.serial("uses zero fill when min and max are equal", async () => {
    await mount(`<input type="range" min="10" max="10" value="10">`);

    expect(fill()).toBe(0);
});

test.serial("falls back to native defaults for malformed bounds", async () => {
    await mount(`<input type="range" min="10x" max="20" value="15">`);

    expect(fill()).toBe(75);
});

test.serial("refreshes after Turbo morph removes the managed style", async () => {
    await mount(`<input type="range" min="0" max="100" value="25">`);

    mounted.root.value = "75";
    mounted.root.style.removeProperty("--slider-value");
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));

    expect(fill()).toBe(75);
});

test.serial("rebinds reset handling when Turbo morph changes the form owner", async () => {
    await mount(`
        <form id="first"></form>
        <form id="second"></form>
        <input type="range" min="0" max="100" value="25" form="first" data-action="input->slider#update">
    `);

    mounted.root.setAttribute("form", "second");
    mounted.root.dispatchEvent(new CustomEvent("turbo:morph-element", { bubbles: true }));
    mounted.root.value = "75";
    dispatchEvent(mounted.root, "input");
    expect(fill()).toBe(75);

    document.querySelector("#second").reset();
    await waitFrame();

    expect(mounted.root.value).toBe("25");
    expect(fill()).toBe(25);
});

test.serial("refreshes fill after a native form reset", async () => {
    await mount(`<form><input type="range" min="0" max="100" value="25" data-action="input->slider#update"></form>`);

    mounted.root.value = "75";
    dispatchEvent(mounted.root, "input");
    expect(fill()).toBe(75);

    mounted.root.form.reset();
    await waitFrame();

    expect(mounted.root.value).toBe("25");
    expect(fill()).toBe(25);
});

test.serial("disconnect removes reset handling and cancels pending work", async () => {
    await mount(`<form><input type="range" min="0" max="100" value="25" data-action="input->slider#update"></form>`);

    mounted.root.value = "75";
    dispatchEvent(mounted.root, "input");
    expect(fill()).toBe(75);

    mounted.controller.reset();
    mounted.controller.disconnect();
    mounted.root.form.reset();
    await waitFrame();

    expect(fill()).toBe(75);
});

async function mount(markup) {
    mounted = await mountController(
        "slider",
        SliderController,
        markup.replace("<input", '<input data-controller="slider"'),
    );
}

function fill() {
    return Number.parseFloat(mounted.root.style.getPropertyValue("--slider-value"));
}

function waitFrame() {
    return new Promise((resolve) => requestAnimationFrame(() => resolve()));
}
