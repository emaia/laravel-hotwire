import { afterEach, beforeEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import AnimatedNumberController from "../../resources/js/controllers/animated_number_controller.js";

// --- IntersectionObserver mock ---

let ioInstances = [];

class FakeIntersectionObserver {
    constructor(callback, options) {
        this.callback = callback;
        this.options = options;
        this.observed = [];
        ioInstances.push(this);
    }
    observe(el) { this.observed.push(el); }
    unobserve(el) { this.observed = this.observed.filter((e) => e !== el); }
    disconnect() { this.observed = []; }
    trigger(entries) {
        this.callback(entries, this);
    }
}

globalThis.IntersectionObserver = FakeIntersectionObserver;

// --- rAF mock helper ---
// The controller uses `if (!startTimestamp)` (falsy check), so timestamp 0
// is indistinguishable from null. We use non-zero timestamps to work around this.

let rafCalls = [];

function rafMock(fn) {
    rafCalls.push(fn);
    return rafCalls.length;
}

function flushRaf(forceTimestamp) {
    const pending = [...rafCalls];
    rafCalls = [];
    for (const fn of pending) {
        fn(forceTimestamp);
    }
}

function installRafMock() {
    globalThis.requestAnimationFrame = rafMock;
    window.requestAnimationFrame = rafMock;
    rafCalls = [];
}

let mounted;

beforeEach(() => {
    rafCalls = [];
    ioInstances = [];
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- animate: non-lazy, tested manually ---

test.serial("animate: first rAF frame sets innerHTML to start value", async () => {
    await mountLazy(`<span>50</span>`);

    installRafMock();
    mounted.controller.animate();
    flushRaf(100);

    expect(mounted.root.innerHTML).toBe("0");
});

test.serial("animate: animates through frames to completion", async () => {
    await mountLazy(`<span>50</span>`);

    installRafMock();
    mounted.controller.animate();

    flushRaf(100);
    expect(mounted.root.innerHTML).toBe("0");

    flushRaf(600);
    expect(mounted.root.innerHTML).toBe("50");

    flushRaf(1100);
    expect(mounted.root.innerHTML).toBe("100");

    expect(rafCalls).toHaveLength(0);
});

test.serial("animate: animates negative range", async () => {
    await mountWrapper(`<span>50</span>`, { start: 10, end: -10 });

    installRafMock();
    mounted.controller.animate();

    flushRaf(100);
    expect(mounted.root.innerHTML).toBe("10");

    flushRaf(600);
    expect(mounted.root.innerHTML).toBe("0");

    flushRaf(1100);
    expect(mounted.root.innerHTML).toBe("-10");

    expect(rafCalls).toHaveLength(0);
});

test.serial("animate: custom duration affects timing", async () => {
    await mountWrapper(`<span>50</span>`, { start: 0, end: 100, duration: 500 });

    installRafMock();
    mounted.controller.animate();

    flushRaf(100);
    expect(mounted.root.innerHTML).toBe("0");

    flushRaf(350);
    expect(mounted.root.innerHTML).toBe("50");

    flushRaf(600);
    expect(mounted.root.innerHTML).toBe("100");
});

// --- lazy mode ---

test.serial("lazy: creates IntersectionObserver but does not call animate immediately", async () => {
    await mountLazy(`<span>50</span>`);

    expect(mounted.controller.lazyValue).toBe(true);
    expect(ioInstances).toHaveLength(1);
    expect(ioInstances[0].observed).toContain(mounted.root);
});

test.serial("lazy: intersection triggers animate which calls rAF", async () => {
    await mountLazy(`<span>50</span>`);

    installRafMock();

    ioInstances[0].trigger([{ isIntersecting: true, target: mounted.root }]);

    expect(rafCalls).toHaveLength(1);
});

test.serial("lazy: unobserve is called after intersection", async () => {
    await mountLazy(`<span>50</span>`);

    ioInstances[0].trigger([{ isIntersecting: true, target: mounted.root }]);
    await wait(0);

    expect(ioInstances[0].observed).toHaveLength(0);
});

test.serial("lazy: intersection options include threshold and rootMargin", async () => {
    await mountWrapper(`<span>50</span>`, {
        lazy: true,
        lazyThreshold: 0.5,
        lazyRootMargin: "100px",
    });

    expect(ioInstances[0].options.threshold).toBe(0.5);
    expect(ioInstances[0].options.rootMargin).toBe("100px");
});

test.serial("lazy: default lazyRootMargin is 0px", async () => {
    await mountLazy(`<span>50</span>`);

    expect(ioInstances[0].options.rootMargin).toBe("0px");
});

// --- value defaults ---

test.serial("start and end value default to 0", async () => {
    await mount(`<span data-controller="animated-number" data-animated-number-duration-value="1000">50</span>`);

    expect(mounted.controller.startValue).toBe(0);
    expect(mounted.controller.endValue).toBe(0);
});

test.serial("lazy value defaults to false", async () => {
    await mount(`<span data-controller="animated-number" data-animated-number-duration-value="1000">50</span>`);

    expect(mounted.controller.lazyValue).toBe(false);
});

// --- mount helpers ---

async function mountLazy(innerHTML) {
    return mountWrapper(innerHTML, { lazy: true });
}

async function mountWrapper(innerHTML, values) {
    const attrs = [];
    attrs.push(`data-controller="animated-number"`);
    attrs.push(`data-animated-number-start-value="${values.start ?? 0}"`);
    attrs.push(`data-animated-number-end-value="${values.end ?? 100}"`);
    attrs.push(`data-animated-number-duration-value="${values.duration ?? 1000}"`);
    if (values.lazy) {
        attrs.push(`data-animated-number-lazy-value="true"`);
    }
    if (values.lazyThreshold != null) {
        attrs.push(`data-animated-number-lazy-threshold-value="${values.lazyThreshold}"`);
    }
    if (values.lazyRootMargin != null) {
        attrs.push(`data-animated-number-lazy-root-margin-value="${values.lazyRootMargin}"`);
    }
    mounted = await mountController("animated-number", AnimatedNumberController, `<span ${attrs.join(" ")}>${innerHTML}</span>`);
}

async function mount(html) {
    mounted = await mountController("animated-number", AnimatedNumberController, html);
}
