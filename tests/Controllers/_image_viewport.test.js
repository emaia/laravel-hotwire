import { describe, expect, test } from "bun:test";
import {
    fitImage,
    flipImage,
    imageBounds,
    panImage,
    resizeImage,
    rotateImage,
    zoomImage,
} from "../../resources/js/controllers/_image_viewport.js";

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

    test("refits quarter turns using rotated bounds without upscaling small originals", () => {
        const state = fitImage(800, 600, 1600, 800);
        const rotated = rotateImage(state, 90);
        expect(rotated).toMatchObject({ rotation: 90, scale: 0.375, minScale: 0.375, x: 250, y: 0 });
        expect(imageBounds(rotated)).toEqual({ width: 300, height: 600 });
        expect(rotateImage(state, -90)).toMatchObject({ rotation: 270, scale: 0.375, x: 250, y: 0 });
        expect(rotateImage(fitImage(800, 600, 100, 50), 90)).toMatchObject({ scale: 1, x: 375, y: 250 });
    });

    test("four quarter turns and two flips return fitted images to the same state", () => {
        const state = fitImage(800, 600, 1600, 800);
        let rotated = state;
        for (let turn = 0; turn < 4; turn++) rotated = rotateImage(rotated, 90);
        expect(rotated).toEqual(state);
        expect(flipImage(flipImage(state, "horizontal"), "horizontal")).toEqual(state);
        expect(flipImage(flipImage(state, "vertical"), "vertical")).toEqual(state);
    });

    test("keeps the inspected image point centered during a zoomed rotation when bounds permit", () => {
        const state = panImage(zoomImage(fitImage(800, 600, 1600, 1200), 2), -100, 50);
        expect(state).toMatchObject({ x: -1300, y: -850 });
        expect(rotateImage(state, 90)).toMatchObject({
            rotation: 90,
            scale: 2,
            minScale: 0.375,
            x: -850,
            y: -1400,
        });
    });

    test("keeps the inspected point centered when flipping either screen axis", () => {
        const state = panImage(zoomImage(fitImage(800, 600, 1600, 1200), 2), -100, 50);
        expect(flipImage(state, "horizontal")).toMatchObject({ flipX: true, flipY: false, x: -1100, y: -850 });
        expect(flipImage(state, "vertical")).toMatchObject({ flipX: false, flipY: true, x: -1300, y: -950 });
    });

    test("rotates the displayed orientation clockwise even after a flip", () => {
        const state = fitImage(800, 600, 1600, 800);
        const flipped = flipImage(state, "horizontal");
        const rotated = rotateImage(flipped, 90);
        expect(rotated).toMatchObject({ rotation: 90, flipX: false, flipY: true });
        expect(flipImage(rotated, "horizontal")).toMatchObject({ rotation: 90, flipX: true, flipY: true });
        expect(rotateImage(rotated, -90)).toEqual(flipped);
    });

    test("constrains pan against the oriented bitmap rather than its unrotated dimensions", () => {
        const state = zoomImage(rotateImage(fitImage(800, 600, 1600, 400), 90), 1);
        expect(panImage(state, 10000, 10000)).toMatchObject({ x: 200, y: 0 });
        expect(panImage(state, -10000, -10000)).toMatchObject({ x: 200, y: -1000 });
    });

    test("anchors zoom to the same point in an oriented and mirrored image", () => {
        const state = flipImage(rotateImage(fitImage(800, 600, 1600, 1200), 90), "horizontal");
        const zoomed = zoomImage(state, 1, 400, 300);
        expect(zoomed).toMatchObject({ rotation: 90, flipX: true, flipY: false, x: -200, y: -500 });
        expect(imageBounds(zoomed)).toEqual({ width: 1200, height: 1600 });
    });

    test("preserves orientation during resize and refits using the new rotated minimum", () => {
        const state = flipImage(rotateImage(fitImage(800, 600, 1600, 800), 90), "vertical");
        expect(resizeImage(state, 400, 300)).toMatchObject({
            rotation: 90,
            flipX: false,
            flipY: true,
            scale: 0.1875,
            x: 125,
            y: 0,
        });
        const zoomed = zoomImage(state, 2);
        expect(resizeImage(zoomed, 400, 300)).toMatchObject({
            rotation: 90,
            flipX: false,
            flipY: true,
            scale: 2,
            x: -600,
            y: -1450,
        });
    });

    test("fits the current orientation without resetting rotation or flips", () => {
        const state = zoomImage(flipImage(rotateImage(fitImage(800, 600, 1600, 800), 90), "horizontal"), 2);
        expect(fitImage(800, 600, 1600, 800, state)).toMatchObject({
            rotation: 90,
            flipX: true,
            flipY: false,
            scale: 0.375,
            x: 250,
            y: 0,
        });
    });

    test("ignores invalid rotation steps and flip axes", () => {
        const state = fitImage(800, 600, 1600, 800);
        for (const degrees of [NaN, Infinity, 45, "90"]) expect(rotateImage(state, degrees)).toBe(state);
        expect(flipImage(state, "diagonal")).toBe(state);
    });
});
