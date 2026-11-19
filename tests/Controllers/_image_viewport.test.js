import { describe, expect, test } from "bun:test";
import { fitImage, panImage, resizeImage, zoomImage } from "../../resources/js/controllers/_image_viewport.js";

describe("image viewport geometry", () => {
    test("fits landscape and portrait images without enlarging small originals", () => {
        expect(fitImage(800, 600, 1600, 800)).toMatchObject({ scale: 0.5, x: 0, y: 100 });
        expect(fitImage(800, 600, 400, 1200)).toMatchObject({ scale: 0.5, x: 300, y: 0 });
        expect(fitImage(800, 600, 100, 100)).toMatchObject({ scale: 1, x: 350, y: 250 });
    });

    test("rejects unavailable and non-finite measurements", () => {
        for (const dimensions of [
            [0, 600, 100, 100],
            [800, 600, 0, 100],
            [800, Infinity, 100, 100],
        ]) {
            expect(fitImage(...dimensions)).toBeNull();
        }
    });

    test("keeps the image point under the zoom pivot stationary", () => {
        const state = fitImage(800, 600, 1600, 1200);
        expect(zoomImage(state, 1, 200, 150)).toMatchObject({ scale: 1, x: -200, y: -150 });
        expect(state).toMatchObject({ scale: 0.5, x: 0, y: 0 });
    });

    test("clamps zoom to fit and maximum natural scale", () => {
        const state = fitImage(800, 600, 1600, 1200);
        expect(zoomImage(state, 0.01)).toMatchObject({ scale: 0.5, x: 0, y: 0 });
        expect(zoomImage(state, 100).scale).toBe(4);
        expect(zoomImage(state, NaN)).toEqual(state);
    });

    test("limits panning so enlarged images cannot leave the viewport", () => {
        const state = zoomImage(fitImage(800, 600, 1600, 1200), 1);
        expect(panImage(state, 10000, 10000)).toMatchObject({ x: 0, y: 0 });
        expect(panImage(state, -10000, -10000)).toMatchObject({ x: -800, y: -600 });
    });

    test("keeps dimensions smaller than the viewport centered", () => {
        const state = zoomImage(fitImage(800, 600, 1600, 400), 1);
        expect(panImage(state, -50, -50)).toMatchObject({ x: -450, y: 100 });
    });

    test("refits an unzoomed image after resizing", () => {
        expect(resizeImage(fitImage(800, 600, 1600, 1200), 400, 300)).toMatchObject({ scale: 0.25, x: 0, y: 0 });
    });

    test("preserves the viewed center of a zoomed image after resizing", () => {
        const state = zoomImage(fitImage(800, 600, 1600, 1200), 1);
        expect(resizeImage(state, 400, 300)).toMatchObject({ scale: 1, x: -600, y: -450 });
    });
});
