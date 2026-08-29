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

test("restores a shown popover after a DOM move closes its native top layer", () => {
    const element = document.createElement("div");
    const showPopover = mock(() => {});
    const shown = mock(() => {});
    element.showPopover = showPopover;
    element.hidePopover = mock(() => {});
    element.matches = mock(() => false);
    document.addEventListener("hotwire:top-layer:show", shown);
    const topLayer = createTopLayer(element);
    topLayer.show();

    topLayer.restore();

    expect(showPopover).toHaveBeenCalledTimes(2);
    expect(shown).toHaveBeenCalledTimes(2);
    expect(topLayer.isShown).toBe(true);
});

test("cleanup removes a detached entry retained after a failed raise", () => {
    const lowerElement = document.createElement("div");
    lowerElement.showPopover = mock(() => {});
    lowerElement.hidePopover = mock(() => {});
    lowerElement.matches = mock(() => false);
    const upperElement = document.createElement("div");
    let upperShows = 0;
    upperElement.showPopover = mock(() => {
        upperShows++;
        if (upperShows > 1) throw new Error("detached");
    });
    upperElement.hidePopover = mock(() => {});
    const lower = createTopLayer(lowerElement);
    const upper = createTopLayer(upperElement);
    lower.show();
    upper.show();
    const releasedPosition = upper.position;

    lower.restore();
    upper.cleanup();

    const replacementElement = document.createElement("div");
    replacementElement.showPopover = mock(() => {});
    replacementElement.hidePopover = mock(() => {});
    const replacement = createTopLayer(replacementElement);
    replacement.show();

    expect(replacement.position).toBe(releasedPosition);

    lower.cleanup();
    replacement.cleanup();
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

test("restores an entry retained after a raise failed while it was detached", () => {
    const lowerElement = document.createElement("div");
    lowerElement.showPopover = mock(() => {});
    lowerElement.hidePopover = mock(() => {});
    lowerElement.matches = mock(() => false);

    const upperElement = document.createElement("div");
    let detached = false;
    upperElement.showPopover = mock(() => {
        if (detached) throw new Error("not connected");
    });
    upperElement.hidePopover = mock(() => {});
    upperElement.matches = mock(() => false);
    Object.defineProperty(upperElement, "isConnected", { get: () => !detached });

    const lower = createTopLayer(lowerElement);
    const upper = createTopLayer(upperElement);
    lower.show();
    upper.show();

    // A morph detaches the upper dialog; re-showing the lower one cascades a
    // raise onto it, which fails and leaves the entry retained but not shown.
    detached = true;
    lower.restore();
    expect(upper.isShown).toBe(false);
    expect(upper.position).toBeGreaterThanOrEqual(0);

    // Once it reconnects, restoring has to put it back in the top layer.
    detached = false;
    upper.restore();

    expect(upper.isShown).toBe(true);
    expect(upper.position).toBeGreaterThanOrEqual(0);
});
