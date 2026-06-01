// Dependency-free enter/leave transitions, Vue/stimulus-use style. Reads the
// classes to apply from the element's own data attributes:
//
//   data-transition-enter (timing/easing classes applied throughout)
//   data-transition-enter-from (initial state)
//   data-transition-enter-to (final state)
//   data-transition-leave / from / to
//
// With no attributes, it just toggles the hidden class (instant). Interrupting a
// running transition (rapid toggling) is safe: stale classes are stripped first.

const running = new WeakMap();

export function enter(el, options = {}) {
    transition(el, "enter", hiddenClasses(options.hidden));
}

export function leave(el, options = {}) {
    transition(el, "leave", hiddenClasses(options.hidden));
}

function transition(el, direction, hidden) {
    cancel(el);

    const isEnter = direction === "enter";
    const active = read(el, direction);
    const from = read(el, `${direction}-from`);
    const to = read(el, `${direction}-to`);

    if (isEnter) el.classList.remove(...hidden);

    // Nothing to animate — toggle instantly.
    if (!active.length && !from.length && !to.length) {
        if (!isEnter) el.classList.add(...hidden);
        return;
    }

    el.classList.add(...active, ...from);

    const state = { tracked: [...active, ...from] };
    running.set(el, state);

    state.raf1 = requestAnimationFrame(() => {
        state.raf2 = requestAnimationFrame(() => {
            el.classList.remove(...from);
            el.classList.add(...to);
            state.tracked = [...active, ...to];

            state.timer = setTimeout(() => {
                el.classList.remove(...active, ...to);
                if (!isEnter) el.classList.add(...hidden);
                running.delete(el);
            }, durationOf(el));
        });
    });
}

function cancel(el) {
    const state = running.get(el);
    if (!state) return;

    cancelAnimationFrame(state.raf1);
    cancelAnimationFrame(state.raf2);
    clearTimeout(state.timer);
    el.classList.remove(...state.tracked);
    running.delete(el);
}

function read(el, name) {
    const value = el.getAttribute(`data-transition-${name}`);
    return value ? value.split(/\s+/).filter(Boolean) : [];
}

function hiddenClasses(hidden) {
    if (hidden == null) return ["hidden"];
    return Array.isArray(hidden) ? hidden : String(hidden).split(/\s+/).filter(Boolean);
}

function durationOf(el) {
    const style = getComputedStyle(el);
    return longest(style.transitionDuration) + longest(style.transitionDelay);
}

function longest(value) {
    if (!value) return 0;

    return value.split(",").reduce((max, part) => {
        const trimmed = part.trim();
        const ms = trimmed.endsWith("ms") ? parseFloat(trimmed) : parseFloat(trimmed) * 1000;
        return Number.isFinite(ms) ? Math.max(max, ms) : max;
    }, 0);
}
