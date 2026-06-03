import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

// --- Embla mock ---
// The controller does `import EmblaCarousel from "embla-carousel"`. We swap it
// for a controllable fake so we can spy on calls and emit lifecycle events.

const emblaState = {
    instance: null,
    calls: [],
    snaps: [0, 0.5, 1],
    slideNodes: [{}, {}, {}],
    selected: 0,
    previous: 0,
    progress: 0,
    canPrev: true,
    canNext: true,
    inView: [],
    plugins: {},
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
        plugins: () => emblaState.plugins,
        slidesInView: () => emblaState.inView,
        slideNodes: () => emblaState.slideNodes,
        scrollProgress: () => emblaState.progress,
    };
    return instance;
}

const emblaFactory = mock((node, options, plugins) => {
    const instance = createInstance();
    emblaState.instance = instance;
    emblaState.calls.push({ node, options, plugins });
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
    emblaState.slideNodes = [{}, {}, {}];
    emblaState.selected = 0;
    emblaState.previous = 0;
    emblaState.progress = 0;
    emblaState.canPrev = true;
    emblaState.canNext = true;
    emblaState.inView = [];
    emblaState.plugins = {};
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
            <div data-carousel-container>
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

test.serial("does not rebuild the dots on select (keeps dot nodes stable)", async () => {
    await mount();
    const firstDot = dotEls()[0];

    emblaState.selected = 1;
    emit("select");

    expect(dotEls()[0]).toBe(firstDot); // same node — not re-created
});

test.serial("re-renders dots when slidesChanged fires", async () => {
    emblaState.snaps = [0, 0.5, 1];
    await mount();
    expect(dotEls().length).toBe(3);

    emblaState.snaps = [0, 0.25, 0.5, 0.75];
    emit("slidesChanged");

    expect(dotEls().length).toBe(4);
});

// --- Dot accessibility ---

test.serial("labels each dot with an aria-label", async () => {
    emblaState.snaps = [0, 0.5, 1];
    await mount();

    const dots = dotEls();
    expect(dots[0].getAttribute("aria-label")).toBe("Go to slide 1");
    expect(dots[2].getAttribute("aria-label")).toBe("Go to slide 3");
});

test.serial("marks the active dot with aria-current and moves it on select", async () => {
    emblaState.selected = 1;
    await mount();

    const dots = dotEls();
    expect(dots[1].getAttribute("aria-current")).toBe("true");
    expect(dots[0].hasAttribute("aria-current")).toBe(false);

    emblaState.selected = 2;
    emit("select");

    expect(dots[2].getAttribute("aria-current")).toBe("true");
    expect(dots[1].hasAttribute("aria-current")).toBe(false);
});

// --- Prev/Next disabled state ---

test.serial("toggles the native disabled state on prev/next based on can-scroll", async () => {
    emblaState.canPrev = false;
    emblaState.canNext = true;
    await mount();

    expect(prevButton().disabled).toBe(true);
    expect(nextButton().disabled).toBe(false);

    emblaState.canPrev = true;
    emblaState.canNext = false;
    emit("select");

    expect(prevButton().disabled).toBe(false);
    expect(nextButton().disabled).toBe(true);
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

test.serial("dispatches carousel:slides-in-view with the visible indexes", async () => {
    await mount();

    let detail = null;
    mounted.root.addEventListener("carousel:slides-in-view", (e) => (detail = e.detail));

    emblaState.inView = [1, 2];
    emit("slidesInView");

    expect(detail).not.toBeNull();
    expect(detail.inView).toEqual([1, 2]);
});

test.serial("dispatches carousel:slides-changed when Embla fires slidesChanged", async () => {
    await mount();

    let fired = false;
    mounted.root.addEventListener("carousel:slides-changed", () => (fired = true));

    emit("slidesChanged");

    expect(fired).toBe(true);
});

test.serial("dispatches carousel:scroll with the scroll progress", async () => {
    await mount();

    let detail = null;
    mounted.root.addEventListener("carousel:scroll", (e) => (detail = e.detail));

    emblaState.progress = 0.42;
    emit("scroll");

    expect(detail).not.toBeNull();
    expect(detail.progress).toBe(0.42);
});

// --- Grouped dots (aria-label) ---

test.serial('labels dots "slide" when each snap is one slide', async () => {
    emblaState.snaps = [0, 0.5, 1];
    emblaState.slideNodes = [{}, {}, {}];
    await mount();

    expect(dotEls()[0].getAttribute("aria-label")).toBe("Go to slide 1");
});

test.serial('labels dots "group" when slides are grouped (snaps < slides)', async () => {
    emblaState.snaps = [0, 0.5];
    emblaState.slideNodes = [{}, {}, {}, {}];
    await mount();

    const dots = dotEls();
    expect(dots[0].getAttribute("aria-label")).toBe("Go to group 1");
    expect(dots[1].getAttribute("aria-label")).toBe("Go to group 2");
});

// --- Autoplay actions ---

test.serial("play() and stop() delegate to the autoplay plugin when present", async () => {
    const play = mock(() => {});
    const stop = mock(() => {});
    emblaState.plugins = { autoplay: { play, stop } };
    await mount();

    mounted.controller.play();
    mounted.controller.stop();

    expect(play).toHaveBeenCalledTimes(1);
    expect(stop).toHaveBeenCalledTimes(1);
});

test.serial("play() and stop() are no-ops when no autoplay plugin is present", async () => {
    emblaState.plugins = {};
    await mount();

    expect(() => {
        mounted.controller.play();
        mounted.controller.stop();
    }).not.toThrow();
});

// --- Plugins / extension ---

test.serial("passes an empty plugin array to Embla by default", async () => {
    await mount();

    expect(emblaState.calls[0].plugins).toEqual([]);
});

test.serial("a subclass supplies Embla plugins via emblaPlugins(), and dots use the subclass identifier", async () => {
    const fakePlugin = { name: "fake" };

    class Gallery extends CarouselController {
        emblaPlugins() {
            return [fakePlugin];
        }
    }

    const gallery = await mountController(
        "gallery",
        Gallery,
        `
        <div data-controller="gallery" data-gallery-options-value='{}'>
            <div data-carousel-viewport>
                <div data-carousel-container><div>slide</div></div>
            </div>
            <div data-gallery-target="dotList"></div>
        </div>`,
    );

    // plugins from the subclass reach Embla
    expect(emblaState.calls[0].plugins).toEqual([fakePlugin]);

    // fallback dots are wired to the subclass identifier, not a hardcoded "carousel"
    const dot = document.querySelector('[data-gallery-target="dotList"] button');
    expect(dot.getAttribute("data-action")).toBe("gallery#scrollTo");
    expect(dot.hasAttribute("data-gallery-index-param")).toBe(true);

    await gallery.cleanup();
});

// --- Axis mirroring (CSS hook) ---

test.serial("mirrors the axis option to a data attribute and defaults to x", async () => {
    await mount();
    expect(mounted.root.getAttribute("data-carousel-axis")).toBe("x");
});

test.serial("mirrors a vertical axis to the data attribute", async () => {
    await mount({ options: { axis: "y" } });
    expect(mounted.root.getAttribute("data-carousel-axis")).toBe("y");
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

// --- Progress target ---

test.serial("sets the progress target width on scroll", async () => {
    await mount();

    emblaState.progress = 0.42;
    emit("scroll");

    const bar = document.querySelector('[data-carousel-target="progress"]');
    expect(bar.style.width).toBe("42%");
});

test.serial("progress target is optional (no error when absent)", async () => {
    const m = await mountController("carousel", CarouselController, `
        <div data-controller="carousel" data-carousel-options-value='{}'>
            <div data-carousel-viewport>
                <div data-carousel-container><div>slide</div></div>
            </div>
        </div>`);

    expect(() => {
        emblaState.progress = 0.5;
        emit("scroll");
    }).not.toThrow();

    await m.cleanup();
});

// --- Counter targets ---

test.serial("sets indexLabel (1-based) and totalLabel on connect", async () => {
    emblaState.snaps = [0, 0.25, 0.5, 0.75];
    await mount();

    expect(document.querySelector('[data-carousel-target="indexLabel"]').textContent).toBe("1");
    expect(document.querySelector('[data-carousel-target="totalLabel"]').textContent).toBe("4");
});

test.serial("updates indexLabel on select", async () => {
    await mount();

    emblaState.selected = 2;
    emit("select");

    expect(document.querySelector('[data-carousel-target="indexLabel"]').textContent).toBe("3");
});

test.serial("updates totalLabel on reInit and slidesChanged", async () => {
    emblaState.snaps = [0, 0.5, 1];
    await mount();
    expect(document.querySelector('[data-carousel-target="totalLabel"]').textContent).toBe("3");

    emblaState.snaps = [0, 0.2, 0.4, 0.6, 0.8];
    emit("reInit");
    expect(document.querySelector('[data-carousel-target="totalLabel"]').textContent).toBe("5");

    emblaState.snaps = [0, 0.5];
    emit("slidesChanged");
    expect(document.querySelector('[data-carousel-target="totalLabel"]').textContent).toBe("2");
});

test.serial("counter targets are optional (no error when absent)", async () => {
    emblaState.snaps = [0, 0.5, 1];

    const m = await mountController("carousel", CarouselController, `
        <div data-controller="carousel" data-carousel-options-value='{}'>
            <div data-carousel-viewport>
                <div data-carousel-container><div>slide</div></div>
            </div>
        </div>`);

    expect(() => {
        emblaState.selected = 1;
        emit("select");
        emit("reInit");
        emit("slidesChanged");
    }).not.toThrow();

    await m.cleanup();
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
    return document.querySelector("[data-carousel-viewport]");
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
             data-action="turbo:before-cache@window->carousel#teardownForCache">
            <div data-carousel-viewport>
                <div data-carousel-container>
                    <div>slide 1</div>
                    <div>slide 2</div>
                    <div>slide 3</div>
                </div>
            </div>
            <div data-carousel-target="progress"></div>
            <span data-carousel-target="indexLabel"></span>
            <span data-carousel-target="totalLabel"></span>
            <button type="button" data-carousel-target="prevButton" data-action="carousel#prev">‹</button>
            <button type="button" data-carousel-target="nextButton" data-action="carousel#next">›</button>
            <div data-carousel-target="dotList"></div>
            <template data-carousel-target="dotTemplate">
                <button type="button" data-action="carousel#scrollTo"></button>
            </template>
        </div>`,
    );
}
