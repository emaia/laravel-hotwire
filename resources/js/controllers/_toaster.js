// @hotwire-package

import { createPresence } from "./_presence.js";

const TYPES = ["default", "success", "error", "warning", "info"];

// Emissions routinely arrive before a viewport exists: the trigger sits earlier in the document
// than the layout's, and lazy-loaded controllers connect in whatever order their chunks land.
let pending = [];
let active = null;
let sequence = 0;

/** Show a toast, buffering it until a viewport exists. Returns its id. */
export function emitToast(payload) {
    const toast = { ...payload, id: payload.id ?? nextId() };

    if (active) {
        active.show(toast);
    } else {
        pending.push(toast);
    }

    return toast.id;
}

/** Test seam; never called at runtime. */
export function resetToaster() {
    pending = [];
    active = null;
    sequence = 0;
}

export function createToaster(element, options = {}) {
    const config = {
        closeButton: options.closeButton ?? true,
        duration: options.duration ?? 4000,
        expand: options.expand ?? false,
        position: options.position ?? "bottom-center",
        visibleToasts: options.visibleToasts ?? 3,
        className: options.className ?? "",
        ariaLabel: options.containerAriaLabel || "Notifications",
    };
    const entries = new Map();
    let destroyed = false;
    const pauseCauses = new Set();
    let reflowHandle = null;

    element.setAttribute("role", "region");
    element.setAttribute("aria-label", config.ariaLabel);
    element.setAttribute("tabindex", "-1");
    element.dataset.expanded = String(config.expand);
    element.addEventListener("pointerenter", pausePointer);
    element.addEventListener("pointerleave", resumePointer);
    element.addEventListener("focusin", pauseFocus);
    element.addEventListener("focusout", resumeFocus);
    document.addEventListener("keydown", handleFocusKey);
    document.addEventListener("visibilitychange", handleVisibility);

    function show(payload) {
        if (destroyed) return null;

        const id = payload.id ?? nextId();
        const entry = build(id, payload);
        entries.set(id, entry);
        element.appendChild(entry.element);
        // Anything that forces layout has to run before presence stages the closed frame: a
        // suppressed read afterwards becomes the transition's base style and the toast snaps in.
        measure(entry);
        observe(entry);
        reflow();
        entry.presence.sync(false);
        entry.presence.open();
        startTimer(entry);

        return id;
    }

    function build(id, payload) {
        const type = TYPES.includes(payload.type) ? payload.type : "default";
        const node = document.createElement("div");
        node.dataset.slot = "toast";
        node.dataset.type = type;
        node.dataset.position = payload.position || config.position;
        node.setAttribute("role", type === "error" ? "alert" : "status");
        node.setAttribute("aria-live", type === "error" ? "assertive" : "polite");
        node.setAttribute("aria-atomic", "true");
        node.dataset.expanded = element.dataset.expanded ?? "false";
        // Born closed: attached without a state, the card paints at rest first and the entry
        // transition runs backwards from there, leaving a bare fade.
        node.dataset.state = "closed";
        if (config.className) node.classList.add(...config.className.split(/\s+/).filter(Boolean));
        if (payload.className) node.classList.add(...String(payload.className).split(/\s+/).filter(Boolean));

        const content = slot("div", "toast-content");
        content.appendChild(slot("span", "toast-icon", { "aria-hidden": "true" }));

        // Empty strings arrive from request input forwarded through a stream macro; an empty node
        // still takes a row and throws the text out of line with the icon.
        const body = slot("div", "toast-body");
        if (payload.message) body.appendChild(text("div", "toast-title", payload.message));
        if (payload.description) body.appendChild(text("div", "toast-description", payload.description));
        content.appendChild(body);

        if (config.closeButton) {
            const close = slot("button", "toast-close", { type: "button", "aria-label": "Close toast" });
            close.addEventListener("click", () => dismiss(id));
            content.appendChild(close);
        }

        node.appendChild(content);

        return {
            element: node,
            presence: createPresence(node),
            duration: Number.isFinite(payload.duration) ? payload.duration : config.duration,
            height: 0,
            bodyHeight: 0,
            observer: null,
            remaining: null,
            startedAt: null,
            timer: null,
            id,
        };
    }

    /**
     * Read the natural height with the clamp lifted, believing only a positive answer. Zero is
     * routine — a Turbo visit connects the trigger before the permanent viewport reaches the new
     * document — and storing it would clip the card for good.
     */
    function measure(entry) {
        entry.element.setAttribute("data-measuring", "");
        const height = entry.element.offsetHeight ?? 0;
        entry.element.removeAttribute("data-measuring");

        if (height <= 0 || height === entry.height) return false;

        entry.height = height;
        entry.element.style.setProperty("--toast-height", `${height}px`);

        return true;
    }

    /**
     * Re-measure when the content resizes: a webfont, a late stylesheet in dev, a zoom change.
     * Gated on the body really changing, because a ResizeObserver fires once on observe and
     * measuring suppresses transitions for a frame, cancelling the entry animation.
     */
    function observe(entry) {
        if (typeof ResizeObserver !== "function") return;

        const body = entry.element.querySelector('[data-slot="toast-body"]');
        entry.bodyHeight = body.offsetHeight ?? 0;
        entry.observer = new ResizeObserver(([record]) => {
            const height = record?.contentRect?.height ?? body.offsetHeight ?? 0;
            if (height === entry.bodyHeight) return;

            entry.bodyHeight = height;
            if (measure(entry)) reflow();
        });
        entry.observer.observe(body);
    }

    function remeasure() {
        let changed = false;
        entries.forEach((entry) => {
            if (measure(entry)) changed = true;
        });
        if (changed) reflow();

        return changed;
    }

    function startTimer(entry) {
        if (entry.duration <= 0) return;

        entry.remaining ??= entry.duration;
        if (isPaused()) return;

        entry.startedAt = Date.now();
        entry.timer = setTimeout(() => dismiss(entry.id), entry.remaining);
    }

    function stopTimer(entry) {
        if (entry.timer === null) return;

        clearTimeout(entry.timer);
        entry.timer = null;
        if (entry.startedAt !== null) {
            entry.remaining = Math.max(0, entry.remaining - (Date.now() - entry.startedAt));
            entry.startedAt = null;
        }
    }

    function pause(cause) {
        setExpanded(true);
        const wasPaused = isPaused();

        pauseCauses.add(cause);
        if (wasPaused) return;

        entries.forEach(stopTimer);
    }

    function resume(cause) {
        pauseCauses.delete(cause);
        setExpanded(isPaused());
        if (isPaused()) return;

        entries.forEach(startTimer);
    }

    function isPaused() {
        return pauseCauses.size > 0;
    }

    function pausePointer() {
        pause("pointer");
    }

    function resumePointer() {
        resume("pointer");
    }

    function pauseFocus() {
        pause("focus");
    }

    function resumeFocus() {
        resume("focus");
    }

    function handleVisibility() {
        if (document.hidden) pause("visibility");
        else resume("visibility");
    }

    /** F6 jumps to the notification region, as in Radix and Base UI. The landmark alone does not
     *  deliver it: Chrome spends F6 on its own panes and screen readers use their own commands. */
    function handleFocusKey(event) {
        if (event.key !== "F6" || event.defaultPrevented || entries.size === 0) return;

        event.preventDefault();
        element.focus();
    }

    /** Play the exit, then drop the node; the stack closes the gap while it animates out. */
    function dismiss(id) {
        const entry = entries.get(id);
        if (!entry || entry.leaving) return;

        entry.leaving = true;
        stopTimer(entry);
        entry.observer?.disconnect();
        entries.delete(id);
        reflow();

        entry.presence.close().finally(() => {
            entry.presence.cleanup();
            entry.element.remove();
        });
    }

    /** Remove the node now, motion or not. */
    function drop(entry) {
        stopTimer(entry);
        entry.observer?.disconnect();
        entry.presence.cleanup();
        entry.element.remove();
    }

    /**
     * Restack next frame, coalescing bursts. Cards must be painted where they are before being
     * told to move, or they jump — a Turbo visit re-inserts every toast in the permanent viewport
     * and the arriving one would reindex them in the same tick. The new toast loses nothing:
     * --toast-index falls back to 0.
     */
    function reflow() {
        if (reflowHandle !== null || typeof requestAnimationFrame !== "function") {
            if (typeof requestAnimationFrame !== "function") restack();

            return;
        }

        reflowHandle = requestAnimationFrame(() => {
            reflowHandle = null;
            if (!destroyed) restack();
        });
    }

    function restack() {
        const stacks = new Map();

        [...entries.values()].reverse().forEach((entry) => {
            const position = entry.element.dataset.position;
            const { index, offset } = stacks.get(position) ?? { index: 0, offset: 0 };

            entry.element.style.setProperty("--toast-index", String(index));
            entry.element.style.setProperty("--toast-offset-y", `${offset}px`);
            entry.element.toggleAttribute("data-limited", index >= config.visibleToasts);
            entry.element.toggleAttribute("data-behind", index > 0);

            stacks.set(position, { index: index + 1, offset: offset + entry.height });
        });

        // Per position, so two stacks on screen clamp to their own frontmost card.
        stacks.forEach((_stack, position) => {
            const frontmost = [...entries.values()]
                .reverse()
                .find((entry) => entry.element.dataset.position === position);

            entries.forEach((entry) => {
                if (entry.element.dataset.position !== position) return;

                if (frontmost?.height > 0) {
                    entry.element.style.setProperty("--toast-frontmost-height", `${frontmost.height}px`);
                } else {
                    entry.element.style.removeProperty("--toast-frontmost-height");
                }
            });
        });
    }

    /** Mirrored onto every toast so the stylesheet places a card from its own attributes. */
    function setExpanded(expanded) {
        const value = String(config.expand || expanded);
        element.dataset.expanded = value;
        entries.forEach((entry) => {
            entry.element.dataset.expanded = value;
        });
    }

    function destroy() {
        if (destroyed) return;

        destroyed = true;
        if (reflowHandle !== null) cancelAnimationFrame(reflowHandle);
        reflowHandle = null;
        pauseCauses.clear();
        entries.forEach(drop);
        entries.clear();
        element.removeEventListener("pointerenter", pausePointer);
        element.removeEventListener("pointerleave", resumePointer);
        element.removeEventListener("focusin", pauseFocus);
        element.removeEventListener("focusout", resumeFocus);
        document.removeEventListener("keydown", handleFocusKey);
        document.removeEventListener("visibilitychange", handleVisibility);
        element.removeAttribute("role");
        element.removeAttribute("aria-label");
        element.removeAttribute("tabindex");
        if (active === instance) active = null;
        if (window.toaster === instance) window.toaster = null;
    }

    const instance = {
        get destroyed() { return destroyed; },
        show,
        dismiss,
        destroy,
        remeasure,
        toast: (message, options = {}) => emitToast({ ...options, message, type: options.type ?? "default" }),
        success: (message, options = {}) => emitToast({ ...options, message, type: "success" }),
        error: (message, options = {}) => emitToast({ ...options, message, type: "error" }),
        warning: (message, options = {}) => emitToast({ ...options, message, type: "warning" }),
        info: (message, options = {}) => emitToast({ ...options, message, type: "info" }),
    };

    active = instance;
    const buffered = pending;
    pending = [];
    buffered.forEach(show);

    return instance;
}

function nextId() {
    sequence += 1;

    return `toast-${sequence}`;
}

function slot(tag, name, attributes = {}) {
    const node = document.createElement(tag);
    node.dataset.slot = name;
    Object.entries(attributes).forEach(([key, value]) => node.setAttribute(key, value));

    return node;
}

function text(tag, name, value) {
    const node = slot(tag, name);
    node.textContent = String(value ?? "");

    return node;
}
