// @hotwire-package
import { imageBounds } from "./_image_viewport.js";

export function createImageMotion(image, overlay) {
    const view = image.ownerDocument.defaultView;
    const reduced = view.matchMedia("(prefers-reduced-motion: reduce)");
    let animation = null;
    let pose = null;
    let from = null;
    let to = null;
    let destroyed = false;
    const disabled = () => reduced.matches || overlay.dataset.motion === "none";
    const onPreference = () => {
        if (disabled()) finish();
    };
    reduced.addEventListener("change", onPreference);
    const observer = new view.MutationObserver(onPreference);
    observer.observe(overlay, { attributes: true, attributeFilter: ["data-motion"] });

    function finish() {
        animation?.cancel();
        animation = null;
        from = to = null;
    }

    function sync(state, { animate = false, rotation = 0, fade = false } = {}) {
        if (destroyed) return;
        const start = sample();
        // Retain composited alpha before retiring a fade or its continuation.
        const opacity = animation ? view.getComputedStyle(image).opacity : null;
        const bounds = imageBounds(state);
        const angle =
            rotation && pose
                ? pose.angle + rotation
                : state.rotation + Math.round(((pose?.angle ?? state.rotation) - state.rotation) / 360) * 360;
        const sideways = state.rotation % 180 !== 0;
        const next = {
            x: state.x + bounds.width / 2,
            y: state.y + bounds.height / 2,
            angle,
            sx: (sideways ? state.flipY : state.flipX) ? -state.scale : state.scale,
            sy: (sideways ? state.flipX : state.flipY) ? -state.scale : state.scale,
        };
        finish();
        pose = next;
        if ((!animate && !fade) || disabled() || typeof image.animate !== "function") return;
        const style = view.getComputedStyle(image);
        const duration = milliseconds(
            style.getPropertyValue(fade ? "--image-viewer-fade-duration" : "--image-viewer-transform-duration"),
        );
        const continueOpacity = opacity !== null && opacity !== style.opacity;
        if (
            !duration ||
            (!fade && (!start || (!continueOpacity && Object.keys(next).every((key) => next[key] === start[key]))))
        )
            return;
        from = fade ? next : start;
        to = next;
        const frame = (point) => ({
            width: `${state.naturalWidth}px`,
            height: `${state.naturalHeight}px`,
            // Keep a constant bitmap size while animating its complete transform, including zoom.
            transform: `translate(${point.x - state.naturalWidth / 2}px, ${point.y - state.naturalHeight / 2}px) rotate(${point.angle}deg) scale(${point.sx}, ${point.sy})`,
        });
        const keyframes = fade ? [{ opacity: 0 }, { opacity: style.opacity }] : [frame(from), frame(to)];
        if (!fade && continueOpacity) {
            keyframes[0].opacity = opacity;
            keyframes[1].opacity = style.opacity;
        }
        const current = image.animate(keyframes, { duration, easing: "ease-out", fill: "both" });
        animation = current;
        current.finished.then(
            () => {
                if (animation === current) finish();
            },
            () => {},
        );
    }

    function sample() {
        if (!animation || !from || !to) return pose;
        const progress = animation.effect.getComputedTiming().progress ?? 0;
        return Object.fromEntries(Object.keys(to).map((key) => [key, from[key] + (to[key] - from[key]) * progress]));
    }

    return {
        sync,
        finish,
        reset: () => {
            finish();
            pose = null;
        },
        destroy: () => {
            destroyed = true;
            finish();
            reduced.removeEventListener("change", onPreference);
            observer.disconnect();
        },
    };
}

function milliseconds(value) {
    const match = value.trim().match(/^(\d+(?:\.\d+)?|\.\d+)(ms|s)$/);
    return match ? Number(match[1]) * (match[2] === "s" ? 1000 : 1) : 0;
}
