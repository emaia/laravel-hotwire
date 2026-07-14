// @hotwire-package
import { autoUpdate, computePosition, flip, offset, shift, size } from "@floating-ui/dom";

const SIDES = ["top", "right", "bottom", "left"];
const ALIGNS = ["start", "center", "end"];
const STRATEGIES = ["absolute", "fixed"];

export function createFloating(anchor, floating, options = {}) {
    const config = normalizeOptions(options);
    let cleanupAutoUpdate = null;

    async function update() {
        const { x, y, placement } = await computePosition(anchor, floating, {
            placement: placementValue(config.side, config.align),
            strategy: config.strategy,
            middleware: middleware(config, floating),
        });

        const resolved = parsePlacement(placement);

        Object.assign(floating.style, {
            position: config.strategy,
            left: `${x}px`,
            top: `${y}px`,
        });

        floating.dataset.side = resolved.side;
        floating.dataset.align = resolved.align;
        floating.style.setProperty("--transform-origin", transformOrigin(resolved.side, resolved.align));
    }

    return {
        start() {
            if (cleanupAutoUpdate) return update();

            cleanupAutoUpdate = autoUpdate(anchor, floating, () => {
                void update();
            });

            return update();
        },
        stop() {
            cleanupAutoUpdate?.();
            cleanupAutoUpdate = null;
        },
        update,
        cleanup() {
            this.stop();
        },
    };
}

function middleware(config, floating) {
    const middleware = [
        offset({ mainAxis: config.sideOffset, crossAxis: config.alignOffset }),
    ];

    if (config.flip) middleware.push(flip());
    if (config.shift) middleware.push(shift({ padding: 8 }));

    middleware.push(size({
        apply({ availableWidth, availableHeight, rects }) {
            floating.style.setProperty("--anchor-width", `${rects.reference.width}px`);
            floating.style.setProperty("--anchor-height", `${rects.reference.height}px`);
            floating.style.setProperty("--available-width", `${availableWidth}px`);
            floating.style.setProperty("--available-height", `${availableHeight}px`);
        },
    }));

    return middleware;
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
    };
}

function number(value, fallback) {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
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

function transformOrigin(side, align) {
    if (side === "top") return `bottom ${inlineOrigin(align)}`;
    if (side === "bottom") return `top ${inlineOrigin(align)}`;
    if (side === "left") return `right ${blockOrigin(align)}`;

    return `left ${blockOrigin(align)}`;
}

function inlineOrigin(align) {
    if (align === "start") return "left";
    if (align === "end") return "right";

    return "center";
}

function blockOrigin(align) {
    if (align === "start") return "top";
    if (align === "end") return "bottom";

    return "center";
}
