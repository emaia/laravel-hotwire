import { afterEach, beforeEach, expect, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import LazyImageController from "../../resources/js/controllers/lazy_image_controller.js";

let mounted;
let probes;
let originalImage;

beforeEach(() => {
    probes = [];
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
    if (originalImage) {
        globalThis.Image = originalImage;
        originalImage = null;
    }
});

function stubImage({ outcome = "success" } = {}) {
    originalImage = globalThis.Image;
    globalThis.Image = class {
        constructor() {
            probes.push(this);
        }
        set src(value) {
            this._src = value;
            queueMicrotask(() => {
                if (outcome === "success") this.onload?.();
                else this.onerror?.();
            });
        }
        get src() { return this._src; }
    };
}

// --- successful probe ---

test.serial("renders an <img> with src/alt and replaces innerHTML on first success", async () => {
    stubImage({ outcome: "success" });

    await mount({ url: "/photo.png", alt: "Hero" });
    await wait(10);

    const img = mounted.root.querySelector("img");
    expect(img).not.toBeNull();
    expect(img.getAttribute("src")).toBe("/photo.png");
    expect(img.alt).toBe("Hero");
});

test.serial("applies width, height and imgClass when set", async () => {
    stubImage();

    await mount({ url: "/photo.png", width: 320, height: 200, imgClass: "rounded shadow" });
    await wait(10);

    const img = mounted.root.querySelector("img");
    expect(img.width).toBe(320);
    expect(img.height).toBe(200);
    expect(img.className).toBe("rounded shadow");
});

test.serial("renders <source> entries from sources value before the <img>", async () => {
    stubImage();

    await mount({
        url: "/photo.png",
        sources: JSON.stringify([
            { media: "(max-width: 600px)", srcset: "/small.png" },
            { media: "(min-width: 601px)", srcset: "/large.png" },
        ]),
    });
    await wait(10);

    const sources = mounted.root.querySelectorAll("source");
    expect(sources.length).toBe(2);
    expect(sources[0].media).toBe("(max-width: 600px)");
    expect(sources[0].srcset).toBe("/small.png");
    expect(sources[1].srcset).toBe("/large.png");
});

// --- retries ---

test.serial("retries up to maxAttempts on probe error", async () => {
    stubImage({ outcome: "error" });

    await mount({ url: "/missing.png", interval: 1, maxAttempts: 3 });
    await wait(40);

    // First probe + 2 retries = 3 attempts total.
    expect(probes.length).toBe(3);
    expect(mounted.root.querySelector("img")).toBeNull();
});

test.serial("stops retrying after a success", async () => {
    // First two probes fail; third succeeds.
    let calls = 0;
    originalImage = globalThis.Image;
    globalThis.Image = class {
        constructor() { probes.push(this); }
        set src(value) {
            this._src = value;
            const idx = ++calls;
            queueMicrotask(() => {
                if (idx >= 3) this.onload?.();
                else this.onerror?.();
            });
        }
        get src() { return this._src; }
    };

    await mount({ url: "/photo.png", interval: 1, maxAttempts: 10 });
    await wait(40);

    expect(probes.length).toBe(3);
    expect(mounted.root.querySelector("img")).not.toBeNull();
});

// --- disconnect ---

test.serial("disconnect cancels a pending retry timer", async () => {
    stubImage({ outcome: "error" });

    await mount({ url: "/missing.png", interval: 30, maxAttempts: 10 });
    await wait(5);

    const probesBefore = probes.length;
    mounted.controller.disconnect();
    await wait(60);

    expect(probes.length).toBe(probesBefore);
});

async function mount({ url, alt = "", interval = 1, maxAttempts = 20, width = 0, height = 0, imgClass = "", sources = "[]" } = {}) {
    const attrs = [
        `data-lazy-image-url-value="${url}"`,
        `data-lazy-image-alt-value="${alt}"`,
        `data-lazy-image-interval-value="${interval}"`,
        `data-lazy-image-max-attempts-value="${maxAttempts}"`,
        `data-lazy-image-width-value="${width}"`,
        `data-lazy-image-height-value="${height}"`,
        `data-lazy-image-img-class-value="${imgClass}"`,
        `data-lazy-image-sources-value='${sources}'`,
    ].join(" ");
    mounted = await mountController(
        "lazy-image",
        LazyImageController,
        `<picture data-controller="lazy-image" ${attrs}></picture>`,
    );
}
