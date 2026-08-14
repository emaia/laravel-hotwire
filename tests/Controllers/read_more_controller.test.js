import { afterEach, beforeEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ReadMoreController from "../../resources/js/controllers/read_more_controller.js";

let mounted;
let observers;

beforeEach(() => {
    observers = [];
    globalThis.ResizeObserver = class {
        constructor(callback) {
            this.callback = callback;
            this.observed = [];
            this.disconnected = false;
            this.disconnectCalls = 0;
            observers.push(this);
        }

        observe(element) {
            this.observed.push(element);
        }

        disconnect() {
            this.disconnected = true;
            this.disconnectCalls += 1;
            this.observed = [];
        }

        trigger() {
            this.callback(this.observed.map((target) => ({ target })));
        }
    };
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    delete globalThis.ResizeObserver;
});

test.serial("collapses overflowing content and exposes the measured state", async () => {
    await mount();
    setContentHeight(480);

    mounted.controller.refresh();

    expect(root().dataset.state).toBe("collapsed");
    expect(root().hasAttribute("data-ready")).toBe(true);
    expect(root().style.getPropertyValue("--read-more-collapsed-height")).toBe("200px");
    expect(root().style.getPropertyValue("--read-more-expanded-height")).toBe("480px");
    expect(trigger().hidden).toBe(false);
    expect(fade().hidden).toBe(false);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
});

test.serial("measures the constrained viewport instead of the content box", async () => {
    await mount();
    setContentHeight(300);
    setViewportHeight(350);

    mounted.controller.refresh();

    expect(root().style.getPropertyValue("--read-more-expanded-height")).toBe("350px");
});

test.serial("toggles state, labels, icon semantics, and dispatches change", async () => {
    await mount();
    setContentHeight(480);
    mounted.controller.refresh();
    const changes = [];
    const frames = [];
    globalThis.requestAnimationFrame = (callback) => {
        frames.push(callback);

        return frames.length;
    };
    root().addEventListener("read-more:change", (event) => changes.push(event.detail));

    trigger().click();
    await wait(0);

    expect(root().dataset.state).toBe("expanded");
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
    expect(moreLabel().hidden).toBe(true);
    expect(lessLabel().hidden).toBe(false);
    expect(fade().hidden).toBe(true);
    expect(icon().dataset.state).toBe("expanded");
    expect(root().hasAttribute("data-transitioning")).toBe(true);

    frames.shift()();
    frames.shift()();

    expect(root().hasAttribute("data-transitioning")).toBe(false);

    mounted.controller.collapse();

    expect(root().dataset.state).toBe("collapsed");
    expect(changes).toEqual([{ expanded: true }, { expanded: false }]);
});

test.serial("leaves short content fully visible and preserves the requested state", async () => {
    await mount({ expanded: true });
    setContentHeight(160);

    mounted.controller.refresh();

    expect(root().dataset.state).toBe("static");
    expect(trigger().hidden).toBe(true);
    expect(fade().hidden).toBe(true);
    expect(trigger().getAttribute("aria-expanded")).toBe("false");
    expect(mounted.controller.expandedValue).toBe(true);

    setContentHeight(480);
    observers[0].trigger();
    await wait(20);

    expect(root().dataset.state).toBe("expanded");
    expect(trigger().getAttribute("aria-expanded")).toBe("true");
});

test.serial("does not overwrite an application collapsed-height custom property", async () => {
    await mount();
    root().style.setProperty("--read-more-collapsed-height", "12rem");
    setContentHeight(480);

    mounted.controller.refresh();

    expect(root().style.getPropertyValue("--read-more-collapsed-height")).toBe("12rem");
});

test.serial("observes replacement content after a Turbo morph and disconnects cleanly", async () => {
    await mount();
    const original = content();
    const replacement = document.createElement("div");
    replacement.dataset.readMoreTarget = "content";
    replacement.textContent = "Replacement";
    setElementHeight(replacement, 520);
    setViewportHeight(520);

    original.replaceWith(replacement);
    await wait(0);

    expect(observers[0].observed).toEqual([replacement]);
    expect(observers[0].disconnectCalls).toBe(1);
    expect(root().style.getPropertyValue("--read-more-expanded-height")).toBe("520px");

    await mounted.cleanup();
    mounted = null;

    expect(observers[0].disconnected).toBe(true);
    expect(observers[0].disconnectCalls).toBe(2);
});

test.serial("coalesces ResizeObserver notifications into one animation frame", async () => {
    await mount();
    setContentHeight(480);
    setViewportHeight(480);
    const frames = [];
    globalThis.requestAnimationFrame = (callback) => {
        frames.push(callback);

        return frames.length;
    };

    observers[0].trigger();
    observers[0].trigger();

    expect(frames).toHaveLength(1);

    frames[0]();

    expect(root().dataset.state).toBe("collapsed");
    expect(root().style.getPropertyValue("--read-more-expanded-height")).toBe("480px");
});

test.serial("cancels a pending observer refresh on disconnect", async () => {
    await mount();
    setContentHeight(480);
    const cancelled = [];
    globalThis.requestAnimationFrame = () => 42;
    globalThis.cancelAnimationFrame = (id) => cancelled.push(id);

    observers[0].trigger();

    await mounted.cleanup();
    mounted = null;

    expect(cancelled).toEqual([42]);
});

test.serial("is a safe static fallback when required targets are missing", async () => {
    mounted = await mountController(
        "read-more",
        ReadMoreController,
        '<div data-controller="read-more" data-state="collapsed"><button data-read-more-target="trigger"></button></div>',
    );

    expect(() => mounted.controller.refresh()).not.toThrow();
    expect(root().dataset.state).toBe("static");
    expect(root().hasAttribute("data-ready")).toBe(true);
    expect(trigger().hidden).toBe(true);
});

// Target removal and replacement are covered in tests/Browser/read_more_controller.pw.js:
// they hinge on MutationObserver delivery, which happy-dom drains unreliably once many
// files share a process.

test.serial("moves focus to content before hiding the active trigger", async () => {
    await mount();
    setContentHeight(480);
    mounted.controller.refresh();
    trigger().focus();
    expect(document.activeElement).toBe(trigger());

    setContentHeight(120);
    mounted.controller.refresh();

    expect(document.activeElement).toBe(content());
    expect(trigger().hidden).toBe(true);
});

async function mount({ expanded = false } = {}) {
    mounted = await mountController(
        "read-more",
        ReadMoreController,
        `
            <section
                data-controller="read-more"
                data-state="${expanded ? "expanded" : "collapsed"}"
                data-read-more-collapsed-height-value="200"
                data-read-more-expanded-value="${expanded}"
            >
                <div data-read-more-target="viewport">
                    <div data-read-more-target="content" tabindex="-1">Content</div>
                    <div data-read-more-target="fade" hidden></div>
                </div>
                <button data-read-more-target="trigger" data-action="read-more#toggle" hidden>
                    <span data-read-more-target="moreLabel">More</span>
                    <span data-read-more-target="lessLabel" hidden>Less</span>
                    <span data-read-more-target="icon"></span>
                </button>
            </section>
        `,
    );
}

function setContentHeight(height) {
    setElementHeight(content(), height);
    setViewportHeight(height);
}

function setViewportHeight(height) {
    setElementHeight(viewport(), height);
}

function setElementHeight(element, height) {
    Object.defineProperty(element, "scrollHeight", {
        configurable: true,
        value: height,
    });
}

function root() {
    return mounted.root;
}

function content() {
    return root().querySelector('[data-read-more-target="content"]');
}

function viewport() {
    return root().querySelector('[data-read-more-target="viewport"]');
}

function trigger() {
    return root().querySelector('[data-read-more-target="trigger"]');
}

function fade() {
    return root().querySelector('[data-read-more-target="fade"]');
}

function moreLabel() {
    return root().querySelector('[data-read-more-target="moreLabel"]');
}

function lessLabel() {
    return root().querySelector('[data-read-more-target="lessLabel"]');
}

function icon() {
    return root().querySelector('[data-read-more-target="icon"]');
}
