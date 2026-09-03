import { afterEach, beforeEach, expect, test } from "bun:test";

import AnimatedNumberController from "../../resources/js/controllers/animated_number_controller.js";
import RevealController from "../../resources/js/controllers/reveal_controller.js";
import { mountController, mountMultipleControllers, wait } from "../../resources/js/helpers/test_stimulus.js";

let mounted;
let observers;
let rafCallbacks;

class FakeIntersectionObserver {
    constructor(callback, options) {
        this.callback = callback;
        this.options = options;
        this.observed = [];
        this.disconnected = false;
        observers.push(this);
    }

    observe(element) {
        this.observed.push(element);
    }
    unobserve(element) {
        this.observed = this.observed.filter((item) => item !== element);
    }
    disconnect() {
        this.disconnected = true;
        this.observed = [];
    }
    trigger(entries) {
        this.callback(entries, this);
    }
}

beforeEach(() => {
    observers = [];
    rafCallbacks = new Map();
    globalThis.IntersectionObserver = FakeIntersectionObserver;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test("load trigger indexes explicit items and dispatches shown", async () => {
    mounted = await mount(`
        <section data-controller="reveal">
            <article data-reveal-item></article>
            <div><article data-reveal-item></article></div>
        </section>
    `);
    const events = [];
    mounted.root.addEventListener("reveal:shown", (event) => events.push(event));

    flushRaf();

    expect(mounted.controller.items.map((item) => item.style.getPropertyValue("--reveal-index"))).toEqual(["0", "1"]);
    expect(events).toHaveLength(1);
});

test("direct-child mode excludes nested descendants", async () => {
    mounted = await mount(`
        <section data-controller="reveal" data-reveal-children>
            <article><span data-reveal-item></span></article>
            <article></article>
        </section>
    `);

    expect(mounted.controller.items).toEqual([...mounted.root.children]);
});

test("explicit mode excludes items owned by a nested Reveal", async () => {
    mounted = await mount(`
        <section data-controller="reveal">
            <article data-reveal-item id="outer"></article>
            <div data-controller="reveal"><article data-reveal-item id="inner"></article></div>
        </section>
    `);

    expect(mounted.controller.items.map((item) => item.id)).toEqual(["outer"]);
});

test("scroll trigger arms only offscreen items and observes them", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll"
            data-reveal-threshold-value="0.25" data-reveal-root-margin-value="0px 0px -20% 0px">
            <article data-reveal-item id="visible"></article>
            <article data-reveal-item id="pending"></article>
        </section>
    `,
        (root) => {
            root.querySelector("#visible").getBoundingClientRect = () => ({ top: 10, bottom: 50, left: 0, right: 50 });
            root.querySelector("#pending").getBoundingClientRect = () => ({
                top: 2000,
                bottom: 2050,
                left: 0,
                right: 50,
            });
        },
    );

    expect(mounted.root.querySelector("#visible").hasAttribute("data-reveal-armed")).toBeFalse();
    expect(mounted.root.querySelector("#pending").hasAttribute("data-reveal-armed")).toBeTrue();
    expect(observers[0].options.rootMargin).toBe("0px 0px -20% 0px");
    expect(observers[0].options.threshold).toHaveLength(21);
    expect(observers[0].options.threshold[0]).toBe(0);
    expect(observers[0].options.threshold.at(-1)).toBe(0.25);
    expect(observers[0].observed).toEqual([mounted.root.querySelector("#pending")]);
});

test("scroll trigger stays visible when IntersectionObserver is unavailable", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root, testWindow) => {
            globalThis.IntersectionObserver = testWindow.IntersectionObserver = undefined;
            root.firstElementChild.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
        },
    );

    expect(mounted.root.firstElementChild.hasAttribute("data-reveal-armed")).toBeFalse();
});

test("reconnect observes an item that was armed before disconnect", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
        },
    );
    const item = mounted.root.firstElementChild;
    const firstObserver = observers[0];

    mounted.controller.disconnect();
    mounted.controller.connect();

    expect(firstObserver.disconnected).toBeTrue();
    expect(item.hasAttribute("data-reveal-armed")).toBeTrue();
    expect(observers.at(-1).observed).toContain(item);
});

test("intersecting items are released as a fresh batch and shown is coalesced", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item id="one"></article>
            <article data-reveal-item id="two"></article>
        </section>
    `,
        (root) => {
            for (const item of root.children) {
                item.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
            }
        },
    );
    const shown = [];
    mounted.root.addEventListener("reveal:shown", () => shown.push(true));
    const entries = [...mounted.root.children].map((target) => ({ target, isIntersecting: true }));

    observers[0].trigger(entries);
    observers[0].trigger([]);
    flushRaf();

    expect([...mounted.root.children].map((item) => item.style.getPropertyValue("--reveal-index"))).toEqual(["0", "1"]);
    expect([...mounted.root.children].every((item) => !item.hasAttribute("data-reveal-armed"))).toBeTrue();
    expect(shown).toHaveLength(1);
});

