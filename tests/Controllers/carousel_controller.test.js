import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

// --- Embla mock ---
// The controller does `import EmblaCarousel from "embla-carousel"`. We swap it
// for a controllable fake so we can spy on calls and emit lifecycle events.

const emblaState = {
    instance: null,
    calls: [],
    snaps: [0, 0.5, 1],
    selected: 0,
    previous: 0,
    canPrev: true,
    canNext: true,
    handlers: {},
};

function createInstance() {
    const instance = {
        scrollNext: mock(() => {}),
        scrollPrev: mock(() => {}),
        scrollTo: mock(() => {}),
        scrollSnapList: () => emblaState.snaps,
        selectedScrollSnap: () => emblaState.selected,
        previousScrollSnap: () => emblaState.previous,
        canScrollPrev: () => emblaState.canPrev,
        canScrollNext: () => emblaState.canNext,
        on: mock((event, handler) => {
            (emblaState.handlers[event] = emblaState.handlers[event] || []).push(handler);
            return instance;
        }),
        off: mock(() => instance),
        destroy: mock(() => {}),
        reInit: mock(() => {}),
        plugins: () => ({}),
        slidesInView: () => [],
    };
    return instance;
}

const emblaFactory = mock((node, options) => {
    const instance = createInstance();
    emblaState.instance = instance;
    emblaState.calls.push({ node, options });
    return instance;
});

mock.module("embla-carousel", () => ({ default: emblaFactory }));
mock.module("./carousel.css", () => ({}));

const CarouselController = (await import("../../resources/js/controllers/carousel_controller.js")).default;

let mounted;

