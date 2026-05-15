import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
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

test.serial("setActiveOption applies and removes activeClass from data attribute", async () => {
    mounted = await mountController(
        "select",
        ComboboxController,
        `
            <div data-controller="select" data-select-active-class="is-active highlight">
                <button type="button" data-select-target="trigger"><span data-select-target="selectedLabel">A</span></button>
                <div data-select-target="popover" aria-hidden="true">
                    <div data-select-target="listbox">
                        <div role="option" data-value="a" id="opt-a">A</div>
                        <div role="option" data-value="b" id="opt-b">B</div>
                    </div>
                </div>
                <input type="hidden" data-select-target="input" />
            </div>
        `,
    );

    const ctrl = mounted.controller;
    const [a, b] = ctrl.options;

    ctrl.setActiveOption(0);
    expect(a.classList.contains("is-active")).toBe(true);
    expect(a.classList.contains("highlight")).toBe(true);

    ctrl.setActiveOption(1);
    expect(a.classList.contains("is-active")).toBe(false);
    expect(a.classList.contains("highlight")).toBe(false);
    expect(b.classList.contains("is-active")).toBe(true);
    expect(b.classList.contains("highlight")).toBe(true);
});

test.serial("updateValue toggles placeholderClass on the label in multiple mode", async () => {
    mounted = await mountController(
        "select",
        ComboboxController,
        `
            <div
                data-controller="select"
                data-select-placeholder-class="muted"
                data-placeholder="Pick some"
            >
                <button type="button" data-select-target="trigger">
                    <span data-select-target="selectedLabel">Pick some</span>
                </button>
                <div data-select-target="popover" aria-hidden="true">
                    <div data-select-target="listbox" aria-multiselectable="true">
                        <div role="option" data-value="a">A</div>
                        <div role="option" data-value="b">B</div>
                    </div>
                </div>
                <input type="hidden" data-select-target="input" />
            </div>
        `,
    );

    const ctrl = mounted.controller;
    const label = ctrl.selectedLabelTarget;
    const [a] = ctrl.options;

    // Initial connect runs updateValue with no selection -> muted applied
    expect(label.classList.contains("muted")).toBe(true);

    ctrl.updateValue([a]);
    expect(label.classList.contains("muted")).toBe(false);

    ctrl.updateValue([]);
    expect(label.classList.contains("muted")).toBe(true);
});

test.serial("setActiveOption is a no-op on classes when activeClass is not set", async () => {
    mounted = await mountController(
        "select",
        ComboboxController,
        `
            <div data-controller="select">
                <button type="button" data-select-target="trigger"><span data-select-target="selectedLabel">A</span></button>
                <div data-select-target="popover" aria-hidden="true">
                    <div data-select-target="listbox">
                        <div role="option" data-value="a">A</div>
                    </div>
                </div>
                <input type="hidden" data-select-target="input" />
            </div>
        `,
    );

    const ctrl = mounted.controller;
    const [a] = ctrl.options;
    const beforeLength = a.classList.length;

    ctrl.setActiveOption(0);
    expect(a.classList.length).toBe(beforeLength);
    expect(ctrl.triggerTarget.getAttribute("aria-activedescendant")).toBe("");
});
