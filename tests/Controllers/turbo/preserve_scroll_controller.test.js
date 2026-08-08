import { afterEach, expect, mock, test } from "bun:test";

import { mountController } from "../../../resources/js/helpers/test_stimulus.js";
import PreserveScrollController from "../../../resources/js/controllers/turbo/preserve_scroll_controller.js";

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

test.serial("blurs a focused element inside the frame before rendering", async () => {
    await mount();
    const link = mounted.root.querySelector("a");
    const blur = mock(() => {});
    link.blur = blur;
    link.focus();

    const event = dispatchBeforeFrameRender(mounted.root, mock(() => {}));
    event.detail.render(mounted.root, document.createElement("turbo-frame"));

    expect(blur).toHaveBeenCalledTimes(1);
});

test.serial("restores window scroll after Turbo replaces the frame", async () => {
    await mount();
    Object.defineProperty(window, "scrollY", { configurable: true, value: 420 });
    Object.defineProperty(document, "scrollingElement", {
        configurable: true,
        value: { scrollHeight: 900, clientHeight: 720 },
    });
    const scrollTo = mock(() => {});
    window.scrollTo = scrollTo;

    const event = dispatchBeforeFrameRender(mounted.root, () => {
        Object.defineProperty(window, "scrollY", { configurable: true, value: 0 });
    });

    event.detail.render(mounted.root, document.createElement("turbo-frame"));

    expect(scrollTo).toHaveBeenCalledWith(0, 180);
});

test.serial("does not blur focus outside the frame", async () => {
    await mount();
    const outside = document.createElement("button");
    const blur = mock(() => {});
    outside.blur = blur;
    document.body.append(outside);
    outside.focus();

    const event = dispatchBeforeFrameRender(mounted.root, mock(() => {}));
    event.detail.render(mounted.root, document.createElement("turbo-frame"));

    expect(blur).not.toHaveBeenCalled();
});

test.serial("disconnect detaches the listener", async () => {
    await mount();
    mounted.controller.disconnect();
    Object.defineProperty(window, "scrollY", { configurable: true, value: 420 });
    const scrollTo = mock(() => {});
    window.scrollTo = scrollTo;

    const event = dispatchBeforeFrameRender(mounted.root, () => {});
    event.detail.render(mounted.root, document.createElement("turbo-frame"));

    expect(scrollTo).not.toHaveBeenCalled();
});

async function mount() {
    mounted = await mountController(
        "turbo--preserve-scroll",
        PreserveScrollController,
        `<turbo-frame id="results" data-controller="turbo--preserve-scroll"><a href="/tasks?page=2">Next</a></turbo-frame><div style="min-height: 1200px"></div>`,
    );
}
