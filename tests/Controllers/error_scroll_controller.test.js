import { afterEach, expect, test, mock } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";
import ErrorScrollController from "../../resources/js/controllers/error_scroll_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("scrolls to first error on frame render", async () => {
    const { root } = await mount(`
        <turbo-frame data-controller="error-scroll">
            <form>
                <input type="text" name="email" />
                <span aria-invalid="true">Required</span>
                <input type="text" name="name" />
                <span aria-invalid="true">Required</span>
            </form>
        </turbo-frame>
    `);

    const firstError = root.querySelector("[aria-invalid]");
    firstError.scrollIntoView = mock(() => {});

    document.dispatchEvent(new Event("turbo:frame-render", { bubbles: true }));
    await wait(50);

    expect(firstError.scrollIntoView).toHaveBeenCalledTimes(1);
    expect(firstError.scrollIntoView.mock.calls[0][0]).toEqual({
        behavior: "smooth",
        block: "center",
    });
});

test.serial("does nothing when no error elements present", async () => {
    const { root } = await mount(`
        <turbo-frame data-controller="error-scroll">
            <form>
                <input type="text" name="email" />
            </form>
        </turbo-frame>
    `);

    document.dispatchEvent(new Event("turbo:frame-render", { bubbles: true }));
    await wait(50);

    // Should not throw
});

test.serial("respects custom selector value", async () => {
    const { root } = await mount(`
        <turbo-frame
            data-controller="error-scroll"
            data-error-scroll-selector-value=".has-error"
        >
            <form>
                <input type="text" class="has-error" name="email" />
            </form>
        </turbo-frame>
    `);

    const target = root.querySelector(".has-error");
    target.scrollIntoView = mock(() => {});

    document.dispatchEvent(new Event("turbo:frame-render", { bubbles: true }));
    await wait(50);

    expect(target.scrollIntoView).toHaveBeenCalledTimes(1);
});

test.serial("respects custom block value", async () => {
    const { root } = await mount(`
        <turbo-frame
            data-controller="error-scroll"
            data-error-scroll-block-value="start"
        >
            <form>
                <span aria-invalid="true">Error</span>
            </form>
        </turbo-frame>
    `);

    const target = root.querySelector("[aria-invalid]");
    target.scrollIntoView = mock(() => {});

    document.dispatchEvent(new Event("turbo:frame-render", { bubbles: true }));
    await wait(50);

    expect(target.scrollIntoView.mock.calls[0][0]).toEqual({
        behavior: "smooth",
        block: "start",
    });
});

test.serial("removes listeners on disconnect", async () => {
    const { root, controller } = await mountRaw(`
        <turbo-frame data-controller="error-scroll">
            <form>
                <span aria-invalid="true">Error</span>
            </form>
        </turbo-frame>
    `);

    const target = root.querySelector("[aria-invalid]");
    target.scrollIntoView = mock(() => {});

    controller.disconnect();

    document.dispatchEvent(new Event("turbo:frame-render", { bubbles: true }));
    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(target.scrollIntoView).toHaveBeenCalledTimes(0);
});

test.serial("scrolls to first error on full-page render", async () => {
    const { root } = await mount(`
        <form data-controller="error-scroll">
            <input type="text" name="email" />
            <span aria-invalid="true">Required</span>
            <input type="text" name="name" />
            <span aria-invalid="true">Required</span>
        </form>
    `);

    const firstError = root.querySelector("[aria-invalid]");
    firstError.scrollIntoView = mock(() => {});

    document.dispatchEvent(new Event("turbo:render", { bubbles: true }));
    await wait(50);

    expect(firstError.scrollIntoView).toHaveBeenCalledTimes(1);
    expect(firstError.scrollIntoView.mock.calls[0][0]).toEqual({
        behavior: "smooth",
        block: "center",
    });
});

// --- Helpers ---

async function mount(html) {
    mounted = await mountController("error-scroll", ErrorScrollController, html);
    return { root: mounted.root, controller: mounted.controller };
}

async function mountRaw(html) {
    const result = await mountController("error-scroll", ErrorScrollController, html);
    return { root: result.root, controller: result.controller };
}
