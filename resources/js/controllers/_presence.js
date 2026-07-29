// @hotwire-package

export function createPresence(element, options = {}) {
    const reducedMotion = options.reducedMotion ?? prefersReducedMotion;
    const maxWait = number(options.maxWait, 30_000);
    const stateAttribute = options.stateAttribute ?? "state";
    const motionElements = uniqueElements(element, options.motionElements);
    let phase = element.hidden || state(element, stateAttribute) !== "open" ? "closed" : "open";
    let generation = 0;
    let operation = null;
    let motionSuppression = null;
    let destroyed = false;

    function sync(open) {
        if (destroyed) return false;

        invalidate();
        restoreMotion();
        applyStableState(element, open, stateAttribute);
        phase = open ? "open" : "closed";

        return true;
    }

    async function open({ beforeEnter, onEnter, immediate = false } = {}) {
        if (destroyed) return false;
        if (phase === "open" && !element.hidden) return true;

        const current = begin();
        const wasPresent = !element.hidden;
        phase = "opening";

        if (!wasPresent) {
            suppressMotion(current);
            element.dataset.presence = "preparing";
            setState(element, stateAttribute, "closed");
            element.hidden = false;
            setInert(element, true);
        }

        try {
            const prepared = await prepare(beforeEnter, current.signal);
            if (!isCurrent(current) || prepared === aborted) return false;

            if (prepared === false) {
                failOpen(current);

                return false;
            }

            const animate = !immediate && motionEnabled(element, reducedMotion);

            if (!wasPresent && animate) {
                forceStyles(motionElements);
                restoreMotion(current);
                element.dataset.presence = "entering";
                forceStyles(motionElements);
                if (!await nextFrame(current.signal) || !isCurrent(current)) return false;
            } else if (!animate && !isMotionSuppressed(current)) {
                suppressMotion(current);
            }

            element.dataset.presence = animate ? "entering" : "instant";
            setInert(element, false);
            setState(element, stateAttribute, "open");
            onEnter?.({ signal: current.signal });
            if (!isCurrent(current)) return false;

            if (!animate) {
                forceStyles(motionElements);
                restoreMotion(current);
            }

            const enterMotion = animate ? waitForMotion(motionElements, current.signal, maxWait) : null;
            if (enterMotion && !await enterMotion) return false;
            if (!isCurrent(current)) return false;

            forceStyles(motionElements);
            delete element.dataset.presence;
            phase = "open";
            finish(current);

            return true;
        } catch (error) {
            if (isCurrent(current)) failOpen(current);

            throw error;
        } finally {
            restoreMotion(current);
        }
    }

    async function close({ immediate = false } = {}) {
        if (destroyed) return false;
        if (phase === "closed" && element.hidden) return true;

        const current = begin();
        phase = "closing";
        setInert(element, true);
        element.dataset.presence = immediate ? "instant" : "leaving";
        setState(element, stateAttribute, "closed");

        if (!immediate && motionEnabled(element, reducedMotion)) {
            const exitMotion = waitForMotion(motionElements, current.signal, maxWait);
            if (exitMotion && !await exitMotion) return false;
        }

        if (!isCurrent(current)) return false;

        applyStableState(element, false, stateAttribute);
        phase = "closed";
        finish(current);

        return true;
    }

    function cleanup() {
        if (destroyed) return;

        invalidate();
        restoreMotion();
        destroyed = true;
        applyStableState(element, false, stateAttribute);
        phase = "closed";
    }

    function begin() {
        invalidate();
        restoreMotion();

        const current = {
            controller: new AbortController(),
            generation,
        };
        operation = current;

        return {
            ...current,
            signal: current.controller.signal,
        };
    }

    function invalidate() {
        generation++;
        operation?.controller.abort();
        operation = null;
    }

    function isCurrent(current) {
        return !destroyed && current.generation === generation && !current.signal.aborted;
    }

    function finish(current) {
        if (operation?.generation === current.generation) operation = null;
    }

    function failOpen(current) {
        applyStableState(element, false, stateAttribute);
        phase = "closed";
        finish(current);
    }

    function suppressMotion(current) {
        restoreMotion();
        const properties = ["transition-property", "animation-name"];
        const entries = motionElements.map((motionElement) => ({
            animations: new Set(ownAnimations(motionElement)),
            element: motionElement,
            previous: properties.map((property) => ({
                property,
                value: motionElement.style.getPropertyValue(property),
                priority: motionElement.style.getPropertyPriority(property),
            })),
        }));

        entries.forEach(({ element: motionElement }) => {
            properties.forEach((property) => motionElement.style.setProperty(property, "none", "important"));
        });
        motionSuppression = { entries, generation: current.generation };
    }

    function restoreMotion(current = null) {
        if (!motionSuppression) return;
        if (current && motionSuppression.generation !== current.generation) return;

        const { entries } = motionSuppression;
        motionSuppression = null;
        entries.forEach(({ animations, element: motionElement, previous }) => {
            previous.forEach(({ property, value, priority }) => {
                if (value) {
                    motionElement.style.setProperty(property, value, priority);
                } else {
                    motionElement.style.removeProperty(property);
                }
            });
            ownAnimations(motionElement).forEach((animation) => {
                if (!animations.has(animation) && isCssMotion(animation, motionElement)) animation.cancel?.();
            });
        });
    }

    function isMotionSuppressed(current) {
        return motionSuppression?.generation === current.generation;
    }

    return {
        get isPresent() { return !element.hidden; },
        get phase() { return phase; },
        sync,
        open,
        close,
        cleanup,
    };
}

const aborted = Symbol("aborted");

async function prepare(beforeEnter, signal) {
    if (!beforeEnter) return true;

    return abortable(Promise.resolve().then(() => signal.aborted ? aborted : beforeEnter({ signal })), signal);
}

