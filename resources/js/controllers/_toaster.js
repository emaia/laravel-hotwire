// @hotwire-package

import { createPresence } from "./_presence.js";

const TYPES = ["default", "success", "error", "warning", "info"];
const MAX_RENDER_WAIT = 2000;

let pending = [];
let active = null;
let sequence = 0;

let renderInFlight = false;
let renderGuard = null;

function endRender() {
    clearTimeout(renderGuard);
    renderGuard = null;
    renderInFlight = false;
}

if (typeof document !== "undefined") {
    document.addEventListener("turbo:before-render", () => {
        clearTimeout(renderGuard);
        renderInFlight = true;
        renderGuard = setTimeout(endRender, MAX_RENDER_WAIT);
    });
    document.addEventListener("turbo:load", endRender);
    document.addEventListener("turbo:load", flushPending);
}

export function isDetached(instance) {
    return instance?.element?.isConnected === false;
}

export function flushPending() {
    if (!active || isDetached(active) || pending.length === 0) return;

    const buffered = pending;
    pending = [];
    buffered.forEach((toast) => active.show(toast));
}

export function emitToast(payload) {
    const toast = { ...payload, id: payload.id ?? nextId() };

    if (active && !isDetached(active)) {
        active.show(toast);
    } else {
        pending.push(toast);
    }

    return toast.id;
}

export function resetToaster() {
    pending = [];
    active = null;
    sequence = 0;
    renderInFlight = false;
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
        element.insertBefore(entry.element, element.firstChild);
        measure(entry);
        observe(entry);
        entry.presence.sync(false);
        enter(entry);

        return id;
    }

    function enter(entry) {
        const settled = whenPageVisible();

        if (settled === null) {
            release(entry);

            return;
        }

        settled.then(() => {
            if (destroyed || !entry.element.isConnected) return;

            requestAnimationFrame(() => release(entry));
        });
    }

    function release(entry) {
        if (destroyed || !entry.element.isConnected) return;

        reflow();
        entry.presence.open().then(() => flushPendingMeasure(entry));
        startTimer(entry);
    }

    function flushPendingMeasure(entry) {
        if (!entry.pendingMeasure || destroyed || !entry.element.isConnected) return;

        entry.pendingMeasure = false;
        if (measure(entry)) reflow();
    }

    function build(id, payload) {
        const type = TYPES.includes(payload.type) ? payload.type : "default";
        const node = document.createElement("div");
        node.dataset.slot = "toast";
        node.dataset.toastId = id;
        node.dataset.type = type;
        node.dataset.position = payload.position || config.position;
        node.setAttribute("role", type === "error" ? "alert" : "status");
        node.setAttribute("aria-live", type === "error" ? "assertive" : "polite");
        node.setAttribute("aria-atomic", "true");
        node.dataset.expanded = element.dataset.expanded ?? "false";
        node.dataset.state = "closed";
        if (config.className) node.classList.add(...config.className.split(/\s+/).filter(Boolean));
        if (payload.className) node.classList.add(...String(payload.className).split(/\s+/).filter(Boolean));

        const content = slot("div", "toast-content");
        content.appendChild(slot("span", "toast-icon", { "aria-hidden": "true" }));

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
            pendingMeasure: false,
            observer: null,
            remaining: null,
            startedAt: null,
            timer: null,
            id,
        };
    }

    function measure(entry) {
        entry.element.setAttribute("data-measuring", "");
        const height = entry.element.offsetHeight ?? 0;
        entry.element.removeAttribute("data-measuring");

        if (height <= 0 || height === entry.height) return false;

        entry.height = height;
        entry.element.style.setProperty("--toast-height", `${height}px`);

        return true;
    }

    function observe(entry) {
        if (typeof ResizeObserver !== "function") return;

        const body = entry.element.querySelector('[data-slot="toast-body"]');
        entry.bodyHeight = body.offsetHeight ?? 0;
        entry.observer = new ResizeObserver(([record]) => {
            const height = record?.contentRect?.height ?? body.offsetHeight ?? 0;
            if (height === entry.bodyHeight) return;

            entry.bodyHeight = height;

            if (entry.element.dataset.presence) {
                entry.pendingMeasure = true;

                return;
            }

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

    function handleFocusKey(event) {
        if (event.key !== "F6" || event.defaultPrevented || entries.size === 0) return;

        event.preventDefault();
        element.focus();
    }

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

    function drop(entry) {
        stopTimer(entry);
        entry.observer?.disconnect();
        entry.presence.cleanup();
        entry.element.remove();
    }

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
            const limited = index >= config.visibleToasts;
            entry.element.toggleAttribute("data-limited", limited);
            entry.element.toggleAttribute("data-behind", index > 0);
            entry.element.querySelectorAll("button").forEach((button) => {
                button.tabIndex = limited ? -1 : 0;
            });

            stacks.set(position, { index: index + 1, offset: offset + entry.height });
        });

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
        get destroyed() {
            return destroyed;
        },
        get element() {
            return element;
        },
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
    flushPending();

    return instance;
}

function nextId() {
    sequence += 1;

    return `toast-${sequence}`;
}

function whenPageVisible() {
    if (renderInFlight) {
        return new Promise((resolve) => {
            const settle = () => {
                clearTimeout(guard);
                document.removeEventListener("turbo:load", settle);
                resolve();
            };
            const guard = setTimeout(settle, MAX_RENDER_WAIT);

            document.addEventListener("turbo:load", settle);
        });
    }

    const animations = viewTransitionAnimations();

    return animations.length === 0 ? null : Promise.allSettled(animations.map((animation) => animation.finished));
}

function viewTransitionAnimations() {
    if (typeof document.getAnimations !== "function") return [];

    return document.getAnimations().filter((animation) => {
        const pseudo = animation.effect?.pseudoElement;

        return typeof pseudo === "string" && pseudo.startsWith("::view-transition");
    });
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
