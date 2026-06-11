import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController } from "../../resources/js/helpers/test_stimulus.js";
import DevLogController from "../../resources/js/controllers/dev/log_controller.js";

let mounted;
let originalConsoleLog;

beforeEach(() => {
    originalConsoleLog = console.log;
    console.log = mock(() => {});
});

afterEach(async () => {
    console.log = originalConsoleLog;
    await mounted?.cleanup();
    mounted = null;
});

test.serial("log action outputs event info to console.log", async () => {
    await mount(`<div data-controller="dev--log"></div>`);

    const fakeEvent = { type: "click", target: mounted.root };
    mounted.controller.log(fakeEvent);

    expect(console.log).toHaveBeenCalledTimes(2);
    expect(console.log.mock.calls[0][0]).toBe("Logging event...");
    expect(console.log.mock.calls[1][0]).toBe("Event:");
    expect(console.log.mock.calls[1][1]).toBe(fakeEvent);
});

test.serial("log action works with keyboard event", async () => {
    await mount(`<div data-controller="dev--log"></div>`);

    const keyEvent = new KeyboardEvent("keydown", { key: "a" });
    mounted.controller.log(keyEvent);

    expect(console.log).toHaveBeenCalledTimes(2);
});

async function mount(html) {
    mounted = await mountController("dev--log", DevLogController, html);
}
