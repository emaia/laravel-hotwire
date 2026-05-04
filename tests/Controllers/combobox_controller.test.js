import { afterEach, expect, test } from "bun:test";

import { mountController, wait, dispatchEvent } from "../../resources/js/helpers/test_stimulus.js";
import ComboboxController from "../../resources/js/controllers/combobox_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("has filter target declared and accessible", async () => {
    mounted = await mountController(
        "select",
        ComboboxController,
        `
            <div data-controller="select">
                <input type="text" data-select-target="filter" />
                <div data-select-target="listbox">
                    <div role="option" data-value="a">A</div>
                </div>
                <input type="hidden" data-select-target="input" />
                <button type="button" data-select-target="trigger">
                    <span data-select-target="selectedLabel">A</span>
                </button>
                <div data-select-target="popover"></div>
            </div>
        `,
    );

    expect(mounted.controller.hasFilterTarget).toBe(true);
    expect(mounted.controller.filterTarget).not.toBeNull();
    expect(mounted.controller.hasListboxTarget).toBe(true);
});

test.serial("filterOptions sets aria-hidden on non-matching options", async () => {
    mounted = await mountController(
        "select",
        ComboboxController,
        `
            <div data-controller="select">
                <button type="button" data-select-target="trigger">
                    <span data-select-target="selectedLabel">Apple</span>
                </button>
                <div data-select-target="popover" aria-hidden="true">
                    <header>
                        <input type="text" data-select-target="filter" placeholder="Search..." />
                    </header>
                    <div data-select-target="listbox">
                        <div role="option" data-value="apple">Apple</div>
                        <div role="option" data-value="banana">Banana</div>
                        <div role="option" data-value="blueberry">Blueberry</div>
                        <div role="option" data-value="grapes">Grapes</div>
                        <div role="option" data-value="pineapple">Pineapple</div>
                    </div>
                </div>
                <input type="hidden" name="value" value="apple" data-select-target="input" />
            </div>
        `,
    );

    const ctrl = mounted.controller;

    expect(ctrl.hasFilterTarget).toBe(true);
    expect(ctrl.allOptions.length).toBe(5);
    expect(ctrl.visibleOptions.length).toBe(5);

    ctrl.filterTarget.value = "blue";
    ctrl.filterOptions();

    expect(ctrl.visibleOptions.length).toBe(1);
    expect(ctrl.visibleOptions[0].getAttribute("data-value")).toBe("blueberry");

    ctrl.filterTarget.value = "";
    ctrl.filterOptions();

    expect(ctrl.visibleOptions.length).toBe(5);
});

test.serial("toggles aria-hidden on open/close", async () => {
    mounted = await mountController("select", ComboboxController, `
        <div data-controller="select">
            <button type="button" data-select-target="trigger"><span data-select-target="selectedLabel">A</span></button>
            <div data-select-target="popover" aria-hidden="true">
                <div data-select-target="listbox">
                    <div role="option" data-value="a">A</div>
                </div>
            </div>
            <input type="hidden" data-select-target="input" />
        </div>
    `);

    const ctrl = mounted.controller;
    const popover = ctrl.popoverTarget;

    expect(popover.getAttribute("aria-hidden")).toBe("true");

    ctrl.openPopover();
    expect(popover.getAttribute("aria-hidden")).toBe("false");

    ctrl.closePopover();
    expect(popover.getAttribute("aria-hidden")).toBe("true");
});

test.serial("sets left:auto and right when popover overflows viewport right edge", async () => {
    mounted = await mountController("select", ComboboxController, `
        <div data-controller="select">
            <button type="button" data-select-target="trigger"><span data-select-target="selectedLabel">A</span></button>
            <div data-select-target="popover">
                <div data-select-target="listbox">
                    <div role="option" data-value="a">Long option text</div>
                </div>
            </div>
            <input type="hidden" data-select-target="input" />
        </div>
    `);

    const ctrl = mounted.controller;
    const popover = ctrl.popoverTarget;

    ctrl.triggerTarget.getBoundingClientRect = () => ({
        x: 900, y: 100, width: 200, height: 40,
        top: 100, right: 1100, bottom: 140, left: 900,
        toJSON: () => ({}),
    });

    Object.defineProperty(popover, "offsetWidth", { value: 300, configurable: true });

    ctrl.openPopover();

    expect(popover.style.left).toBe("auto");
    expect(popover.style.right).toBe("4px");
});

test.serial("limits listbox height when popover overflows viewport bottom", async () => {
    mounted = await mountController("select", ComboboxController, `
        <div data-controller="select">
            <button type="button" data-select-target="trigger"><span data-select-target="selectedLabel">A</span></button>
            <div data-select-target="popover">
                <div data-select-target="listbox">
                    <div role="option" data-value="a">Option</div>
                </div>
            </div>
            <input type="hidden" data-select-target="input" />
        </div>
    `);

    const ctrl = mounted.controller;
    const popover = ctrl.popoverTarget;
    const listbox = ctrl.listboxTarget;

    ctrl.triggerTarget.getBoundingClientRect = () => ({
        x: 100, y: 700, width: 200, height: 40,
        top: 700, right: 300, bottom: 740, left: 100,
        toJSON: () => ({}),
    });

    Object.defineProperty(popover, "offsetHeight", { value: 200, configurable: true });

    ctrl.openPopover();

    expect(listbox.style.overflowY).toBe("auto");
    expect(listbox.style.maxHeight).not.toBe("");
});

test.serial("resets overflow styles on close", async () => {
    mounted = await mountController("select", ComboboxController, `
        <div data-controller="select">
            <button type="button" data-select-target="trigger"><span data-select-target="selectedLabel">A</span></button>
            <div data-select-target="popover" aria-hidden="true">
                <div data-select-target="listbox">
                    <div role="option" data-value="a">A</div>
                </div>
            </div>
            <input type="hidden" data-select-target="input" />
        </div>
    `);

    const ctrl = mounted.controller;
    const popover = ctrl.popoverTarget;
    const listbox = ctrl.listboxTarget;

    ctrl.triggerTarget.getBoundingClientRect = () => ({
        x: 100, y: 700, width: 200, height: 40,
        top: 700, right: 300, bottom: 740, left: 100,
        toJSON: () => ({}),
    });

    Object.defineProperty(popover, "offsetHeight", { value: 200, configurable: true });
    ctrl.openPopover();
    expect(listbox.style.overflowY).toBe("auto");

    ctrl.closePopover();

    expect(listbox.style.overflowY).toBe("");
    expect(popover.style.left).toBe("");
    expect(popover.style.right).toBe("");
});
