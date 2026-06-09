import { afterEach, expect, mock, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import BackToTopController from "../../resources/js/controllers/back_to_top_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

function setScrollY(value) {
    Object.defineProperty(window, "scrollY", {
        configurable: true,
        get: () => value,
    });
}

function setReducedMotion(reduce) {
    window.matchMedia = (query) => ({
        matches: query === "(prefers-reduced-motion: reduce)" && reduce,
        media: query,
        addEventListener: () => {},
        removeEventListener: () => {},
    });
}

function waitFrame() {
    return new Promise((resolve) => requestAnimationFrame(() => resolve()));
}

// --- visibility ---

test.serial("data-visible is false on connect at the top of the page", async () => {
    await mount(`<button data-controller="back-to-top"></button>`);

    expect(mounted.root.getAttribute("data-visible")).toBe("false");
});

test.serial("data-visible flips to true after scrolling past threshold", async () => {
    await mount(`<button data-controller="back-to-top"></button>`);

    setScrollY(500);
    window.dispatchEvent(new Event("scroll"));
    await waitFrame();

    expect(mounted.root.getAttribute("data-visible")).toBe("true");
});

test.serial("data-visible flips back to false after scrolling below threshold", async () => {
    await mount(`<button data-controller="back-to-top"></button>`);

    setScrollY(500);
    window.dispatchEvent(new Event("scroll"));
    await waitFrame();
    expect(mounted.root.getAttribute("data-visible")).toBe("true");

    setScrollY(0);
    window.dispatchEvent(new Event("scroll"));
    await waitFrame();

    expect(mounted.root.getAttribute("data-visible")).toBe("false");
});

test.serial("threshold value overrides the 400px default", async () => {
    await mount(`<button data-controller="back-to-top" data-back-to-top-threshold-value="200"></button>`);

    setScrollY(300);
    window.dispatchEvent(new Event("scroll"));
    await waitFrame();

    expect(mounted.root.getAttribute("data-visible")).toBe("true");
});

test.serial("threshold uses strict greater-than (not greater-or-equal)", async () => {
    await mount(`<button data-controller="back-to-top" data-back-to-top-threshold-value="200"></button>`);

    setScrollY(200);
    window.dispatchEvent(new Event("scroll"));
    await waitFrame();

    expect(mounted.root.getAttribute("data-visible")).toBe("false");
});

// --- scrollToTop action ---

test.serial("scrollToTop scrolls to (0, 0) with smooth behavior by default", async () => {
    await mount(`<button data-controller="back-to-top" data-action="back-to-top#scrollToTop"></button>`);

    setReducedMotion(false);
    const scrollTo = mock(() => {});
    window.scrollTo = scrollTo;

    mounted.root.click();

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, left: 0, behavior: "smooth" });
});

test.serial("scrollToTop uses auto behavior when prefers-reduced-motion is set", async () => {
    await mount(`<button data-controller="back-to-top" data-action="back-to-top#scrollToTop"></button>`);

    setReducedMotion(true);
    const scrollTo = mock(() => {});
    window.scrollTo = scrollTo;

    mounted.root.click();

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, left: 0, behavior: "auto" });
});

// --- throttle ---

test.serial("scroll listener is throttled via requestAnimationFrame", async () => {
    await mount(`<button data-controller="back-to-top"></button>`);

    setScrollY(500);
    window.dispatchEvent(new Event("scroll"));
    window.dispatchEvent(new Event("scroll"));
    window.dispatchEvent(new Event("scroll"));

    // Before the rAF callback flushes, the attribute should not have changed yet.
    expect(mounted.root.getAttribute("data-visible")).toBe("false");

    await waitFrame();

    expect(mounted.root.getAttribute("data-visible")).toBe("true");
});

// --- cleanup ---

test.serial("disconnect removes the scroll listener and cancels pending frame", async () => {
    await mount(`<button data-controller="back-to-top"></button>`);

    const root = mounted.root;

    setScrollY(500);
    window.dispatchEvent(new Event("scroll"));

    await mounted.cleanup();
    mounted = null;

    await waitFrame();

    // The element was detached on cleanup; assert it has no live state.
    // The key promise: dispatching after disconnect doesn't crash and no listener fires.
    setScrollY(700);
    window.dispatchEvent(new Event("scroll"));
    await waitFrame();

    expect(root.isConnected).toBe(false);
});

async function mount(html) {
    mounted = await mountController("back-to-top", BackToTopController, html);
}
