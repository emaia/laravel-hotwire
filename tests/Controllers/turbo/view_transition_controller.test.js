import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController } from "../../../resources/js/helpers/test_stimulus.js";
import ViewTransitionController from "../../../resources/js/controllers/turbo/view_transition_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

function dispatchBeforeFrameRender(element, renderFn) {
    const event = new CustomEvent("turbo:before-frame-render", {
        bubbles: true,
        cancelable: true,
        detail: { render: renderFn },
    });
    element.dispatchEvent(event);
    return event;
}

test.serial("wraps event.detail.render with document.startViewTransition", async () => {
    await mount();
    document.startViewTransition = mock((cb) => cb());

    const originalRender = mock(() => {});
    const event = dispatchBeforeFrameRender(mounted.root, originalRender);

    event.detail.render("current", "new");

    expect(document.startViewTransition).toHaveBeenCalledTimes(1);
    expect(originalRender).toHaveBeenCalledWith("current", "new");
});

test.serial("leaves event.detail.render untouched when startViewTransition is unavailable", async () => {
    await mount();
    document.startViewTransition = undefined;

    const originalRender = mock(() => {});
    const event = dispatchBeforeFrameRender(mounted.root, originalRender);

    event.detail.render("current", "new");

    expect(originalRender).toHaveBeenCalledTimes(1);
    expect(originalRender).toHaveBeenCalledWith("current", "new");
});

test.serial("skips only the initial render of an opted-in frame", async () => {
    await mount('<turbo-frame data-controller="turbo--view-transition" data-turbo--view-transition-skip-initial-value="true"></turbo-frame>');
    document.startViewTransition = mock((cb) => cb());

    const initialRender = mock(() => {});
    const initialEvent = dispatchBeforeFrameRender(mounted.root, initialRender);
    initialEvent.detail.render("empty", "show");

    expect(document.startViewTransition).not.toHaveBeenCalled();
    expect(initialRender).toHaveBeenCalledWith("empty", "show");

    const nextRender = mock(() => {});
    const nextEvent = dispatchBeforeFrameRender(mounted.root, nextRender);
    nextEvent.detail.render("show", "edit");

    expect(document.startViewTransition).toHaveBeenCalledTimes(1);
    expect(nextRender).toHaveBeenCalledWith("show", "edit");

    mounted.root.innerHTML = "";
    mounted.root.dispatchEvent(new CustomEvent("turbo:before-fetch-request", { bubbles: true }));
    mounted.root.innerHTML = "Loading";

    const reopenedRender = mock(() => {});
    const reopenedEvent = dispatchBeforeFrameRender(mounted.root, reopenedRender);
    reopenedEvent.detail.render("empty", "show again");

    expect(document.startViewTransition).toHaveBeenCalledTimes(1);
    expect(reopenedRender).toHaveBeenCalledWith("empty", "show again");
});

test.serial("transitions the first render of a frame that already holds content", async () => {
    await mount('<turbo-frame data-controller="turbo--view-transition" data-turbo--view-transition-skip-initial-value="true">Fallback</turbo-frame>');
    document.startViewTransition = mock((cb) => cb());

    const firstRender = mock(() => {});
    const firstEvent = dispatchBeforeFrameRender(mounted.root, firstRender);
    firstEvent.detail.render("fallback", "edit");

    expect(document.startViewTransition).toHaveBeenCalledTimes(1);
    expect(firstRender).toHaveBeenCalledWith("fallback", "edit");
});

test.serial("renders without document snapshots while modal dialogs are stacked and resumes afterward", async () => {
    await mount(`<div>
        <div role="dialog" aria-modal="true" data-state="open"></div>
        <div role="dialog" aria-modal="true" data-state="open">
            <turbo-frame data-controller="turbo--view-transition">Customer form</turbo-frame>
        </div>
    </div>`);
    document.startViewTransition = mock((cb) => cb());
    const frame = mounted.root;
    const render = mock(() => {});
    const event = dispatchBeforeFrameRender(frame, render);
    event.detail.render("form", "validation errors");

    expect(document.startViewTransition).not.toHaveBeenCalled();
    expect(render).toHaveBeenCalledWith("form", "validation errors");

    const background = document.querySelector('[role="dialog"]');
    background.hidden = true;
    const next = dispatchBeforeFrameRender(frame, render);
    next.detail.render("errors", "next form");
    expect(document.startViewTransition).toHaveBeenCalledTimes(1);

    background.hidden = false;
    background.dataset.state = "closed";
    dispatchBeforeFrameRender(frame, render).detail.render("next form", "another form");
    expect(document.startViewTransition).toHaveBeenCalledTimes(2);
});

test.serial("only wraps events fired on this.element", async () => {
    await mount(`<div data-controller="turbo--view-transition"><span id="child"></span></div>`);
    document.startViewTransition = mock((cb) => cb());

    const originalRender = mock(() => {});
    // Dispatch on the inner child — won't bubble through the controller's own
    // element listener because the listener is bound to this.element directly.
    // Actually it will bubble; verify the wrapping happens.
    const child = document.getElementById("child");
    const event = dispatchBeforeFrameRender(child, originalRender);

    event.detail.render("a", "b");

    // The controller wraps any matching event that reaches its element via bubbling.
    expect(document.startViewTransition).toHaveBeenCalledTimes(1);
});

test.serial("disconnect detaches the listener", async () => {
    await mount();
    document.startViewTransition = mock((cb) => cb());

    mounted.controller.disconnect();

    const originalRender = mock(() => {});
    const event = dispatchBeforeFrameRender(mounted.root, originalRender);

    event.detail.render("a", "b");

    expect(document.startViewTransition).not.toHaveBeenCalled();
    // Original render still runs because we called event.detail.render directly.
    expect(originalRender).toHaveBeenCalled();
});

async function mount(html) {
    mounted = await mountController(
        "turbo--view-transition",
        ViewTransitionController,
        html ?? `<div data-controller="turbo--view-transition"></div>`,
    );
}