beforeEach(() => {
    emblaState.instance = null;
    emblaState.calls = [];
    emblaState.snaps = [0, 0.5, 1];
    emblaState.selected = 0;
    emblaState.previous = 0;
    emblaState.canPrev = true;
    emblaState.canNext = true;
    emblaState.handlers = {};
    emblaFactory.mockClear();
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- Mounting ---

test.serial("mounts Embla on the viewport target", async () => {
    await mount();

    expect(emblaFactory).toHaveBeenCalledTimes(1);
    const call = emblaState.calls[0];
    expect(call.node).toBe(viewport());
    expect(call.options).toEqual({});
});

test.serial("falls back to the controller element when no viewport target exists", async () => {
    mounted = await mountController(
        "carousel",
        CarouselController,
        `
        <div data-controller="carousel">
            <div data-carousel-target="container">
                <div>slide 1</div>
            </div>
        </div>`,
    );

    const root = document.querySelector('[data-controller~="carousel"]');
    expect(emblaState.calls[0].node).toBe(root);
});

test.serial("forwards optionsValue to Embla", async () => {
    await mount({ options: { loop: true, align: "center" } });

    expect(emblaState.calls[0].options).toEqual({ loop: true, align: "center" });
});

// --- Actions ---

test.serial("next() and prev() call Embla scrollNext/scrollPrev", async () => {
    await mount();

    mounted.controller.next();
    mounted.controller.prev();

    expect(emblaState.instance.scrollNext).toHaveBeenCalledTimes(1);
    expect(emblaState.instance.scrollPrev).toHaveBeenCalledTimes(1);
});

test.serial("scrollTo passes the index param to Embla", async () => {
    await mount();

    mounted.controller.scrollTo({ params: { index: 2 } });

    expect(emblaState.instance.scrollTo).toHaveBeenCalledWith(2);
});

test.serial("scrollTo defaults to 0 when no index param is given", async () => {
    await mount();

    mounted.controller.scrollTo({ params: {} });

    expect(emblaState.instance.scrollTo).toHaveBeenCalledWith(0);
});

// --- Dots ---

test.serial("renders one dot per snap by cloning the dot template", async () => {
    emblaState.snaps = [0, 0.25, 0.5, 0.75, 1];
    await mount();

    const dots = dotEls();
    expect(dots.length).toBe(5);
    dots.forEach((dot, index) => {
        expect(dot.tagName).toBe("BUTTON");
        expect(dot.dataset.carouselIndexParam).toBe(String(index));
    });
});

test.serial("applies the active-dot class to the selected snap on init", async () => {
    emblaState.snaps = [0, 0.5, 1];
    emblaState.selected = 1;
    await mount();

    const dots = dotEls();
    expect(dots[1].classList.contains("is-active")).toBe(true);
    expect(dots[0].classList.contains("is-active")).toBe(false);
});

test.serial("moves the active-dot class when Embla fires select", async () => {
    await mount();

    emblaState.previous = 0;
    emblaState.selected = 2;
    emit("select");

    const dots = dotEls();
    expect(dots[0].classList.contains("is-active")).toBe(false);
    expect(dots[2].classList.contains("is-active")).toBe(true);
});

// --- Prev/Next disabled state ---

test.serial("disables prevButton when canScrollPrev is false", async () => {
    emblaState.canPrev = false;
    emblaState.canNext = true;
    await mount();

    expect(prevButton().disabled).toBe(true);
    expect(nextButton().disabled).toBe(false);
});

test.serial("toggles the disabled-nav class on prev/next based on can-scroll", async () => {
    emblaState.canPrev = false;
    emblaState.canNext = true;
    await mount();

    expect(prevButton().classList.contains("is-disabled")).toBe(true);
    expect(nextButton().classList.contains("is-disabled")).toBe(false);

    emblaState.canPrev = true;
    emblaState.canNext = false;
    emit("select");

    expect(prevButton().classList.contains("is-disabled")).toBe(false);
    expect(nextButton().classList.contains("is-disabled")).toBe(true);
});

// --- Events dispatched ---

test.serial("dispatches carousel:select when Embla fires select", async () => {
    await mount();

    let detail = null;
    mounted.root.addEventListener("carousel:select", (e) => (detail = e.detail));

    emblaState.previous = 0;
    emblaState.selected = 2;
    emit("select");

    expect(detail).not.toBeNull();
    expect(detail.index).toBe(2);
    expect(detail.previousIndex).toBe(0);
});

test.serial("dispatches carousel:settle when Embla fires settle", async () => {
    await mount();

    let fired = false;
    mounted.root.addEventListener("carousel:settle", () => (fired = true));

    emit("settle");

    expect(fired).toBe(true);
});

// --- Reactivity ---

test.serial("calls reInit when optionsValue changes", async () => {
    await mount();
    emblaState.instance.reInit.mockClear();

    mounted.controller.optionsValue = { loop: true };
    await wait(0);

    expect(emblaState.instance.reInit).toHaveBeenCalled();
    expect(emblaState.instance.reInit.mock.calls[0][0]).toEqual({ loop: true });
});

test.serial("re-renders dots when the snap list changes on reInit", async () => {
    emblaState.snaps = [0, 0.5, 1];
    await mount();

    expect(dotEls().length).toBe(3);

    emblaState.snaps = [0, 0.2, 0.4, 0.6, 0.8];
    emit("reInit");

    const dots = dotEls();
    expect(dots.length).toBe(5);
    dots.forEach((dot, index) => {
        expect(dot.dataset.carouselIndexParam).toBe(String(index));
    });
});

// --- Lifecycle ---

test.serial("destroys the Embla instance on disconnect", async () => {
    await mount();
    const instance = emblaState.instance;

    mounted.controller.disconnect();

    expect(instance.destroy).toHaveBeenCalled();
});

test.serial("teardownForCache destroys the Embla instance before Turbo caches the page", async () => {
    await mount();
    const instance = emblaState.instance;

    window.dispatchEvent(new CustomEvent("turbo:before-cache"));
    await wait(0);

    expect(instance.destroy).toHaveBeenCalled();
});

// --- Helpers ---

function viewport() {
    return document.querySelector('[data-carousel-target="viewport"]');
}

function dotEls() {
    return [...document.querySelectorAll('[data-carousel-target="dotList"] button')];
}

function prevButton() {
    return document.querySelector('[data-carousel-target="prevButton"]');
}

function nextButton() {
    return document.querySelector('[data-carousel-target="nextButton"]');
}

function emit(event) {
    const handlers = emblaState.handlers[event] ?? [];
    handlers.forEach((handler) => handler(emblaState.instance, event));
}

async function mount({ options = {} } = {}) {
    const optsAttr = `data-carousel-options-value='${JSON.stringify(options)}'`;

    mounted = await mountController(
        "carousel",
        CarouselController,
        `
        <div data-controller="carousel"
             ${optsAttr}
             data-carousel-active-dot-class="is-active"
             data-carousel-disabled-nav-class="is-disabled"
             data-action="turbo:before-cache@window->carousel#teardownForCache">
            <div data-carousel-target="viewport">
                <div data-carousel-target="container">
                    <div>slide 1</div>
                    <div>slide 2</div>
                    <div>slide 3</div>
                </div>
            </div>
            <button type="button" data-carousel-target="prevButton" data-action="carousel#prev">‹</button>
            <button type="button" data-carousel-target="nextButton" data-action="carousel#next">›</button>
            <div data-carousel-target="dotList"></div>
            <template data-carousel-target="dotTemplate">
                <button type="button" data-action="carousel#scrollTo"></button>
            </template>
        </div>`,
    );
}