function applyStableState(element, open, stateAttribute) {
    delete element.dataset.presence;
    setState(element, stateAttribute, open ? "open" : "closed");
    setInert(element, !open);
    element.hidden = !open;
}

function state(element, attribute) {
    return element.dataset[attribute];
}

function setState(element, attribute, value) {
    element.dataset[attribute] = value;
}

function setInert(element, inert) {
    element.toggleAttribute("inert", inert);
}

function motionEnabled(element, reducedMotion) {
    return element.dataset.motion !== "none" && !reducedMotion();
}

function prefersReducedMotion() {
    return window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false;
}

function waitForMotion(elements, signal, maxWait) {
    forceStyles(elements);

    const animations = ownFiniteAnimations(elements);
    const durationFromStyles = elements.reduce((longest, element) => Math.max(longest, motionDuration(element)), 0);
    if (animations.length > 0) {
        const duration = Math.min(maxWait, Math.max(animationEndTime(animations), durationFromStyles, 0) + 50);
        const finished = Promise.allSettled(animations.map(({ animation }) => animation.finished));

        return waitForSettlement(finished, duration, signal);
    }

    const duration = Math.min(maxWait, durationFromStyles);
    if (duration <= 0) return null;

    return waitForSettlement(null, duration, signal);
}

function ownFiniteAnimations(elements) {
    return elements.flatMap((element) => ownAnimations(element)
        .filter((animation) => {
            if (!animation?.finished || animation.playState === "finished") return false;

            const endTime = animation.effect?.getComputedTiming?.().endTime;

            return endTime !== Infinity;
        })
        .map((animation) => ({ animation, element })));
}

function ownAnimations(element) {
    if (typeof element.getAnimations !== "function") return [];

    return element.getAnimations({ subtree: false });
}

function isCssMotion(animation, element) {
    const view = element.ownerDocument?.defaultView;

    return Boolean(
        (view?.CSSAnimation && animation instanceof view.CSSAnimation) ||
        (view?.CSSTransition && animation instanceof view.CSSTransition) ||
        typeof animation.animationName === "string" ||
        typeof animation.transitionProperty === "string",
    );
}

function animationEndTime(animations) {
    return animations.reduce((longest, { animation }) => {
        const endTime = animation.effect?.getComputedTiming?.().endTime;

        return Number.isFinite(endTime) ? Math.max(longest, endTime) : longest;
    }, 0);
}

function motionDuration(element) {
    const style = getComputedStyle(element);
    const transition = longestTimeline(style.transitionDuration, style.transitionDelay);
    const animation = longestTimeline(
        style.animationDuration,
        style.animationDelay,
        style.animationIterationCount,
        style.animationName,
    );

    return Math.max(transition, animation);
}

function longestTimeline(durations, delays, iterations = "1", names = "") {
    const durationList = list(durations).map(time);
    const delayList = list(delays).map(time);
    const iterationList = list(iterations).map((value) => value === "infinite" ? Infinity : number(value, 1));
    const nameList = list(names);
    const count = Math.max(durationList.length, delayList.length, iterationList.length, nameList.length, 1);
    let longest = 0;

    for (let index = 0; index < count; index++) {
        if (nameList.length > 0 && nameList[index % nameList.length] === "none") continue;

        const iteration = iterationList[index % iterationList.length] ?? 1;
        if (!Number.isFinite(iteration)) continue;

        const duration = durationList[index % durationList.length] ?? 0;
        const delay = delayList[index % delayList.length] ?? 0;
        longest = Math.max(longest, Math.max(0, duration * iteration + delay));
    }

    return longest;
}

function list(value) {
    return String(value ?? "").split(",").map((part) => part.trim()).filter(Boolean);
}

function time(value) {
    if (!value) return 0;

    const parsed = parseFloat(value);
    if (!Number.isFinite(parsed)) return 0;

    return value.endsWith("ms") ? parsed : parsed * 1000;
}

function number(value, fallback) {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function forceStyles(elements) {
    elements.forEach(forceStyle);
}

function forceStyle(element) {
    void getComputedStyle(element).opacity;
}

function uniqueElements(element, additional) {
    const elements = Array.isArray(additional) ? additional : [];

    return [...new Set([element, ...elements].filter(Boolean))];
}

function nextFrame(signal) {
    if (signal.aborted) return Promise.resolve(false);

    return new Promise((resolve) => {
        let frame = null;
        const abort = () => {
            if (frame !== null) cancelAnimationFrame(frame);
            resolve(false);
        };

        signal.addEventListener("abort", abort, { once: true });
        frame = requestAnimationFrame(() => {
            signal.removeEventListener("abort", abort);
            resolve(!signal.aborted);
        });
    });
}

function abortable(promise, signal) {
    if (signal.aborted) return Promise.resolve(aborted);

    return new Promise((resolve, reject) => {
        const abort = () => resolve(aborted);
        signal.addEventListener("abort", abort, { once: true });

        promise.then(
            (value) => {
                signal.removeEventListener("abort", abort);
                resolve(value);
            },
            (error) => {
                signal.removeEventListener("abort", abort);
                reject(error);
            },
        );
    });
}

function waitForSettlement(promise, duration, signal) {
    if (signal.aborted) return Promise.resolve(false);

    return new Promise((resolve) => {
        let settled = false;
        const finish = (completed) => {
            if (settled) return;

            settled = true;
            clearTimeout(timer);
            signal.removeEventListener("abort", abort);
            resolve(completed);
        };
        const abort = () => finish(false);
        const timer = setTimeout(() => finish(true), duration);

        signal.addEventListener("abort", abort, { once: true });
        promise?.then(() => finish(true), () => finish(true));
    });
}
