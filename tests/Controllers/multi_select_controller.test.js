import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";
import DrawerController from "../../resources/js/controllers/drawer_controller.js";
import ModalController from "../../resources/js/controllers/modal_controller.js";

const floatingCleanup = mock(() => {});
const autoUpdate = mock((_anchor, _floating, update) => {
    update();

    return floatingCleanup;
});
const defaultComputePosition = async () => ({ x: 12, y: 18, placement: "bottom-start" });
const computePosition = mock(defaultComputePosition);
const offset = mock((options) => ({ name: "offset", options }));
const flip = mock((options = {}) => ({ name: "flip", options }));
const shift = mock((options = {}) => ({ name: "shift", options }));
const size = mock((options) => ({ name: "size", options }));
const arrow = mock((options) => ({ name: "arrow", options }));
const hide = mock((options = {}) => ({ name: "hide", options }));

mock.module("@floating-ui/dom", () => ({
    autoUpdate,
    computePosition,
    offset,
    flip,
    shift,
    size,
    arrow,
    hide,
}));

const { default: MultiSelectController } = await import("../../resources/js/controllers/multi_select_controller.js");

let mounted;

beforeEach(() => {
    floatingCleanup.mockClear();
    autoUpdate.mockClear();
    computePosition.mockClear();
    computePosition.mockImplementation(defaultComputePosition);
    offset.mockClear();
    flip.mockClear();
    shift.mockClear();
    size.mockClear();
    arrow.mockClear();
    hide.mockClear();
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

const root = () => document.querySelector('[data-controller="multi-select"]');
const trigger = () => document.querySelector('[data-multi-select-target="trigger"]');
const content = () => document.querySelector('[data-multi-select-target="content"]');
const value = () => document.querySelector('[data-multi-select-target="value"]');
const select = () => document.querySelector('[data-multi-select-target="select"]');
const option = (value) => document.querySelector(`[data-multi-select-target="option"][data-value="${value}"]`);
const empty = () => document.querySelector('[data-multi-select-target="empty"]');
const selectedValues = () => [...select().options].filter((option) => option.selected).map((option) => option.value);
const isOpen = () => content().dataset.state === "open" && !content().hidden;

function clickTrigger() {
    trigger().dispatchEvent(new MouseEvent("click", { bubbles: true }));
}

function pressDocument(key) {
    document.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true }));
}

function pressTarget(element, key) {
    element.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));
}

test.serial("starts closed and opens with Floating UI positioning", async () => {
    await mount();

    expect(isOpen()).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");

    clickTrigger();
    await wait(0);

    expect(isOpen()).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(autoUpdate).toHaveBeenCalledTimes(1);
    expect(computePosition).toHaveBeenCalled();
    expect(computePosition.mock.calls[0][2].strategy).toBe("fixed");
    expect(content().style.left).toBe("12px");
    expect(content().dataset.side).toBe("bottom");
});

test.serial("passes positioning values to Floating UI", async () => {
    await mount({
        values: `
            data-multi-select-side-value="right"
            data-multi-select-align-value="end"
            data-multi-select-side-offset-value="10"
            data-multi-select-align-offset-value="-3"
            data-multi-select-strategy-value="fixed"
            data-multi-select-flip-value="false"
            data-multi-select-shift-value="false"
        `,
    });

    clickTrigger();
    await wait(0);

    const options = computePosition.mock.calls[0][2];
    expect(options.placement).toBe("right-end");
    expect(options.strategy).toBe("fixed");
    expect(offset).toHaveBeenCalledWith({ mainAxis: 10, crossAxis: -3 });
    expect(flip).not.toHaveBeenCalled();
    expect(shift).not.toHaveBeenCalled();
});

test.serial("rolls back when the first placement fails", async () => {
    computePosition.mockRejectedValueOnce(new Error("positioning failed"));
    await mount();
    const handleError = mock(() => {});
    mounted.application.handleError = handleError;

    clickTrigger();
    await wait(0);
    await wait(0);

    expect(mounted.controller.openValue).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(trigger().dataset.multiSelectState).toBe("closed");
    expect(content().hidden).toBe(true);
    expect(content().hasAttribute("inert")).toBe(true);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
    expect(handleError).toHaveBeenCalledTimes(1);
});

test.serial("waits for placement before moving focus into content", async () => {
    const placement = deferred();
    computePosition.mockImplementationOnce(() => placement.promise);
    await mount();

    trigger().focus();
    clickTrigger();
    await wait(0);

    expect(content().dataset.state).toBe("closed");
    expect(content().hasAttribute("inert")).toBe(true);
    expect(document.activeElement).toBe(trigger());

    placement.resolve({ x: 12, y: 18, placement: "bottom-start" });
    await wait(0);

    expect(content().dataset.state).toBe("open");
    expect(document.activeElement).toBe(document.querySelector('[data-multi-select-target="search"]'));
});

