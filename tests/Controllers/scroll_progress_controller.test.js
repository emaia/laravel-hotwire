import { afterEach, beforeEach, expect, test } from "bun:test";

import { dispatchEvent, mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ScrollProgressController from "../../resources/js/controllers/scroll_progress_controller.js";

let mounted;

beforeEach(() => {
    // Default test viewport: 1000 doc, 500 client → scrollable range = 500.
    Object.defineProperty(globalThis, "innerHeight", { value: 500, writable: true, configurable: true });
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

function stubScroll({ scrollY = 0, scrollHeight = 1000, clientHeight = 500 } = {}) {
    Object.defineProperty(globalThis.window, "scrollY", { value: scrollY, writable: true, configurable: true });
    Object.defineProperty(document.documentElement, "scrollHeight", { value: scrollHeight, writable: true, configurable: true });
    Object.defineProperty(document.documentElement, "clientHeight", { value: clientHeight, writable: true, configurable: true });
}

// --- connect: applies initial width ---

test.serial("connect computes width from current scroll position", async () => {
    await mount();
    stubScroll({ scrollY: 250, scrollHeight: 1000, clientHeight: 500 });

    mounted.controller.onScroll();

    expect(mounted.root.style.width).toBe("50%");
});

test.serial("connect's initial onScroll runs synchronously", async () => {
    await mount();

    stubScroll({ scrollY: 100, scrollHeight: 1000, clientHeight: 500 });
    mounted.controller.onScroll();

    expect(mounted.root.style.width).toBe("20%");
});

// --- scroll updates ---

test.serial("dispatches scroll → recomputes width", async () => {
    await mount(0); // disable throttle so updates are synchronous

    stubScroll({ scrollY: 100, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");

    expect(mounted.root.style.width).toBe("20%");
});

test.serial("width reaches 100% at maximum scroll", async () => {
    await mount(0);

    stubScroll({ scrollY: 500, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");

    expect(mounted.root.style.width).toBe("100%");
});

// --- throttling ---

test.serial("throttle: second scroll within the window does not recompute", async () => {
    await mount(50);

    stubScroll({ scrollY: 100, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");
    expect(mounted.root.style.width).toBe("20%");

    stubScroll({ scrollY: 500, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");
    // Still showing the first reading — second was swallowed by throttle.
    expect(mounted.root.style.width).toBe("20%");
});

test.serial("throttle: a scroll after the window does recompute", async () => {
    await mount(20);

    stubScroll({ scrollY: 100, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");
    expect(mounted.root.style.width).toBe("20%");

    await wait(40);

    stubScroll({ scrollY: 500, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");
    expect(mounted.root.style.width).toBe("100%");
});

// --- disconnect ---

test.serial("disconnect detaches the scroll listener", async () => {
    await mount(0);

    // Capture style before disconnect for comparison.
    stubScroll({ scrollY: 100, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");
    const widthBefore = mounted.root.style.width;
    expect(widthBefore).toBe("20%");

    mounted.controller.disconnect();

    stubScroll({ scrollY: 500, scrollHeight: 1000, clientHeight: 500 });
    dispatchEvent(globalThis.window, "scroll");

    // Width didn't change → listener detached.
    expect(mounted.root.style.width).toBe("20%");
});

async function mount(throttleDelay = 0) {
    const attr = throttleDelay > 0
        ? ` data-scroll-progress-throttle-delay-value="${throttleDelay}"`
        : ' data-scroll-progress-throttle-delay-value="0"';
    mounted = await mountController(
        "scroll-progress",
        ScrollProgressController,
        `<div data-controller="scroll-progress"${attr}></div>`,
    );
}
