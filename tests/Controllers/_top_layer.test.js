import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

import { createTopLayer } from "../../resources/js/controllers/_top_layer.js";

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.CustomEvent = testWindow.CustomEvent;
});

afterEach(() => {
    testWindow.close();
});

test("shows in the top layer and restores existing popover attributes on hide", () => {
    const element = document.createElement("div");
    const showPopover = mock(() => {});
    const hidePopover = mock(() => {});
    const shown = mock(() => {});
    element.setAttribute("popover", "auto");
    element.showPopover = showPopover;
    element.hidePopover = hidePopover;
    document.addEventListener("hotwire:top-layer:show", shown);
    const topLayer = createTopLayer(element);

    topLayer.show();

    expect(topLayer.isSupported).toBe(true);
    expect(topLayer.isShown).toBe(true);
    expect(showPopover).toHaveBeenCalledTimes(1);
    expect(element.getAttribute("popover")).toBe("manual");
    expect(element.hasAttribute("data-hotwire-top-layer")).toBe(true);
    expect(shown).toHaveBeenCalledTimes(1);

    topLayer.hide();

    expect(hidePopover).toHaveBeenCalledTimes(1);
    expect(topLayer.isShown).toBe(false);
    expect(element.getAttribute("popover")).toBe("auto");
    expect(element.hasAttribute("data-hotwire-top-layer")).toBe(false);
});

test("bringToFront re-enters an already shown element", () => {
    const element = document.createElement("div");
    element.showPopover = mock(() => {});
    element.hidePopover = mock(() => {});
    const topLayer = createTopLayer(element);
    topLayer.show();

    topLayer.bringToFront();

    expect(element.showPopover).toHaveBeenCalledTimes(2);
    expect(element.hidePopover).toHaveBeenCalledTimes(1);
    expect(topLayer.isShown).toBe(true);
});

test("does not preserve package-owned popover attributes cloned from an open element", () => {
    const element = document.createElement("div");
    element.setAttribute("popover", "manual");
    element.setAttribute("data-hotwire-top-layer", "");
    element.showPopover = mock(() => {});
    element.hidePopover = mock(() => {});
    const topLayer = createTopLayer(element);

    topLayer.show();
    topLayer.cleanup();

    expect(element.hasAttribute("popover")).toBe(false);
    expect(element.hasAttribute("data-hotwire-top-layer")).toBe(false);
});

test("normalizes package-owned attributes even when a cloned element is never shown", () => {
    const element = document.createElement("div");
    element.setAttribute("popover", "manual");
    element.setAttribute("data-hotwire-top-layer", "");
    element.showPopover = mock(() => {});
    element.hidePopover = mock(() => {});
    const topLayer = createTopLayer(element);

    topLayer.cleanup();

    expect(element.hasAttribute("popover")).toBe(false);
    expect(element.hasAttribute("data-hotwire-top-layer")).toBe(false);
});

test("preserves the original popover mode when managed content is cloned", () => {
    const element = document.createElement("div");
    element.setAttribute("popover", "auto");
    element.showPopover = mock(() => {});
    element.hidePopover = mock(() => {});
    const topLayer = createTopLayer(element);
    topLayer.show();
    const clone = element.cloneNode(true);
    clone.showPopover = mock(() => {});
    clone.hidePopover = mock(() => {});

    const clonedTopLayer = createTopLayer(clone);
    clonedTopLayer.cleanup();

    expect(clone.getAttribute("popover")).toBe("auto");
    expect(clone.hasAttribute("data-hotwire-top-layer")).toBe(false);
});

test("cleanup is idempotent and unsupported elements remain unchanged", () => {
    const supported = document.createElement("div");
    supported.showPopover = mock(() => {});
    supported.hidePopover = mock(() => {});
    const topLayer = createTopLayer(supported);
    topLayer.show();

    topLayer.cleanup();
    topLayer.cleanup();

    expect(supported.hidePopover).toHaveBeenCalledTimes(1);

    const unsupported = document.createElement("div");
    const fallback = createTopLayer(unsupported);
    fallback.show();
    fallback.hide();

    expect(fallback.isSupported).toBe(false);
    expect(unsupported.hasAttribute("popover")).toBe(false);
});
