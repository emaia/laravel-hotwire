import { expect, test } from "@playwright/test";

test("releases removed action elements when a Turbo Stream leaves the controller mounted", async ({ page }) => {
    await page.setContent(`
        <section id="root" data-controller="probe" data-action="poke@window->probe#poke">
            <button id="once" data-action="probe#once:once">Once</button>
            <div id="results"><button data-action="probe#click">Result 0</button></div>
        </section>
    `);
    await page.addScriptTag({ path: "node_modules/@hotwired/turbo/dist/turbo.es2017-umd.js" });
    await page.addScriptTag({
        path: process.env.STIMULUS_TEST_BUNDLE ?? "node_modules/@hotwired/stimulus/dist/stimulus.umd.js",
    });

    const result = await page.evaluate(async () => {
        const { Application, Controller } = window.Stimulus;
        const app = new Application();
        const events = { connect: 0, disconnect: 0, click: 0, global: 0, once: 0 };
        app.register(
            "probe",
            class extends Controller {
                connect() {
                    events.connect++;
                }
                disconnect() {
                    events.disconnect++;
                }
                click() {
                    events.click++;
                }
                poke() {
                    events.global++;
                }
                once() {
                    events.once++;
                }
            },
        );
        await app.start();

        const root = document.querySelector("#root");
        const once = document.querySelector("#once");
        once.click();

        for (let index = 1; index <= 6; index++) {
            const old = document.querySelector("#results button");
            window.Turbo.renderStreamMessage(`
                <turbo-stream action="update" target="results"><template>
                    <button data-action="probe#click">Result ${index}</button>
                </template></turbo-stream>
            `);
            while (old.isConnected) await new Promise(requestAnimationFrame);
            await new Promise(queueMicrotask);
            old.click();
            document.querySelector("#results button").click();
            window.dispatchEvent(new Event("poke"));
            once.click();
        }

        const listeners = app.dispatcher?.eventListeners;
        if (!Array.isArray(listeners) || listeners.some((listener) => !listener?.eventTarget)) {
            throw new Error(
                "Cannot measure Stimulus action retention: expected app.dispatcher.eventListeners to expose eventTarget. " +
                    "Update the probe for this Stimulus build before deciding whether to return to npm.",
            );
        }
        if (!listeners.some((listener) => listener.eventTarget === window)) {
            throw new Error(
                "Cannot measure Stimulus action retention: the probe's window action listener was not registered. " +
                    "Check the fixture and Stimulus startup before interpreting retention results.",
            );
        }

        return {
            events,
            sameRoot: document.querySelector("#root") === root,
            // A detached button can stay referenced even after its action stops firing.
            retained: listeners.filter(({ eventTarget }) => eventTarget instanceof Element && !eventTarget.isConnected)
                .length,
        };
    });

    expect(result).toEqual({
        events: { connect: 1, disconnect: 0, click: 6, global: 6, once: 1 },
        sameRoot: true,
        retained: 0,
    });
});