test("a tall item uses the maximum achievable intersection ratio", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({
                top: 2000,
                bottom: 2700,
                left: 0,
                right: 50,
                height: 700,
            });
        },
    );
    const item = mounted.root.firstElementChild;
    const lastReachableThreshold = observers[0].options.threshold.at(-2);

    observers[0].trigger([
        {
            target: item,
            isIntersecting: true,
            intersectionRatio: lastReachableThreshold,
            boundingClientRect: { height: 700 },
            rootBounds: { height: 100 },
        },
    ]);

    expect(item.hasAttribute("data-reveal-armed")).toBeFalse();
});

test("document end releases items left in the root-margin dead zone", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
        },
    );
    Object.defineProperty(window, "scrollY", { configurable: true, value: 900 });
    Object.defineProperty(window, "innerHeight", { configurable: true, value: 100 });
    Object.defineProperty(document.documentElement, "scrollHeight", { configurable: true, value: 1000 });

    window.dispatchEvent(new Event("scroll"));

    expect(mounted.root.firstElementChild.hasAttribute("data-reveal-armed")).toBeFalse();
});

test("document end keeps armed items above the viewport pending", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item id="above"></article>
            <article data-reveal-item id="below"></article>
        </section>
    `,
        (root) => {
            root.querySelector("#above").getBoundingClientRect = () => ({ top: -100, bottom: -50, left: 0, right: 50 });
            root.querySelector("#below").getBoundingClientRect = () => ({ top: 90, bottom: 140, left: 0, right: 50 });
        },
    );
    Object.defineProperty(window, "scrollY", { configurable: true, value: 900 });
    Object.defineProperty(window, "innerHeight", { configurable: true, value: 100 });
    Object.defineProperty(document.documentElement, "scrollHeight", { configurable: true, value: 1000 });

    window.dispatchEvent(new Event("scroll"));

    expect(mounted.root.querySelector("#above").hasAttribute("data-reveal-armed")).toBeTrue();
    expect(mounted.root.querySelector("#below").hasAttribute("data-reveal-armed")).toBeFalse();
});

test("items added later are indexed and observed without rearming completed items", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item id="complete"></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
        },
    );
    const complete = mounted.root.firstElementChild;
    observers[0].trigger([{ target: complete, isIntersecting: true }]);
    const added = document.createElement("article");
    added.dataset.revealItem = "";
    added.getBoundingClientRect = () => ({ top: 2100, bottom: 2150, left: 0, right: 50 });

    mounted.root.appendChild(added);
    mounted.controller.refreshItems();

    expect(complete.hasAttribute("data-reveal-armed")).toBeFalse();
    expect(added.style.getPropertyValue("--reveal-index")).toBe("1");
    expect(added.hasAttribute("data-reveal-armed")).toBeTrue();
    expect(observers[0].observed).toContain(added);
});

test("mutations do not rearm an initially visible item after it leaves the viewport", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 10, bottom: 50, left: 0, right: 50 });
        },
    );
    const item = mounted.root.firstElementChild;
    item.getBoundingClientRect = () => ({ top: -100, bottom: -50, left: 0, right: 50 });

    item.appendChild(document.createElement("span"));
    mounted.controller.refreshItems();

    expect(item.hasAttribute("data-reveal-armed")).toBeFalse();
});

test("a visible item added later dispatches a new shown event", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 10, bottom: 50, left: 0, right: 50 });
        },
    );
    const shown = [];
    mounted.root.addEventListener("reveal:shown", () => shown.push(true));
    const added = document.createElement("article");
    added.dataset.revealItem = "";
    added.getBoundingClientRect = () => ({ top: 60, bottom: 100, left: 0, right: 50 });

    mounted.root.appendChild(added);
    const visible = mounted.controller.refreshItems();
    if (visible.length > 0) mounted.controller.fire(visible);

    expect(shown).toHaveLength(1);
    expect(added.hasAttribute("data-reveal-armed")).toBeFalse();
});

test("shown restarts descendant animated-number controllers", async () => {
    mounted = await mount(`
        <section data-controller="reveal">
            <article data-reveal-item><span data-controller="animated-number"></span></article>
        </section>
    `);
    let calls = 0;
    mounted.controller.application.getControllerForElementAndIdentifier = () => ({
        animate() {
            calls++;
        },
    });

    mounted.controller.fire([mounted.root.firstElementChild]);

    expect(calls).toBe(1);
});

test("shown preserves a real lazy animated-number observer", async () => {
    mounted = await mountMultipleControllers(
        { reveal: RevealController, "animated-number": AnimatedNumberController },
        `
            <section data-controller="reveal">
                <article data-reveal-item>
                    <span data-controller="animated-number"
                        data-animated-number-lazy-value="true"
                        data-animated-number-start-value="0"
                        data-animated-number-end-value="100"
                        data-animated-number-duration-value="1000">0</span>
                </article>
            </section>
        `,
    );
    const number = mounted.root.querySelector('[data-controller="animated-number"]');
    const controller = mounted.getController("animated-number", number);

    await waitFor(() => mounted.root.dataset.revealState === "done");

    expect(controller.lazyValue).toBeTrue();
    expect(controller.observer).not.toBeNull();
    expect(controller.observer.observed).toContain(number);
    expect(number.textContent).toBe("0");
});

test("replacement streams stamp incoming reveal items before insertion", async () => {
    mounted = await mount(
        `<section data-controller="reveal"><article id="current" data-reveal-item></article></section>`,
    );
    const template = document.createElement("template");
    template.innerHTML = `<article data-reveal-item></article><div><span data-reveal-item></span></div>`;
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "replace");
    stream.setAttribute("target", "current");
    Object.defineProperty(stream, "templateElement", { value: template });

    document.dispatchEvent(
        new CustomEvent("turbo:before-stream-render", {
            detail: { newStream: stream },
        }),
    );

    expect(
        [...template.content.querySelectorAll("[data-reveal-item]")].every((item) =>
            item.hasAttribute("data-reveal-skip"),
        ),
    ).toBeTrue();
});

test("replacement streams leave unrelated incoming elements untouched", async () => {
    mounted = await mount(`<section data-controller="reveal"><article data-reveal-item></article></section>`);
    const target = document.body.appendChild(document.createElement("div"));
    target.id = "unrelated";
    const template = document.createElement("template");
    template.innerHTML = `<article id="replacement" data-reveal-item></article>`;
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "replace");
    stream.setAttribute("target", "unrelated");
    Object.defineProperty(stream, "templateElement", { value: template });

    document.dispatchEvent(
        new CustomEvent("turbo:before-stream-render", {
            detail: { newStream: stream },
        }),
    );

    expect(template.content.querySelector("#replacement").hasAttribute("data-reveal-skip")).toBeFalse();
    target.remove();
});

test("replacement stream target matching uses Reveal markup rather than controller connection", async () => {
    mounted = await mount(`<section data-controller="reveal"><article data-reveal-item></article></section>`);
    const unconnectedRoot = document.body.appendChild(document.createElement("section"));
    unconnectedRoot.innerHTML = `<div id="pending-reveal-target"></div>`;
    unconnectedRoot.setAttribute("data-controller", "reveal");
    const template = document.createElement("template");
    template.innerHTML = `<article id="replacement" data-reveal-item></article>`;
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "replace");
    stream.setAttribute("target", "pending-reveal-target");
    Object.defineProperty(stream, "templateElement", { value: template });

    expect(mounted.application.getControllerForElementAndIdentifier(unconnectedRoot, "reveal")).toBeNull();
    document.dispatchEvent(
        new CustomEvent("turbo:before-stream-render", {
            detail: { newStream: stream },
        }),
    );

    expect(template.content.querySelector("#replacement").hasAttribute("data-reveal-skip")).toBeTrue();
    unconnectedRoot.remove();
});

test("one related stream target stamps the template shared with unrelated targets", async () => {
    mounted = await mount(`
        <section data-controller="reveal">
            <article class="stream-destination" data-reveal-item></article>
        </section>
    `);
    const unrelatedTarget = document.body.appendChild(document.createElement("div"));
    unrelatedTarget.className = "stream-destination";
    const template = document.createElement("template");
    template.innerHTML = `<article id="replacement" data-reveal-item></article>`;
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "update");
    stream.setAttribute("targets", ".stream-destination");
    Object.defineProperty(stream, "templateElement", { value: template });

    document.dispatchEvent(
        new CustomEvent("turbo:before-stream-render", {
            detail: { newStream: stream },
        }),
    );

    expect(template.content.querySelector("#replacement").hasAttribute("data-reveal-skip")).toBeTrue();
    unrelatedTarget.remove();
});

test("replacement streams stamp unmarked children replacing direct-child items", async () => {
    mounted = await mount(`
        <section data-controller="reveal" data-reveal-children>
            <article id="current"></article>
        </section>
    `);
    const template = document.createElement("template");
    template.innerHTML = `<article id="replacement"></article>`;
    const stream = document.createElement("turbo-stream");
    stream.setAttribute("action", "replace");
    stream.setAttribute("target", "current");
    Object.defineProperty(stream, "templateElement", { value: template });

    document.dispatchEvent(
        new CustomEvent("turbo:before-stream-render", {
            detail: { newStream: stream },
        }),
    );

    expect(template.content.querySelector("#replacement").hasAttribute("data-reveal-skip")).toBeTrue();
});

test("once false observes visible items and rearms only after they leave", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll" data-reveal-once-value="false">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 10, bottom: 50, left: 0, right: 50 });
        },
    );
    const item = mounted.root.firstElementChild;
    const shown = [];
    mounted.root.addEventListener("reveal:shown", () => shown.push(true));
    flushRaf();

    expect(observers[0].observed).toContain(item);
    observers[0].trigger([{ target: item, isIntersecting: true }]);
    flushRaf();
    expect(item.hasAttribute("data-reveal-armed")).toBeFalse();
    expect(shown).toHaveLength(1);

    observers[0].trigger([{ target: item, isIntersecting: true, intersectionRatio: 0.05 }]);
    expect(item.hasAttribute("data-reveal-armed")).toBeFalse();

    observers[0].trigger([{ target: item, isIntersecting: false }]);
    expect(item.hasAttribute("data-reveal-armed")).toBeTrue();
    observers[0].trigger([{ target: item, isIntersecting: true }]);
    flushRaf();
    expect(item.hasAttribute("data-reveal-armed")).toBeFalse();
    expect(shown).toHaveLength(2);
});

test("Turbo visit records document scope and cache cleanup removes transient state", async () => {
    mounted = await mount(
        `<section data-controller="reveal" data-reveal-state="done"><article data-reveal-item data-reveal-armed></article></section>`,
    );

    document.dispatchEvent(new Event("turbo:visit"));
    document.dispatchEvent(new Event("turbo:before-cache"));

    expect(document.documentElement.hasAttribute("data-reveal-booted")).toBeTrue();
    expect(mounted.root.hasAttribute("data-reveal-state")).toBeFalse();
    expect(mounted.root.firstElementChild.hasAttribute("data-reveal-armed")).toBeFalse();
});

test("disconnect removes listeners, observers, and pending frames", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root) => {
            root.firstElementChild.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
        },
    );
    mounted.controller.scheduleFire();
    mounted.root.insertAdjacentHTML("beforeend", '<span data-controller="animated-number"></span>');
    mounted.controller.restartCounters([mounted.root]);
    mounted.controller.restartCounters([mounted.root]);
    const observer = observers[0];

    expect(rafCallbacks.size).toBe(2);

    await mounted.cleanup();
    mounted = null;
    document.documentElement.removeAttribute("data-reveal-booted");
    document.dispatchEvent(new Event("turbo:visit"));

    expect(observer.disconnected).toBeTrue();
    expect(rafCallbacks.size).toBe(0);
    expect(document.documentElement.hasAttribute("data-reveal-booted")).toBeFalse();
});

test("an unrendered root waits without polling every animation frame", async () => {
    mounted = await mount(`
        <section data-controller="reveal" hidden>
            <article data-reveal-item></article>
        </section>
    `);

    flushRaf();

    expect(rafCallbacks.size).toBe(0);
});

test("reduced motion never arms scroll items", async () => {
    mounted = await mount(
        `
        <section data-controller="reveal" data-reveal-trigger-value="scroll">
            <article data-reveal-item></article>
        </section>
    `,
        (root, testWindow) => {
            testWindow.matchMedia = () => ({ matches: true });
            root.firstElementChild.getBoundingClientRect = () => ({ top: 2000, bottom: 2050, left: 0, right: 50 });
        },
    );

    expect(mounted.root.firstElementChild.hasAttribute("data-reveal-armed")).toBeFalse();
    expect(observers).toHaveLength(0);
});

function flushRaf() {
    const callbacks = [...rafCallbacks.values()];
    rafCallbacks.clear();
    callbacks.forEach((callback) => callback(16));
}

async function mount(html, prepare = null) {
    class DeferredRevealController extends RevealController {
        connect() {}
    }

    const result = await mountController("reveal", DeferredRevealController, html);
    result.application.unload("reveal");
    await wait(0);

    let nextRaf = 1;
    const requestFrame = (callback) => {
        const id = nextRaf++;
        rafCallbacks.set(id, callback);
        return id;
    };
    const cancelFrame = (id) => rafCallbacks.delete(id);
    globalThis.IntersectionObserver = result.window.IntersectionObserver = FakeIntersectionObserver;
    globalThis.requestAnimationFrame = result.window.requestAnimationFrame = requestFrame;
    globalThis.cancelAnimationFrame = result.window.cancelAnimationFrame = cancelFrame;
    prepare?.(result.root, result.window);

    observers = [];
    result.application.register("reveal", RevealController);
    await wait(0);
    result.controller = result.application.getControllerForElementAndIdentifier(result.root, "reveal");

    return result;
}

async function waitFor(predicate) {
    for (let attempt = 0; attempt < 200; attempt += 1) {
        if (predicate()) return;
        await wait(10);
    }

    throw new Error("Timed out waiting for Reveal controller state.");
}