test.serial("re-anchors open content when the trigger is replaced", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const oldTrigger = trigger();
    const replacement = oldTrigger.cloneNode(true);

    oldTrigger.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(oldTrigger);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);

    expect(replacement.getAttribute("aria-expanded")).toBe("true");
    expect(replacement.dataset.multiSelectState).toBe("open");
    expect(computePosition.mock.calls.at(-1)[0]).toBe(replacement);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);
});

test.serial("keeps managed focus and ignores a stale focus frame when content is replaced", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const oldContent = content();
    const oldSearch = oldContent.querySelector('[data-multi-select-target="search"]');
    const replacement = oldContent.cloneNode(true);
    mounted.controller.onFocusOut(new window.FocusEvent("focusout", {
        bubbles: true,
        relatedTarget: null,
    }));

    oldContent.replaceWith(replacement);
    mounted.controller.contentTargetDisconnected(oldContent);
    mounted.controller.contentTargetConnected(replacement);
    await wait(20);

    expect(oldSearch.isConnected).toBe(false);
    expect(isOpen()).toBe(true);
    expect(document.activeElement).toBe(replacement.querySelector('[data-multi-select-target="search"]'));
});

test.serial("rolls back when positioning a replacement trigger fails", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const handleError = mock(() => {});
    mounted.application.handleError = handleError;
    computePosition.mockRejectedValueOnce(new Error("replacement positioning failed"));
    const oldTrigger = trigger();
    const replacement = oldTrigger.cloneNode(true);

    oldTrigger.replaceWith(replacement);
    mounted.controller.triggerTargetDisconnected(oldTrigger);
    mounted.controller.triggerTargetConnected(replacement);
    await wait(0);
    await wait(0);

    expect(mounted.controller.openValue).toBe(false);
    expect(content().hidden).toBe(true);
    expect(handleError).toHaveBeenCalledTimes(1);
});

test.serial("synchronizes every trigger before and after fallback re-anchoring", async () => {
    await mount();
    const first = trigger();
    const second = first.cloneNode(true);
    first.after(second);
    mounted.controller.triggerTargetConnected(second);

    clickTrigger();
    await wait(0);

    expect(second.getAttribute("aria-expanded")).toBe("true");
    expect(second.dataset.multiSelectState).toBe("open");

    first.remove();
    mounted.controller.triggerTargetDisconnected(first);
    await wait(0);

    expect(second.getAttribute("aria-expanded")).toBe("true");
    expect(second.dataset.multiSelectState).toBe("open");
    expect(computePosition.mock.calls.at(-1)[0]).toBe(second);
});

test.serial("does not steal focus back when enter motion finishes", async () => {
    const enterMotion = fakeAnimation();
    await mount();
    content().getAnimations = () => content().dataset.state === "open" ? [enterMotion.animation] : [];

    clickTrigger();
    await wait(0);
    const search = document.querySelector('[data-multi-select-target="search"]');
    expect(document.activeElement).toBe(search);

    option("active").focus();
    enterMotion.finish();
    await wait(0);

    expect(document.activeElement).toBe(option("active"));
});

test.serial("selects and deselects options while syncing the native select", async () => {
    await mount();

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(option("active").dataset.selected).toBe("true");
    expect(option("active").getAttribute("aria-selected")).toBe("true");
    expect(selectedValues()).toEqual(["active"]);
    expect(value().textContent).toBe("1 selected");

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(option("active").dataset.selected).toBe("false");
    expect(selectedValues()).toEqual([]);
    expect(value().textContent).toBe("Select options");
});

test.serial("keeps selected attributes as the form state baseline", async () => {
    await mount({
        options: '<option value="active" selected>Active</option>',
        optionMarkup:
            '<div data-multi-select-target="option" data-value="active" data-selected="true" aria-selected="true">Active</div>',
    });

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(select().options[0].selected).toBe(false);
    expect(select().options[0].hasAttribute("selected")).toBe(true);
});

test.serial("commits selection defaults only after the owning form submits successfully", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-slot="multi-select-option" data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const unrelated = document.body.appendChild(document.createElement("form"));
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;

    unrelated.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success: true } }));
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    form.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success: false } }));
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    form.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success: true } }));
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    document.dispatchEvent(new CustomEvent("turbo:render"));
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("does not commit selection defaults when a successful response renders validation errors", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;

    form.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success: true } }));
    active.defaultSelected = false;
    paused.defaultSelected = true;
    active.selected = false;
    paused.selected = true;
    form.insertAdjacentHTML("beforeend", '<p aria-invalid="true">Validation failed</p>');
    document.dispatchEvent(new CustomEvent("turbo:render"));
    await wait(0);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([false, true]);
});

