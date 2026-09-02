// @hotwire-package
import { arrow, autoUpdate, computePosition, flip, hide, offset, shift, size } from "@floating-ui/dom";

const SIDES = ["top", "right", "bottom", "left"];
const ALIGNS = ["start", "center", "end"];
const STRATEGIES = ["absolute", "fixed"];

export function createFloating(anchor, floating, options = {}) {
    const config = normalizeOptions(options);
    let cleanupAutoUpdate = null;
    let active = false;
    let lifecycle = 0;
    let latestRequest = 0;
    let readiness = null;

    async function requestUpdate(run = lifecycle) {
        const request = ++latestRequest;
        const isCurrent = () => run === lifecycle && request === latestRequest;
        let result;

        try {
            result = await computePosition(anchor, floating, {
                placement: placementValue(config.side, config.align),
                strategy: config.strategy,
                middleware: middleware(config, floating, isCurrent),
            });
        } catch (error) {
            if (!isCurrent()) return false;
            throw error;
        }

        if (!isCurrent()) return false;

        const { x, y, placement, middlewareData = {} } = result;

        const resolved = parsePlacement(placement);

        Object.assign(floating.style, {
            position: config.strategy,
            left: `${x}px`,
            top: `${y}px`,
        });

        floating.dataset.side = resolved.side;
        floating.dataset.align = resolved.align;
        const declaredDirection = floating.closest("[dir]")?.getAttribute("dir")?.toLowerCase();
        const direction = ["ltr", "rtl"].includes(declaredDirection)
            ? declaredDirection
            : floating.ownerDocument.defaultView?.getComputedStyle(floating).direction;
        floating.style.setProperty("--transform-origin", transformOrigin(resolved.side, resolved.align, direction));

        positionArrow(config.arrowElement, resolved.side, middlewareData.arrow);
        syncAnchorVisibility(floating, config.hideWhenDetached, middlewareData.hide);

        if (readiness?.run === run) readiness.resolve(true);

        return true;
    }

    function start() {
        if (active) return readiness.promise;

        active = true;
        const run = ++lifecycle;
        readiness = { run, ...deferred() };
        const starting = readiness;

        try {
            cleanupAutoUpdate = autoUpdate(anchor, floating, () => {
                void requestUpdate(run).catch((error) => {
                    failStart(run, error);
                });
            });
        } catch (error) {
            failStart(run, error);
        }

        return starting.promise;
    }

    function stop() {
        active = false;
        lifecycle++;
        latestRequest++;

        cleanupAutoUpdate?.();
        cleanupAutoUpdate = null;

        readiness?.resolve(false);
        readiness = null;
    }

    function failStart(run, error) {
        if (readiness?.run !== run || readiness.settled) return;

        const failed = readiness;
        active = false;
        lifecycle++;
        latestRequest++;
        cleanupAutoUpdate?.();
        cleanupAutoUpdate = null;
        readiness = null;
        failed.reject(error);
    }

    return {
        start,
        stop,
        update: requestUpdate,
        cleanup() {
            stop();
        },
    };
}

function middleware(config, floating, isCurrent) {
    const middleware = [
        offset({ mainAxis: config.sideOffset, crossAxis: config.alignOffset }),
    ];

    if (config.flip) middleware.push(flip());
    if (config.shift) middleware.push(shift({ padding: config.shiftPadding }));

    if (config.size) {
        middleware.push(size({
            apply({ availableWidth, availableHeight, rects }) {
                if (!isCurrent()) return;

                floating.style.setProperty("--anchor-width", `${rects.reference.width}px`);
                floating.style.setProperty("--anchor-height", `${rects.reference.height}px`);
                floating.style.setProperty("--available-width", `${availableWidth}px`);
                floating.style.setProperty("--available-height", `${availableHeight}px`);
            },
        }));
    }

    if (config.arrowElement) middleware.push(arrow({ element: config.arrowElement, padding: config.arrowPadding }));
    if (config.hideWhenDetached) middleware.push(hide());

    return middleware;
}

function deferred() {
    let rejectPromise;
    let resolvePromise;
    let settled = false;
    const promise = new Promise((resolve, reject) => {
        resolvePromise = resolve;
        rejectPromise = reject;
    });

    return {
        get settled() { return settled; },
        promise,
        resolve(value) {
            if (settled) return;
            settled = true;
            resolvePromise(value);
        },
        reject(error) {
            if (settled) return;
            settled = true;
            rejectPromise(error);
        },
    };
}

function normalizeOptions(options) {
    return {
        side: SIDES.includes(options.side) ? options.side : "bottom",
        align: ALIGNS.includes(options.align) ? options.align : "start",
        sideOffset: number(options.sideOffset, 4),
        alignOffset: number(options.alignOffset, 0),
        strategy: STRATEGIES.includes(options.strategy) ? options.strategy : "absolute",
        flip: options.flip !== false,
        shift: options.shift !== false,
        shiftPadding: number(options.shiftPadding, 8),
        size: options.size !== false,
        arrowElement: isElement(options.arrowElement) ? options.arrowElement : null,
        arrowPadding: number(options.arrowPadding, 4),
        hideWhenDetached: options.hideWhenDetached === true,
    };
}

function number(value, fallback) {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function isElement(value) {
    return value?.nodeType === 1;
}

function placementValue(side, align) {
    return align === "center" ? side : `${side}-${align}`;
}

function parsePlacement(placement) {
    const [side, align = "center"] = String(placement).split("-");

    return {
        side: SIDES.includes(side) ? side : "bottom",
        align: ALIGNS.includes(align) ? align : "center",
    };
}

function transformOrigin(side, align, direction) {
    if (side === "top") return `bottom ${inlineOrigin(align, direction)}`;
    if (side === "bottom") return `top ${inlineOrigin(align, direction)}`;
    if (side === "left") return `right ${blockOrigin(align)}`;

    return `left ${blockOrigin(align)}`;
}

function positionArrow(arrowElement, side, data = {}) {
    if (!arrowElement) return;

    const staticSide = {
        top: "bottom",
        right: "left",
        bottom: "top",
        left: "right",
    }[side];

    Object.assign(arrowElement.style, {
        left: data.x != null ? `${data.x}px` : "",
        top: data.y != null ? `${data.y}px` : "",
        right: "",
        bottom: "",
    });

    arrowElement.dataset.side = side;
    if (staticSide) arrowElement.style[staticSide] = "-5px";
}

function syncAnchorVisibility(floating, enabled, data = {}) {
    if (!enabled) return;

    floating.toggleAttribute("data-anchor-hidden", data.referenceHidden === true || data.escaped === true);
}

function inlineOrigin(align, direction) {
    if (align === "start") return direction === "rtl" ? "right" : "left";
    if (align === "end") return direction === "rtl" ? "left" : "right";

    return "center";
}

function blockOrigin(align) {
    if (align === "start") return "top";
    if (align === "end") return "bottom";

    return "center";
}
