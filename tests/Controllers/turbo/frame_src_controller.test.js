import { afterEach, expect, test } from "bun:test";

import { mountController, wait } from "../../../resources/js/helpers/test_stimulus.js";
import FrameSrcController from "../../../resources/js/controllers/turbo/frame_src_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("injects X-Turbo-Frame-Src header when Turbo-Frame header is present", async () => {
    await mount(`
        <form data-controller="frame-src" method="post" action="/posts">
            <input name="title" />
        </form>
    `);

    const event = new CustomEvent("turbo:before-fetch-request", {
        detail: {
            fetchOptions: {
                headers: { "Turbo-Frame": "content" },
            },
        },
    });

    document.dispatchEvent(event);

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBe("http://localhost/");
});

test.serial("does not inject X-Turbo-Frame-Src when Turbo-Frame header is absent", async () => {
    await mount(`
        <form data-controller="frame-src" method="post" action="/posts">
            <input name="title" />
        </form>
    `);

    const event = new CustomEvent("turbo:before-fetch-request", {
        detail: {
            fetchOptions: {
                headers: {},
            },
        },
    });

    document.dispatchEvent(event);

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

test.serial("does not inject X-Turbo-Frame-Src after disconnect", async () => {
    await mount(`
        <form data-controller="frame-src" method="post" action="/posts">
            <input name="title" />
        </form>
    `);

    mounted.controller.disconnect();

    const event = new CustomEvent("turbo:before-fetch-request", {
        detail: {
            fetchOptions: {
                headers: { "Turbo-Frame": "content" },
            },
        },
    });

    document.dispatchEvent(event);

    expect(event.detail.fetchOptions.headers["X-Turbo-Frame-Src"]).toBeUndefined();
});

async function mount(html) {
    mounted = await mountController("frame-src", FrameSrcController, html);
}