test.serial("waits for the submitted frame to render before committing selection defaults", async () => {
    await mount({
        form: true,
        frame: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    const fetchResponse = { contentType: "text/html" };
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: { success: true, fetchResponse },
    }));

    document.body.appendChild(document.createElement("turbo-frame")).dispatchEvent(
        new CustomEvent("turbo:frame-render", { bubbles: true }),
    );
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    document.getElementById("editor").dispatchEvent(new CustomEvent("turbo:frame-render", {
        bubbles: true,
        detail: { fetchResponse: { contentType: "text/html" } },
    }));
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    document.getElementById("editor").dispatchEvent(new CustomEvent("turbo:frame-render", {
        bubbles: true,
        detail: { fetchResponse },
    }));
    await wait(0);
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("correlates page renders with the submitted response before committing defaults", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="submitted"></main></body></html>'),
            },
        },
    }));
    expect([active.selected, paused.selected]).toEqual([false, true]);

    await dispatchPageRender('<main id="unrelated"></main>');
    document.dispatchEvent(new CustomEvent("turbo:render"));
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([false, true]);

    await dispatchPageRender('<main id="submitted"></main>');
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("ignores validation errors outside the submitted form in a page response", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const response = '<form id="multi-select-form"></form><form id="other-form"><input aria-invalid="true"></form>';
    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve(`<!doctype html><html><body>${response}</body></html>`),
            },
        },
    }));

    await dispatchPageRender(response);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("commits selection defaults after a successful Turbo Stream response", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve("<turbo-stream></turbo-stream>"),
            },
        },
    }));

    await wait(40);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("commits a successful Turbo Stream despite stale form errors", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.insertAdjacentHTML("beforeend", '<p aria-invalid="true">Previous validation error</p>');
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve("<turbo-stream><template><p>Saved</p></template></turbo-stream>"),
            },
        },
    }));

    await wait(40);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("restores selection defaults when a successful Turbo Stream contains validation errors", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve('<turbo-stream action="update" target="multi-select-form"><template><input aria-invalid="true"></template></turbo-stream>'),
            },
        },
    }));
    active.defaultSelected = false;
    paused.defaultSelected = true;
    active.selected = false;
    paused.selected = true;
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "multi-select-form");
    stream.innerHTML = '<template><input aria-invalid="true"></template>';
    document.body.appendChild(stream);
    const beforeRender = new CustomEvent("turbo:before-stream-render", {
        bubbles: true,
        detail: { newStream: stream, render: async () => {} },
    });
    stream.dispatchEvent(beforeRender);
    await wait(0);
    await beforeRender.detail.render(stream);

    await wait(40);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([false, true]);
});

test.serial("ignores validation errors in unrelated Turbo Stream targets", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    document.body.insertAdjacentHTML("beforeend", '<div id="other-form"></div>');
    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve('<turbo-stream action="update" target="other-form"><template><input aria-invalid="true"></template></turbo-stream>'),
            },
        },
    }));

    await wait(40);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("tracks validation targets created by an earlier stream in the response", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const response = `
        <turbo-stream action="append" target="multi-select-form"><template><div id="new-errors"></div></template></turbo-stream>
        <turbo-stream action="update" target="new-errors"><template><input aria-invalid="true"></template></turbo-stream>
    `;
    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve(response),
            },
        },
    }));
    active.defaultSelected = false;
    paused.defaultSelected = true;

    for (const markup of [
        '<turbo-stream action="append" target="multi-select-form"><template><div id="new-errors"></div></template></turbo-stream>',
        '<turbo-stream action="update" target="new-errors"><template><input aria-invalid="true"></template></turbo-stream>',
    ]) {
        const container = document.createElement("div");
        container.innerHTML = markup;
        const stream = container.firstElementChild;
        document.body.appendChild(stream);
        const beforeRender = new CustomEvent("turbo:before-stream-render", {
            bubbles: true,
            detail: { newStream: stream, render: async () => {} },
        });
        stream.dispatchEvent(beforeRender);
        await beforeRender.detail.render(stream);
    }

    await wait(0);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([false, true]);
});

