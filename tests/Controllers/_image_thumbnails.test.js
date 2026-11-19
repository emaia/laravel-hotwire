import { expect, test } from "bun:test";
import { createImageThumbnails } from "../../resources/js/controllers/_image_thumbnails.js";

test("invalid thumbnail templates fail before consuming the list's current content", () => {
    let mutations = 0;
    const element = {
        replaceChildren() {
            mutations++;
        },
    };
    for (const firstElementChild of [null, { tagName: "A" }, { tagName: "BUTTON", querySelector: () => null }]) {
        expect(() => createImageThumbnails(element, { content: { firstElementChild } }, "image-viewer")).toThrow(
            "Image viewer thumbnails require a button template containing an image.",
        );
    }
    expect(mutations).toBe(0);
});
