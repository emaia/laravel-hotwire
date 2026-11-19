// @hotwire-package
export function fitImage(viewportWidth, viewportHeight, naturalWidth, naturalHeight) {
    if (
        ![viewportWidth, viewportHeight, naturalWidth, naturalHeight].every(
            (value) => Number.isFinite(value) && value > 0,
        )
    ) {
        return null;
    }
    const scale = Math.min(1, viewportWidth / naturalWidth, viewportHeight / naturalHeight);
    return constrain({
        viewportWidth,
        viewportHeight,
        naturalWidth,
        naturalHeight,
        scale,
        minScale: scale,
        maxScale: 4,
        x: 0,
        y: 0,
    });
}

export function zoomImage(state, scale, pivotX = state.viewportWidth / 2, pivotY = state.viewportHeight / 2) {
    if (![scale, pivotX, pivotY].every(Number.isFinite)) return state;
    scale = Math.max(state.minScale, Math.min(state.maxScale, scale));
    const ratio = scale / state.scale;
    return constrain({
        ...state,
        scale,
        x: pivotX - (pivotX - state.x) * ratio,
        y: pivotY - (pivotY - state.y) * ratio,
    });
}

export function panImage(state, deltaX, deltaY) {
    if (![deltaX, deltaY].every(Number.isFinite)) return state;
    return constrain({ ...state, x: state.x + deltaX, y: state.y + deltaY });
}

export function resizeImage(state, width, height) {
    const fitted = fitImage(width, height, state.naturalWidth, state.naturalHeight);
    if (!fitted || state.scale <= state.minScale + 0.0001) return fitted;
    const centerX = (state.viewportWidth / 2 - state.x) / state.scale;
    const centerY = (state.viewportHeight / 2 - state.y) / state.scale;
    const scale = Math.max(fitted.minScale, state.scale);
    return constrain({ ...fitted, scale, x: width / 2 - centerX * scale, y: height / 2 - centerY * scale });
}

function constrain(state) {
    const width = state.naturalWidth * state.scale;
    const height = state.naturalHeight * state.scale;
    return {
        ...state,
        x:
            width <= state.viewportWidth
                ? (state.viewportWidth - width) / 2
                : Math.max(state.viewportWidth - width, Math.min(0, state.x)),
        y:
            height <= state.viewportHeight
                ? (state.viewportHeight - height) / 2
                : Math.max(state.viewportHeight - height, Math.min(0, state.y)),
    };
}