test.serial("waits for a deferred Turbo Stream renderer before committing defaults", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    mounted.root.id = "status";
    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    const renderer = deferred();
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve('<turbo-stream action="update" target="status"><template><p>Saved</p></template></turbo-stream>'),
            },
        },
    }));

    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("target", "status");
    stream.innerHTML = "<template><p>Saved</p></template>";
    document.body.appendChild(stream);
    const beforeRender = new CustomEvent("turbo:before-stream-render", {
        bubbles: true,
        detail: { newStream: stream, render: () => renderer.promise },
    });
    stream.dispatchEvent(beforeRender);
    const rendering = beforeRender.detail.render(stream);

    await wait(40);
    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    renderer.resolve();
    await rendering;
    await wait(0);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("restores the prior baseline after a refresh stream renders errors", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve('<turbo-stream action="refresh"></turbo-stream>'),
            },
        },
    }));

    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "refresh");
    document.body.appendChild(stream);
    const beforeRender = new CustomEvent("turbo:before-stream-render", {
        bubbles: true,
        detail: {
            newStream: stream,
            render: async () => document.dispatchEvent(new CustomEvent("turbo:visit")),
        },
    });
    stream.dispatchEvent(beforeRender);
    await wait(0);
    await beforeRender.detail.render(stream);
    await wait(0);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);

    const followUpSubmission = {};
    active.selected = true;
    paused.selected = false;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission: followUpSubmission },
    }));
    active.defaultSelected = false;
    paused.defaultSelected = true;
    form.insertAdjacentHTML("beforeend", '<p aria-invalid="true">Validation failed</p>');
    document.dispatchEvent(new CustomEvent("turbo:render"));
    await wait(0);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([true, false]);

    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission: followUpSubmission,
            success: false,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="follow-up-failed"></main></body></html>'),
            },
        },
    }));
    active.defaultSelected = false;
    paused.defaultSelected = true;

    await dispatchPageRender('<main id="follow-up-failed"></main>');

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([true, false]);
});

test.serial("resolves a canceled refresh baseline before a failed follow-up submission", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/vnd.turbo-stream.html",
                responseHTML: Promise.resolve('<turbo-stream action="refresh"></turbo-stream>'),
            },
        },
    }));

    const followUpSubmission = {};
    active.selected = true;
    paused.selected = false;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission: followUpSubmission },
    }));
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission: followUpSubmission,
            success: false,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="canceled-refresh-follow-up"></main></body></html>'),
            },
        },
    }));
    active.defaultSelected = false;
    paused.defaultSelected = true;

    await dispatchPageRender('<main id="canceled-refresh-follow-up"></main>');

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([true, false]);
});

test.serial("commits an empty successful HTML response without waiting for a render", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve(""),
            },
        },
    }));

    await wait(0);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("commits selection defaults after a 204 despite stale validation markers", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.insertAdjacentHTML("beforeend", '<p aria-invalid="true">Previous validation error</p>');
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: true,
            fetchResponse: {
                contentType: null,
                responseHTML: Promise.resolve(undefined),
                statusCode: 204,
            },
        },
    }));

    await wait(40);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
});

test.serial("commits the submitted selection while preserving edits made during the request", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    const formSubmission = {};
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission },
    }));

    active.selected = true;
    paused.selected = false;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission,
            success: true,
            fetchResponse: {
                contentType: null,
                responseHTML: Promise.resolve(undefined),
                statusCode: 204,
            },
        },
    }));

    await wait(40);

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
    expect([active.selected, paused.selected]).toEqual([true, false]);
});

test.serial("restores selection defaults after an unsuccessful document render", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            success: false,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="failed"></main></body></html>'),
                statusCode: 422,
            },
        },
    }));
    active.defaultSelected = false;
    paused.defaultSelected = true;
    active.selected = false;
    paused.selected = true;

    await dispatchPageRender('<main id="failed"></main>');

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
    expect([active.selected, paused.selected]).toEqual([false, true]);
});

test.serial("carries a pending 204 baseline into an immediate follow-up submission", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    const firstSubmission = {};
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission: firstSubmission },
    }));
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission: firstSubmission,
            success: true,
            fetchResponse: {
                contentType: null,
                responseHTML: Promise.resolve(undefined),
                statusCode: 204,
            },
        },
    }));

    const secondSubmission = {};
    active.selected = true;
    paused.selected = false;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission: secondSubmission },
    }));
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission: secondSubmission,
            success: false,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="second-failed"></main></body></html>'),
                statusCode: 422,
            },
        },
    }));
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = true;
    paused.selected = false;

    await dispatchPageRender('<main id="second-failed"></main>');

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
    expect([active.selected, paused.selected]).toEqual([true, false]);
});

