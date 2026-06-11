import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import OembedController from "../../resources/js/controllers/oembed_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- YouTube ---

test.serial("replaces youtube.com/watch URL with iframe and preserves root", async () => {
    await mount(`
        <oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed>
        <p id="sibling">survives</p>
    `);

    const root = document.querySelector('[data-controller~="oembed"]');
    expect(root).not.toBeNull();

    const sibling = document.querySelector("#sibling");
    expect(sibling).not.toBeNull();

    const iframe = document.querySelector("iframe");
    expect(iframe).not.toBeNull();
    expect(iframe.src).toBe("https://www.youtube.com/embed/dQw4w9WgXcQ");
});

test.serial("replaces youtu.be short URL with iframe", async () => {
    await mount(`<oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed>`);

    const iframe = document.querySelector("iframe");
    expect(iframe.src).toBe("https://www.youtube.com/embed/dQw4w9WgXcQ");
});

test.serial("replaces youtube.com/shorts URL with iframe", async () => {
    await mount(`<oembed url="https://www.youtube.com/shorts/dQw4w9WgXcQ"></oembed>`);

    const iframe = document.querySelector("iframe");
    expect(iframe.src).toBe("https://www.youtube.com/embed/dQw4w9WgXcQ");
});

test.serial("replaces youtube.com/embed URL with iframe", async () => {
    await mount(`<oembed url="https://www.youtube.com/embed/dQw4w9WgXcQ"></oembed>`);

    const iframe = document.querySelector("iframe");
    expect(iframe.src).toBe("https://www.youtube.com/embed/dQw4w9WgXcQ");
});

// --- Vimeo ---

test.serial("replaces Vimeo URL with iframe", async () => {
    await mount(`<oembed url="https://vimeo.com/123456789"></oembed>`);

    const iframe = document.querySelector("iframe");
    expect(iframe.src).toBe("https://player.vimeo.com/video/123456789");
});

// --- iframe attributes ---

test.serial("iframe has allowfullscreen and allow attributes", async () => {
    await mount(`<oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed>`);

    const iframe = document.querySelector("iframe");
    expect(iframe.getAttribute("allowfullscreen")).toBe("");
    expect(iframe.getAttribute("allow")).toContain("autoplay");
    expect(iframe.getAttribute("allow")).toContain("encrypted-media");
    expect(iframe.getAttribute("frameborder")).toBe("0");
});

test.serial("iframe wrapper has 16:9 aspect ratio", async () => {
    await mount(`<oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed>`);

    const wrapper = document.querySelector("iframe").parentElement;
    expect(wrapper.style.aspectRatio).toBe("16 / 9");
    expect(wrapper.style.width).toBe("100%");
});

// --- fallback for unknown URLs ---

test.serial("creates a link for unknown URL providers", async () => {
    await mount(`<oembed url="https://example.com/video"></oembed>`);

    const link = document.querySelector("a");
    expect(link).not.toBeNull();
    expect(link.href).toBe("https://example.com/video");
    expect(link.target).toBe("_blank");
    expect(link.rel).toBe("noopener noreferrer");
    expect(link.textContent).toBe("https://example.com/video");
    expect(document.querySelector("iframe")).toBeNull();
});

// --- multiple oembed elements ---

test.serial("processes multiple oembed elements", async () => {
    await mount(`
        <figure><oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed></figure>
        <figure><oembed url="https://vimeo.com/123456789"></oembed></figure>
    `);

    const iframes = document.querySelectorAll("iframe");
    expect(iframes.length).toBe(2);
    expect(iframes[0].src).toBe("https://www.youtube.com/embed/dQw4w9WgXcQ");
    expect(iframes[1].src).toBe("https://player.vimeo.com/video/123456789");
});

// --- wrapper replaces figure when inside one ---

test.serial("wrapper replaces the figure ancestor when present", async () => {
    await mount(`
        <figure>
            <oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed>
        </figure>
    `);

    expect(document.querySelector("figure")).toBeNull();
    expect(document.querySelector("iframe")).not.toBeNull();
});

async function mount(innerHTML) {
    mounted = await mountController("oembed", OembedController, `
        <div data-controller="oembed">${innerHTML}</div>
    `);
}
