import { afterEach, expect, test } from "bun:test";

import { mountController } from "../../../resources/js/helpers/test_stimulus.js";
import DispatchController from "../../../resources/js/controllers/optimistic/dispatch_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("dispatch action: no template inside root → no stream appended", async () => {
    await mount(`
        <button data-controller="optimistic--dispatch">Go</button>
    `);

    mounted.controller.dispatch();

    expect(document.body.querySelectorAll("turbo-stream")).toHaveLength(0);
});

test.serial("dispatch action: emits a stream per template found in the root", async () => {
    await mount(`
        <div data-controller="optimistic--dispatch">
            <template data-optimistic-stream data-optimistic-target-id="a">
                <div>A</div>
            </template>
            <template data-optimistic-stream data-optimistic-target-id="b">
                <div>B</div>
            </template>
        </div>
    `);

    mounted.controller.dispatch();

    const streams = document.body.querySelectorAll("turbo-stream");
    expect(streams).toHaveLength(2);
    expect(streams[0].getAttribute("target")).toBe("a");
    expect(streams[1].getAttribute("target")).toBe("b");
});

test.serial("dispatch action does not look at templates outside the root", async () => {
    await mount(`
        <div>
            <button data-controller="optimistic--dispatch">Go</button>
            <template data-optimistic-stream data-optimistic-target-id="outside">
                <div>outside</div>
            </template>
        </div>
    `);

    mounted.controller.dispatch();

    expect(document.body.querySelectorAll("turbo-stream")).toHaveLength(0);
});

async function mount(html) {
    mounted = await mountController("optimistic--dispatch", DispatchController, html);
}
