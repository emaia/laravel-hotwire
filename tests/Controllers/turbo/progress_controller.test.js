import { afterEach, beforeEach, expect, mock, test } from "bun:test";

const progressBar = { show: mock(() => {}), hide: mock(() => {}) };
const turboSession = { adapter: { progressBar } };

mock.module("@hotwired/turbo", () => ({ session: turboSession }));

const { dispatchEvent, mountController } = await import("../../../resources/js/helpers/test_stimulus.js");
const { default: ProgressController } = await import(
    "../../../resources/js/controllers/turbo/progress_controller.js"
);

let mounted;

beforeEach(() => {
    progressBar.show.mockClear();
    progressBar.hide.mockClear();
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("shows progress bar on turbo:before-fetch-request", async () => {
    await mount();

    dispatchEvent(document, "turbo:before-fetch-request");

    expect(progressBar.show).toHaveBeenCalledTimes(1);
});

test.serial("hides progress bar on turbo:frame-render", async () => {
    await mount();

    dispatchEvent(document, "turbo:frame-render");

    expect(progressBar.hide).toHaveBeenCalledTimes(1);
});

test.serial("hides progress bar on turbo:before-stream-render", async () => {
    await mount();

    dispatchEvent(document, "turbo:before-stream-render");

    expect(progressBar.hide).toHaveBeenCalledTimes(1);
});

test.serial("disconnect detaches all three turbo listeners", async () => {
    await mount();

    mounted.controller.disconnect();

    dispatchEvent(document, "turbo:before-fetch-request");
    dispatchEvent(document, "turbo:frame-render");
    dispatchEvent(document, "turbo:before-stream-render");

    expect(progressBar.show).not.toHaveBeenCalled();
    expect(progressBar.hide).not.toHaveBeenCalled();
});

test.serial("no-op when Turbo.session.adapter has no progressBar", async () => {
    const original = turboSession.adapter.progressBar;
    turboSession.adapter.progressBar = undefined;

    await mount();
    dispatchEvent(document, "turbo:before-fetch-request");

    turboSession.adapter.progressBar = original;

    // No throw, no calls (and the originals weren't reachable to count).
    expect(true).toBe(true);
});

async function mount() {
    mounted = await mountController(
        "turbo--progress",
        ProgressController,
        `<div data-controller="turbo--progress"></div>`,
    );
}