test.serial("carries a pending HTML baseline into a failed follow-up submission", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    const firstSubmission = {};
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission: firstSubmission },
    }));
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission: firstSubmission,
            success: true,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="first-success"></main></body></html>'),
                statusCode: 200,
            },
        },
    }));

    const secondSubmission = {};
    active.selected = true;
    paused.selected = false;
    form.dispatchEvent(new CustomEvent("turbo:submit-start", {
        bubbles: true,
        detail: { formSubmission: secondSubmission },
    }));
    form.dispatchEvent(new CustomEvent("turbo:submit-end", {
        bubbles: true,
        detail: {
            formSubmission: secondSubmission,
            success: false,
            fetchResponse: {
                contentType: "text/html",
                responseHTML: Promise.resolve('<!doctype html><html><body><main id="second-html-failed"></main></body></html>'),
                statusCode: 422,
            },
        },
    }));
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = true;
    paused.selected = false;

    await dispatchPageRender('<main id="second-html-failed"></main>');

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([false, true]);
    expect([active.selected, paused.selected]).toEqual([true, false]);
});

test.serial("removes pending baseline listeners on disconnect", async () => {
    await mount({
        form: true,
        options: `
            <option value="active" selected>Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-multi-select-target="option" data-value="active" data-selected="true" role="option" aria-selected="true" tabindex="-1">Active</div>
            <div data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const form = document.getElementById("multi-select-form");
    const [active, paused] = select().options;
    active.defaultSelected = true;
    paused.defaultSelected = false;
    active.selected = false;
    paused.selected = true;
    form.dispatchEvent(new CustomEvent("turbo:submit-end", { bubbles: true, detail: { success: true } }));
    mounted.controller.disconnect();
    document.dispatchEvent(new CustomEvent("turbo:render"));

    expect([active.defaultSelected, paused.defaultSelected]).toEqual([true, false]);
});

test.serial("list-all summary is capped and keeps the full summary in the title", async () => {
    await mount({ values: 'data-multi-select-list-all-value="true" data-multi-select-list-all-limit-value="2"' });

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("paused").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("archived").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(value().textContent).toBe("Active, Paused, +1 more");
    expect(value().title).toBe("Active, Paused, Archived");
});

test.serial("list-all hidden count text can be customized", async () => {
    await mount({
        values: `
            data-multi-select-list-all-value="true"
            data-multi-select-list-all-limit-value="2"
            data-multi-select-list-all-more-text-value="+:count itens"
        `,
    });

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("paused").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("archived").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(value().textContent).toBe("Active, Paused, +1 itens");
});

test.serial("list-all limit can be disabled", async () => {
    await mount({ values: 'data-multi-select-list-all-value="true" data-multi-select-list-all-limit-value="0"' });

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("paused").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("archived").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(value().textContent).toBe("Active, Paused, Archived");
    expect(value().hasAttribute("title")).toBe(false);
});

test.serial("sort-selected moves selected options to the top", async () => {
    await mount({ values: 'data-multi-select-sort-selected-value="true"' });

    option("archived").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect([...document.querySelectorAll('[data-multi-select-target="option"]')].map((option) => option.dataset.value))
        .toEqual(["archived", "active", "paused"]);
});

test.serial("dispatches select, unselect and change events", async () => {
    await mount();
    const events = [];
    root().addEventListener("multi-select:select", (event) => events.push(["select", event.detail.value]));
    root().addEventListener("multi-select:unselect", (event) => events.push(["unselect", event.detail.value]));
    root().addEventListener("multi-select:change", (event) => events.push(["change", event.detail.values]));

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(events).toEqual([
        ["select", "active"],
        ["change", ["active"]],
        ["unselect", "active"],
        ["change", []],
    ]);
});

test.serial("select all toggles all options and respects max", async () => {
    await mount({ values: 'data-multi-select-max-value="2"' });

    document
        .querySelector('[data-multi-select-target="selectAll"]')
        .dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectedValues()).toEqual(["active", "paused"]);
    expect(option("archived").getAttribute("aria-disabled")).toBe("true");
});

test.serial("select all becomes indeterminate when some visible options are selected", async () => {
    await mount();

    const selectAll = document.querySelector('[data-multi-select-target="selectAll"]');

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectAll.dataset.selected).toBe("false");
    expect(selectAll.dataset.indeterminate).toBe("true");
    expect(selectAll.getAttribute("aria-pressed")).toBe("mixed");

    option("paused").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("archived").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectAll.dataset.selected).toBe("true");
    expect(selectAll.dataset.indeterminate).toBe("false");
    expect(selectAll.getAttribute("aria-pressed")).toBe("true");

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectAll.dataset.selected).toBe("false");
    expect(selectAll.dataset.indeterminate).toBe("true");
    expect(selectAll.getAttribute("aria-pressed")).toBe("mixed");

    option("paused").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    option("archived").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectAll.dataset.selected).toBe("false");
    expect(selectAll.dataset.indeterminate).toBe("false");
    expect(selectAll.getAttribute("aria-pressed")).toBe("false");
});

test.serial("select all stays enabled to clear a maxed-out full selection", async () => {
    await mount({
        values: 'data-multi-select-max-value="2"',
        options: `
            <option value="active">Active</option>
            <option value="paused">Paused</option>
        `,
        optionMarkup: `
            <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
            <div data-slot="multi-select-option" data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
        `,
    });

    const selectAll = document.querySelector('[data-multi-select-target="selectAll"]');
    selectAll.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectedValues()).toEqual(["active", "paused"]);
    expect(selectAll.getAttribute("aria-pressed")).toBe("true");
    expect(selectAll.getAttribute("aria-disabled")).toBe("false");

    selectAll.dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(selectedValues()).toEqual([]);
});

test.serial("search refreshes select-all state for the visible options", async () => {
    await mount();

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    const search = document.querySelector('[data-multi-select-target="search"]');
    search.value = "active";
    search.dispatchEvent(new Event("input", { bubbles: true }));

    const selectAll = document.querySelector('[data-multi-select-target="selectAll"]');
    expect(selectAll.getAttribute("aria-pressed")).toBe("true");
});

test.serial("ArrowDown from search focuses select-all before options", async () => {
    await mount();
    clickTrigger();
    await wait(0);

    const search = document.querySelector('[data-multi-select-target="search"]');
    search.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }));

    expect(document.activeElement).toBe(document.querySelector('[data-multi-select-target="selectAll"]'));

    document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp", bubbles: true }));
    expect(document.activeElement).toBe(search);

    search.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }));

    document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }));
    expect(document.activeElement).toBe(option("active"));
});

test.serial("ArrowUp from the first option returns focus to search when select-all is absent", async () => {
    await mount({ selectAll: false });
    clickTrigger();
    await wait(0);

    const search = document.querySelector('[data-multi-select-target="search"]');
    search.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }));
    expect(document.activeElement).toBe(option("active"));

    document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowUp", bubbles: true }));
    expect(document.activeElement).toBe(search);
});

test.serial("Space toggles the option focused from search keyboard navigation", async () => {
    await mount({ selectAll: false });
    clickTrigger();
    await wait(0);

    const search = document.querySelector('[data-multi-select-target="search"]');
    search.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }));
    expect(document.activeElement).toBe(option("active"));

    document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: " ", bubbles: true }));
    expect(selectedValues()).toEqual(["active"]);

    document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: " ", bubbles: true }));
    expect(selectedValues()).toEqual([]);
});

test.serial("clicking an option focuses it so Space can toggle it again", async () => {
    await mount();
    clickTrigger();
    await wait(0);

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));

    expect(document.activeElement).toBe(option("active"));
    expect(selectedValues()).toEqual(["active"]);

    document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: " ", bubbles: true }));
    expect(selectedValues()).toEqual([]);
});

test.serial("works without optional search or select-all targets", async () => {
    await mount({ search: false, selectAll: false });

    option("active").dispatchEvent(new KeyboardEvent("keydown", { key: " ", bubbles: true }));

    expect(selectedValues()).toEqual(["active"]);

    await mounted.cleanup();
    mounted = null;
});

test.serial("search filters options without accents", async () => {
    await mount({
        options: `
            <option value="sao">São Paulo</option>
            <option value="rio">Rio de Janeiro</option>
        `,
        optionMarkup: `
            <div data-slot="multi-select-option" data-multi-select-target="option" data-value="sao" data-selected="false" role="option" aria-selected="false" tabindex="-1">São Paulo</div>
            <div data-slot="multi-select-option" data-multi-select-target="option" data-value="rio" data-selected="false" role="option" aria-selected="false" tabindex="-1">Rio de Janeiro</div>
        `,
    });

    const search = document.querySelector('[data-multi-select-target="search"]');
    search.value = "sao";
    search.dispatchEvent(new Event("input", { bubbles: true }));

    expect(option("sao").hidden).toBe(false);
    expect(option("rio").hidden).toBe(true);
    expect(empty().hidden).toBe(true);
});

test.serial("search shows the empty state and hides select-all when nothing matches", async () => {
    await mount();

    const search = document.querySelector('[data-multi-select-target="search"]');
    const selectAll = document.querySelector('[data-multi-select-target="selectAll"]');
    search.value = "missing";
    search.dispatchEvent(new Event("input", { bubbles: true }));

    expect(option("active").hidden).toBe(true);
    expect(option("paused").hidden).toBe(true);
    expect(empty().hidden).toBe(false);
    expect(empty().textContent).toBe("No options found.");
    expect(selectAll.hidden).toBe(true);
});

test.serial("clearable search reset restores filtered options", async () => {
    await mount();
    const search = document.querySelector('[data-multi-select-target="search"]');
    search.value = "active";
    search.dispatchEvent(new Event("input", { bubbles: true }));

    expect(option("paused").hidden).toBe(true);

    search.value = "";
    search.dispatchEvent(new CustomEvent("inputCleared", { bubbles: true }));

    expect(option("paused").hidden).toBe(false);
});

test.serial("Tab from search can move to the clear button without closing", async () => {
    await mount({ clearableSearch: true });
    clickTrigger();
    await wait(0);
    const search = document.querySelector('[data-multi-select-target="search"]');
    const clearButton = document.querySelector('[data-clear-input-target="clearButton"]');
    search.focus();

    search.dispatchEvent(new KeyboardEvent("keydown", { key: "Tab", bubbles: true }));
    search.dispatchEvent(new window.FocusEvent("focusout", { bubbles: true, relatedTarget: clearButton }));

    expect(isOpen()).toBe(true);
});

test.serial("focus leaving the multi-select closes the list", async () => {
    await mount();
    document.body.insertAdjacentHTML("beforeend", '<button id="outside-focus">Outside</button>');
    clickTrigger();
    const search = document.querySelector('[data-multi-select-target="search"]');
    const outside = document.querySelector("#outside-focus");
    search.focus();

    search.dispatchEvent(new window.FocusEvent("focusout", { bubbles: true, relatedTarget: outside }));
    outside.focus();
    await wait(0);

    expect(isOpen()).toBe(false);
});

test.serial("Escape closes and returns focus to the trigger", async () => {
    await mount();
    clickTrigger();

    pressDocument("Escape");

    expect(isOpen()).toBe(false);
    expect(document.activeElement).toBe(trigger());
});

test.serial("Escape inside an open drawer closes only the multi-select first", async () => {
    mounted = await mountMultipleControllers(
        {
            drawer: DrawerController,
            "multi-select": MultiSelectController,
        },
        `
        <div data-controller="drawer"
             data-drawer-lock-scroll-class="overflow-hidden">
            <button id="drawer-trigger" data-drawer-target="trigger" data-action="drawer#toggle">Open drawer</button>
            <div data-drawer-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-drawer-target="backdrop" data-action="click->drawer#clickOutside" class="opacity-0"></div>
                <div data-drawer-target="dialog" class="translate-x-full">
                    <div data-controller="multi-select" data-multi-select-placeholder-value="Select options">
                        <select data-multi-select-target="select" name="status[]" multiple hidden>
                            <option value="active">Active</option>
                        </select>
                        <button type="button" data-multi-select-target="trigger" aria-expanded="false" data-action="multi-select#toggle">
                            <span data-multi-select-target="value">Select options</span>
                        </button>
                        <div data-multi-select-target="content" data-state="closed" data-motion="default" hidden inert>
                            <div data-multi-select-target="list" role="listbox" aria-multiselectable="true">
                                <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    document.getElementById("drawer-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);

    clickTrigger();
    await wait(0);

    option("active").dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(isOpen()).toBe(false);
    expect(mounted.controller.isOpen).toBe(true);

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    await wait(10);

    expect(mounted.controller.isOpen).toBe(false);
});

test.serial("Escape inside an open modal closes only the multi-select when the multi-select listener runs first", async () => {
    mounted = await mountMultipleControllers(
        {
            "multi-select": MultiSelectController,
            modal: ModalController,
        },
        `
        <div id="modal" data-controller="modal"
             data-modal-lock-scroll-class="overflow-hidden">
            <button id="modal-trigger" data-action="modal#open">Open modal</button>
            <div data-modal-target="modal" data-state="closed" data-motion="none" hidden inert>
                <div data-modal-target="backdrop"></div>
                <div data-modal-target="dialog">
                    <div data-controller="multi-select" data-multi-select-placeholder-value="Select options">
                        <select data-multi-select-target="select" name="status[]" multiple hidden>
                            <option value="active">Active</option>
                        </select>
                        <button type="button" data-multi-select-target="trigger" aria-expanded="false" data-action="multi-select#toggle">
                            <span data-multi-select-target="value">Select options</span>
                        </button>
                        <div data-multi-select-target="content" data-state="closed" data-motion="default" hidden inert>
                            <input id="modal-multi-search" data-multi-select-target="search" type="text">
                            <div data-multi-select-target="list" role="listbox" aria-multiselectable="true">
                                <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`,
    );

    const modal = mounted.getController("modal", document.getElementById("modal"));

    document.getElementById("modal-trigger").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
    await wait(10);

    clickTrigger();
    await wait(0);

    pressTarget(document.getElementById("modal-multi-search"), "Escape");
    await wait(10);

    expect(isOpen()).toBe(false);
    expect(modal.isOpen).toBe(true);
});

test.serial("required validation proxy tracks whether anything is selected", async () => {
    await mount({ values: 'data-multi-select-required-value="true"', validation: true });
    const validation = document.querySelector('[data-multi-select-target="validation"]');

    expect(validation.value).toBe("");

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    expect(validation.value).toBe("1");

    option("active").dispatchEvent(new MouseEvent("click", { bubbles: true }));
    expect(validation.value).toBe("");
});

test.serial("turbo:before-cache closes and disconnect cleans up positioning", async () => {
    await mount();
    clickTrigger();
    await wait(0);

    document.dispatchEvent(new CustomEvent("turbo:before-cache", { bubbles: true }));

    expect(isOpen()).toBe(false);
    expect(floatingCleanup).toHaveBeenCalledTimes(1);

    clickTrigger();
    await wait(0);
    mounted.controller.disconnect();

    expect(floatingCleanup).toHaveBeenCalledTimes(2);
});

test.serial("disconnect tolerates content removed by a morph", async () => {
    await mount();
    clickTrigger();
    await wait(0);
    const removed = content();

    removed.remove();
    mounted.controller.contentTargetDisconnected(removed);

    expect(() => mounted.controller.disconnect()).not.toThrow();
    expect(mounted.controller.openValue).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
});

async function mount({ values = "", options = null, optionMarkup = null, validation = false, search = true, selectAll = true, clearableSearch = false, form = false, frame = false } = {}) {
    const searchMarkup = clearableSearch
        ? `<span data-controller="clear-input">
            <input data-multi-select-target="search" data-clear-input-target="input" type="text">
            <button type="button" class="hidden" data-slot="clear-input-button" data-clear-input-target="clearButton">Clear</button>
        </span>`
        : '<input data-multi-select-target="search" type="text">';

    mounted = await mountController(
        "multi-select",
        MultiSelectController,
        `
        ${frame ? '<turbo-frame id="editor">' : ""}
        ${form ? '<form id="multi-select-form">' : ""}
        <div data-controller="multi-select"
             data-multi-select-placeholder-value="Select options"
             data-multi-select-select-all-value="${selectAll ? "true" : "false"}"
             ${values}>
            <select data-slot="multi-select-native" data-multi-select-target="select" name="status[]" multiple hidden>
                ${options ?? `
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="archived">Archived</option>
                `}
            </select>
            <button type="button" data-multi-select-target="trigger" aria-expanded="false" data-action="multi-select#toggle">
                <span data-multi-select-target="value">Select options</span>
            </button>
            <div data-multi-select-target="content" data-state="closed" data-motion="default" hidden inert>
                ${search ? searchMarkup : ""}
                ${selectAll ? '<button type="button" data-multi-select-target="selectAll" aria-pressed="false">Select all</button>' : ""}
                <div data-multi-select-target="list" role="listbox" aria-multiselectable="true">
                    ${optionMarkup ?? `
                        <div data-slot="multi-select-option" data-multi-select-target="option" data-value="active" data-selected="false" role="option" aria-selected="false" tabindex="-1">Active</div>
                        <div data-slot="multi-select-option" data-multi-select-target="option" data-value="paused" data-selected="false" role="option" aria-selected="false" tabindex="-1">Paused</div>
                        <div data-slot="multi-select-option" data-multi-select-target="option" data-value="archived" data-selected="false" role="option" aria-selected="false" tabindex="-1">Archived</div>
                    `}
                </div>
                <div data-slot="multi-select-empty" data-multi-select-target="empty" hidden>No options found.</div>
            </div>
            ${validation ? '<input data-multi-select-target="validation" type="text" required tabindex="-1">' : ""}
        </div>
        ${form ? "</form>" : ""}
        ${frame ? "</turbo-frame>" : ""}`,
    );
}

async function dispatchPageRender(html) {
    const newBody = document.createElement("body");
    newBody.innerHTML = html;
    const event = new CustomEvent("turbo:before-render", {
        detail: {
            newBody,
            render: async () => {},
        },
    });

    document.dispatchEvent(event);
    await event.detail.render(document.body, newBody);
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}

function fakeAnimation() {
    const finished = deferred();

    return {
        animation: {
            effect: { getComputedTiming: () => ({ endTime: 100 }) },
            finished: finished.promise,
            playState: "running",
        },
        finish: finished.resolve,
    };
}
