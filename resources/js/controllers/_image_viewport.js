// @hotwire-package
export function fitImage(
    viewportWidth,
    viewportHeight,
    naturalWidth,
    naturalHeight,
    { rotation = 0, flipX = false, flipY = false } = {},
) {
    if (
        ![viewportWidth, viewportHeight, naturalWidth, naturalHeight].every(
            (value) => Number.isFinite(value) && value > 0,
        ) ||
        !Number.isFinite(rotation) ||
        rotation % 90 !== 0
    ) {
        return null;
    }
    rotation = ((rotation % 360) + 360) % 360;
    const sideways = rotation % 180 !== 0;
    const scale = Math.min(
        1,
        viewportWidth / (sideways ? naturalHeight : naturalWidth),
        viewportHeight / (sideways ? naturalWidth : naturalHeight),
    );
    return constrain({
        viewportWidth,
        viewportHeight,
        naturalWidth,
        naturalHeight,
        rotation,
        flipX,
        flipY,
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
    const fitted = fitImage(width, height, state.naturalWidth, state.naturalHeight, state);
    if (!fitted || state.scale <= state.minScale + 0.0001) return fitted;
    const centerX = (state.viewportWidth / 2 - state.x) / state.scale;
    const centerY = (state.viewportHeight / 2 - state.y) / state.scale;
    const scale = Math.max(fitted.minScale, state.scale);
    return constrain({ ...fitted, scale, x: width / 2 - centerX * scale, y: height / 2 - centerY * scale });
}

export function imageBounds(state) {
    const sideways = state.rotation % 180 !== 0;
    return {
        width: (sideways ? state.naturalHeight : state.naturalWidth) * state.scale,
        height: (sideways ? state.naturalWidth : state.naturalHeight) * state.scale,
    };
}

export function rotateImage(state, degrees) {
    if (!Number.isFinite(degrees) || degrees % 90 !== 0 || degrees % 360 === 0) return state;
    const sideways = degrees % 180 !== 0;
    // Swap screen-axis reflections so a clockwise command still rotates a mirrored image clockwise.
    return orientImage(state, {
        rotation: state.rotation + degrees,
        flipX: sideways ? state.flipY : state.flipX,
        flipY: sideways ? state.flipX : state.flipY,
    });
}

export function flipImage(state, axis) {
    if (!["horizontal", "vertical"].includes(axis)) return state;
    return orientImage(state, {
        rotation: state.rotation,
        flipX: axis === "horizontal" ? !state.flipX : state.flipX,
        flipY: axis === "vertical" ? !state.flipY : state.flipY,
    });
}

function orientImage(state, orientation) {
    const fitted = fitImage(
        state.viewportWidth,
        state.viewportHeight,
        state.naturalWidth,
        state.naturalHeight,
        orientation,
    );
    if (state.scale <= state.minScale + 0.0001) return fitted;
    const oldBounds = imageBounds(state);
    const oldMatrix = orientationMatrix(state);
    const dx = (state.viewportWidth / 2 - state.x - oldBounds.width / 2) / state.scale;
    const dy = (state.viewportHeight / 2 - state.y - oldBounds.height / 2) / state.scale;
    // Invert the orthogonal transform to retain the source point being inspected at the viewport center.
    const sourceX = oldMatrix.a * dx + oldMatrix.b * dy;
    const sourceY = oldMatrix.c * dx + oldMatrix.d * dy;
    const scale = Math.max(fitted.minScale, state.scale);
    const bounds = imageBounds({ ...fitted, scale });
    const matrix = orientationMatrix(fitted);
    return constrain({
        ...fitted,
        scale,
        x: state.viewportWidth / 2 - bounds.width / 2 - (matrix.a * sourceX + matrix.c * sourceY) * scale,
        y: state.viewportHeight / 2 - bounds.height / 2 - (matrix.b * sourceX + matrix.d * sourceY) * scale,
    });
}

function orientationMatrix({ rotation, flipX, flipY }) {
    const [a, b, c, d] = [
        [1, 0, 0, 1],
        [0, 1, -1, 0],
        [-1, 0, 0, -1],
        [0, -1, 1, 0],
    ][rotation / 90];
    const horizontal = flipX ? -1 : 1;
    const vertical = flipY ? -1 : 1;
    return { a: a * horizontal, b: b * vertical, c: c * horizontal, d: d * vertical };
}

function constrain(state) {
    const { width, height } = imageBounds(state);
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
