import { afterEach, beforeEach, expect, mock, test } from "bun:test";
import { Window } from "happy-dom";

import { createPresence } from "../../resources/js/controllers/_presence.js";

let testWindow;

beforeEach(() => {
    testWindow = new Window({ url: "http://localhost" });
    globalThis.window = testWindow;
    globalThis.document = testWindow.document;
    globalThis.Element = testWindow.Element;
    globalThis.getComputedStyle = testWindow.getComputedStyle.bind(testWindow);
    globalThis.requestAnimationFrame = (callback) => {
        callback();
        return 1;
    };
    globalThis.cancelAnimationFrame = () => {};
});

afterEach(() => {
    testWindow.close();
});

test("sync applies stable open and closed states without motion", () => {
    const element = makeElement();
    const presence = createPresence(element);

    presence.sync(false);

    expect(presence.phase).toBe("closed");
    expect(presence.isPresent).toBe(false);
    expect(element.dataset.state).toBe("closed");
    expect(element.hidden).toBe(true);
    expect(element.hasAttribute("inert")).toBe(true);

    presence.sync(true);

    expect(presence.phase).toBe("open");
    expect(presence.isPresent).toBe(true);
    expect(element.dataset.state).toBe("open");
    expect(element.hidden).toBe(false);
    expect(element.hasAttribute("inert")).toBe(false);
});

test("open keeps a newly mounted element closed until preparation finishes", async () => {
    const prepared = deferred();
    const element = makeElement();
    const presence = createPresence(element);
    presence.sync(false);

    const opening = presence.open({ beforeEnter: () => prepared.promise });

    expect(presence.phase).toBe("opening");
    expect(element.hidden).toBe(false);
    expect(element.dataset.state).toBe("closed");
    expect(element.dataset.presence).toBe("preparing");
    expect(element.hasAttribute("inert")).toBe(true);

    prepared.resolve(true);

    expect(await opening).toBe(true);
    expect(element.dataset.state).toBe("open");
    expect(element.hasAttribute("inert")).toBe(false);
    expect(element.hasAttribute("data-presence")).toBe(false);
});

test("failed preparation restores the stable closed state before rejecting", async () => {
    const element = makeElement();
    const presence = createPresence(element);
    presence.sync(false);

    const opening = presence.open({
        beforeEnter: () => Promise.reject(new Error("positioning failed")),
    });

    await expect(opening).rejects.toThrow("positioning failed");
    expect(presence.phase).toBe("closed");
    expect(presence.isPresent).toBe(false);
    expect(element.dataset.state).toBe("closed");
    expect(element.hidden).toBe(true);
    expect(element.hasAttribute("inert")).toBe(true);
    expect(element.hasAttribute("data-presence")).toBe(false);
});

test("close keeps the element present and inert until its motion finishes", async () => {
    const motion = fakeAnimation();
    const element = makeElement();
    element.getAnimations = () => [motion.animation];
    const presence = createPresence(element);
    presence.sync(true);

    const closing = presence.close();
    let settled = false;
    void closing.then(() => { settled = true; });

    expect(presence.phase).toBe("closing");
    expect(element.dataset.state).toBe("closed");
    expect(element.dataset.presence).toBe("leaving");
    expect(element.hidden).toBe(false);
    expect(element.hasAttribute("inert")).toBe(true);

    await tick();
    expect(settled).toBe(false);

    motion.finish();

    expect(await closing).toBe(true);
    expect(presence.phase).toBe("closed");
    expect(element.hidden).toBe(true);
    expect(element.hasAttribute("data-presence")).toBe(false);
});

test("close waits for motion owned by explicit descendant elements", async () => {
    const motion = fakeAnimation();
    const element = makeElement();
    const panel = document.createElement("div");
    panel.getAnimations = () => [motion.animation];
    element.appendChild(panel);
    const presence = createPresence(element, { motionElements: [panel] });
    presence.sync(true);

    const closing = presence.close();
    await tick();

    expect(element.hidden).toBe(false);
    expect(element.hasAttribute("inert")).toBe(true);

    motion.finish();

    expect(await closing).toBe(true);
    expect(element.hidden).toBe(true);
});

test("supports a dedicated state attribute without overwriting another state", async () => {
    const element = makeElement();
    element.dataset.state = "expanded";
    element.dataset.mobileState = "closed";
    const presence = createPresence(element, { stateAttribute: "mobileState" });
    presence.sync(false);

    expect(await presence.open({ immediate: true })).toBe(true);
    expect(element.dataset.state).toBe("expanded");
    expect(element.dataset.mobileState).toBe("open");

    expect(await presence.close({ immediate: true })).toBe(true);
    expect(element.dataset.state).toBe("expanded");
    expect(element.dataset.mobileState).toBe("closed");
});

test("onEnter runs when content becomes interactive instead of after enter motion", async () => {
    const motion = fakeAnimation();
    const onEnter = mock(() => {});
    const element = makeElement();
    element.getAnimations = () => element.dataset.state === "open" ? [motion.animation] : [];
    const presence = createPresence(element);
    presence.sync(false);

    const opening = presence.open({ onEnter });
    await tick();

    expect(onEnter).toHaveBeenCalledTimes(1);
    expect(element.dataset.state).toBe("open");
    expect(element.hasAttribute("inert")).toBe(false);

    motion.finish();
    expect(await opening).toBe(true);
});

test("reopening during exit prevents the stale close from hiding the element", async () => {
    const exitMotion = fakeAnimation();
    const element = makeElement();
    element.getAnimations = () => element.dataset.state === "closed" ? [exitMotion.animation] : [];
    const presence = createPresence(element);
    presence.sync(true);

    const closing = presence.close();
    const reopening = presence.open();

    expect(await reopening).toBe(true);
    expect(await closing).toBe(false);

    exitMotion.finish();
    await tick();

    expect(presence.phase).toBe("open");
    expect(element.dataset.state).toBe("open");
    expect(element.hidden).toBe(false);
    expect(element.hasAttribute("inert")).toBe(false);
});

test("closing while preparation is pending prevents a stale enter", async () => {
    const prepared = deferred();
    const element = makeElement();
    const presence = createPresence(element);
    presence.sync(false);

    const opening = presence.open({ beforeEnter: () => prepared.promise });
    const closing = presence.close({ immediate: true });

    prepared.resolve(true);

    expect(await opening).toBe(false);
    expect(await closing).toBe(true);
    expect(element.dataset.state).toBe("closed");
    expect(element.hidden).toBe(true);
});

test("closing before preparation starts prevents beforeEnter side effects", async () => {
    const beforeEnter = mock(() => true);
    const element = makeElement();
    const presence = createPresence(element);
    presence.sync(false);

    const opening = presence.open({ beforeEnter });
    const closing = presence.close({ immediate: true });

    expect(await opening).toBe(false);
    expect(await closing).toBe(true);
    expect(beforeEnter).not.toHaveBeenCalled();
    expect(element.hidden).toBe(true);
});

test("motion none closes immediately without inspecting animations", async () => {
    const element = makeElement();
    let inspected = false;
    element.dataset.motion = "none";
    element.getAnimations = () => {
        inspected = true;
        return [];
    };
    const presence = createPresence(element);
    presence.sync(true);

    expect(await presence.close()).toBe(true);
    expect(element.hidden).toBe(true);
    expect(inspected).toBe(false);
});

test("motion none suppresses custom transitions while opening", async () => {
    const element = makeElement();
    element.dataset.motion = "none";
    element.style.transition = "opacity 10s linear";
    const presence = createPresence(element);
    presence.sync(false);
    let transitionDuringEnter = null;

    await presence.open({
        onEnter: () => {
            transitionDuringEnter = element.style.getPropertyValue("transition-property");
        },
    });

    expect(transitionDuringEnter).toBe("none");
    expect(element.style.transition).toBe("opacity 10s linear");
});

test("suppresses transitions while the first placement is prepared", async () => {
    const element = makeElement();
    element.style.transition = "translate 10s linear";
    const presence = createPresence(element);
    presence.sync(false);
    let transitionDuringPreparation = null;

    await presence.open({
        beforeEnter: () => {
            transitionDuringPreparation = element.style.getPropertyValue("transition-property");

            return true;
        },
    });

    expect(transitionDuringPreparation).toBe("none");
    expect(element.style.transition).toBe("translate 10s linear");
});

test("restores inline transition and animation longhands after suppression", async () => {
    const element = makeElement();
    element.dataset.motion = "none";
    element.style.transitionProperty = "opacity";
    element.style.transitionDuration = "2s";
    element.style.animationName = "fade-in";
    element.style.animationDuration = "3s";
    const presence = createPresence(element);
    presence.sync(false);

    await presence.open();

    expect(element.style.transitionProperty).toBe("opacity");
    expect(element.style.transitionDuration).toBe("2s");
    expect(element.style.animationName).toBe("fade-in");
    expect(element.style.animationDuration).toBe("3s");
});

test("does not cancel unrelated WAAPI animations started during preparation", async () => {
    const element = makeElement();
    element.dataset.motion = "none";
    const cancel = mock(() => {});
    const waapiAnimation = { cancel, playState: "running" };
    let animations = [];
    element.getAnimations = () => animations;
    const presence = createPresence(element);
    presence.sync(false);

    await presence.open({
        beforeEnter: () => {
            animations = [waapiAnimation];

            return true;
        },
    });

    expect(cancel).not.toHaveBeenCalled();
});

test("reduced motion closes immediately", async () => {
    const element = makeElement();
    const presence = createPresence(element, { reducedMotion: () => true });
    presence.sync(true);

    expect(await presence.close()).toBe(true);
    expect(element.hidden).toBe(true);
});

test("infinite animations do not keep the element present", async () => {
    const motion = fakeAnimation({ infinite: true });
    const element = makeElement();
    element.getAnimations = () => [motion.animation];
    const presence = createPresence(element);
    presence.sync(true);

    expect(await presence.close()).toBe(true);
    expect(element.hidden).toBe(true);
});

test("cleanup invalidates pending work and closes synchronously", async () => {
    const motion = fakeAnimation();
    const element = makeElement();
    element.getAnimations = () => [motion.animation];
    const presence = createPresence(element);
    presence.sync(true);

    const closing = presence.close();
    presence.cleanup();
    motion.finish();

    expect(await closing).toBe(false);
    expect(presence.phase).toBe("closed");
    expect(element.dataset.state).toBe("closed");
    expect(element.hidden).toBe(true);
});

function makeElement() {
    const element = document.createElement("div");
    element.dataset.motion = "default";
    document.body.appendChild(element);

    return element;
}

function fakeAnimation({ infinite = false } = {}) {
    const finished = deferred();

    return {
        animation: {
            effect: {
                getComputedTiming: () => ({ endTime: infinite ? Infinity : 100 }),
                target: null,
            },
            finished: finished.promise,
            playState: "running",
        },
        finish: () => finished.resolve(),
    };
}

function deferred() {
    let resolve;
    const promise = new Promise((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
}

function tick() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
