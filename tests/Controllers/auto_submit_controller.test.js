import { afterEach, expect, test } from "bun:test";

import {
    dispatchEvent,
    mountController,
    mountMultipleControllers,
    wait,
} from "../../resources/js/helpers/test_stimulus.js";
import AutoSubmitController from "../../resources/js/controllers/auto_submit_controller.js";
import MoneyInputController from "../../resources/js/controllers/money_input_controller.js";

let mounted;

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

test.serial("submit fires immediately", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <select data-action="change->auto-submit#submit"></select>
        </form>
    `);

    const { select, submits } = elements();

    dispatchEvent(select, "change");

    expect(submits()).toBe(1);
});

test.serial("submit ignores composing and prevented events before accepting the committed event", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <input data-action="input->auto-submit#submit">
        </form>
    `);

    const { input, submits } = elements();
    const composing = new Event("input", { bubbles: true, cancelable: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);

    const prevented = new Event("input", { bubbles: true, cancelable: true });
    prevented.preventDefault();
    input.dispatchEvent(prevented);

    expect(submits()).toBe(0);

    dispatchEvent(input, "input");

    expect(submits()).toBe(1);
});

test.serial("submit falls back to compositionend when no committed input follows", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <input data-action="input->auto-submit#submit">
        </form>
    `);

    const { input, submits } = elements();
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);
    input.dispatchEvent(new Event("compositionend", { bubbles: true }));
    await wait(0);

    expect(submits()).toBe(1);
});

test.serial("a prevented committed input cancels the compositionend fallback", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <input data-action="input->auto-submit#submit">
        </form>
    `);

    const { input, submits } = elements();
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);
    input.dispatchEvent(new Event("compositionend", { bubbles: true }));

    const committed = new Event("input", { bubbles: true, cancelable: true });
    committed.preventDefault();
    input.dispatchEvent(committed);
    await wait(0);

    expect(submits()).toBe(0);
});

test.serial("debouncedSubmit coalesces rapid events into a single request", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="20">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();
    const scheduler = installFakeSubmitScheduler();

    dispatchEvent(input, "input");
    dispatchEvent(input, "input");
    dispatchEvent(input, "input");

    expect(submits()).toBe(0);

    scheduler.runNext();

    expect(submits()).toBe(1);
});

test.serial("debouncedSubmit cancels pending work during composition and schedules the committed value", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="20">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();
    const scheduler = installFakeSubmitScheduler();

    dispatchEvent(input, "input");
    const composing = new Event("input", { bubbles: true, cancelable: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    composing.preventDefault();
    input.dispatchEvent(composing);

    expect(scheduler.pending()).toHaveLength(0);
    expect(submits()).toBe(0);

    dispatchEvent(input, "input");
    scheduler.runNext();

    expect(submits()).toBe(1);
});

test.serial("submits a committed Money Input value once after formatting", async () => {
    mounted = await mountMultipleControllers(
        {
            "auto-submit": AutoSubmitController,
            "money-input": MoneyInputController,
        },
        `
            <form data-controller="auto-submit" data-auto-submit-delay-value="0">
                <input type="hidden" id="amount-raw">
                <input
                    name="amount"
                    data-controller="money-input"
                    data-money-input-hidden-id-value="amount-raw"
                    data-action="input->auto-submit#debouncedSubmit"
                >
            </form>
        `,
    );

    const form = document.querySelector("form");
    const input = document.querySelector('[data-controller="money-input"]');
    const raw = document.querySelector("#amount-raw");
    const submissions = [];
    form.requestSubmit = () => submissions.push({ visible: input.value, raw: raw.value });

    input.value = "12";
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);

    expect(submissions).toEqual([]);

    input.dispatchEvent(new Event("input", { bubbles: true }));

    expect(submissions).toEqual([{ visible: "12.00", raw: "1200" }]);
});

test.serial("debouncedSubmit can use a per-field delay action param", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="20">
            <input data-action="input->auto-submit#debouncedSubmit" data-auto-submit-delay-param="75">
        </form>
    `);

    const { input } = elements();
    const scheduler = installFakeSubmitScheduler();

    dispatchEvent(input, "input");

    expect(scheduler.pending()[0].delay).toBe(75);
});

test.serial("debouncedSubmit resolves its delay once per event", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="20">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input } = elements();
    const submitDelay = mounted.controller.submitDelay.bind(mounted.controller);
    let calls = 0;
    mounted.controller.submitDelay = (event) => {
        calls += 1;

        return submitDelay(event);
    };

    dispatchEvent(input, "input");

    expect(calls).toBe(1);
});

test.serial("debouncedSubmit is debounced by default", async () => {
    await setup(`
        <form data-controller="auto-submit">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");

    expect(submits()).toBe(0);
});

test.serial("a delay of 0 makes debouncedSubmit immediate", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");

    expect(submits()).toBe(1);
});

test.serial("a delay of 0 still waits for the committed composition event", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);

    expect(submits()).toBe(0);

    dispatchEvent(input, "input");

    expect(submits()).toBe(1);
});

test.serial("debouncedSubmit falls back to compositionend when the commit input is still composing", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    input.dispatchEvent(composing);
    input.dispatchEvent(new Event("compositionend", { bubbles: true }));
    await wait(0);

    expect(submits()).toBe(1);
});

test.serial("compositionend fallback survives a trailing input still marked composing", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();
    const candidate = new Event("input", { bubbles: true });
    Object.defineProperty(candidate, "isComposing", { value: true });
    input.dispatchEvent(candidate);
    input.dispatchEvent(new Event("compositionend", { bubbles: true }));

    const trailing = new Event("input", { bubbles: true });
    Object.defineProperty(trailing, "isComposing", { value: true });
    input.dispatchEvent(trailing);
    await wait(0);

    expect(submits()).toBe(1);
});

test.serial("a committed event in another field cancels pending form fallbacks", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="0">
            <input id="first" data-action="input->auto-submit#debouncedSubmit">
            <input id="second" data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { submits } = elements();
    const first = document.querySelector("#first");
    const second = document.querySelector("#second");
    const composing = new Event("input", { bubbles: true });
    Object.defineProperty(composing, "isComposing", { value: true });
    first.dispatchEvent(composing);
    first.dispatchEvent(new Event("compositionend", { bubbles: true }));

    second.dispatchEvent(new Event("input", { bubbles: true }));
    await wait(0);

    expect(submits()).toBe(1);
});

test.serial("a per-field delay of 0 makes debouncedSubmit immediate", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="50">
            <input data-action="input->auto-submit#debouncedSubmit" data-auto-submit-delay-param="0">
        </form>
    `);

    const { input, submits } = elements();

    dispatchEvent(input, "input");

    expect(submits()).toBe(1);
});

test.serial("submit cancels a pending debounced submit", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="50">
            <input data-action="input->auto-submit#debouncedSubmit">
            <select data-action="change->auto-submit#submit"></select>
        </form>
    `);

    const { input, select, submits } = elements();
    const scheduler = installFakeSubmitScheduler();

    dispatchEvent(input, "input");
    const pendingTimer = scheduler.pending()[0];
    dispatchEvent(select, "change");

    expect(submits()).toBe(1);
    expect(pendingTimer.cancelled).toBe(true);
    expect(submits()).toBe(1);
});

test.serial("disconnect cancels a pending debounced submit", async () => {
    await setup(`
        <form data-controller="auto-submit" data-auto-submit-delay-value="50">
            <input data-action="input->auto-submit#debouncedSubmit">
        </form>
    `);

    const { input, submits } = elements();
    const scheduler = installFakeSubmitScheduler();

    dispatchEvent(input, "input");
    const pendingTimer = scheduler.pending()[0];
    mounted.controller.disconnect();

    expect(submits()).toBe(0);
    expect(pendingTimer.cancelled).toBe(true);
    expect(scheduler.pending()).toHaveLength(0);
});

function elements() {
    const form = document.querySelector("form");
    let count = 0;
    form.requestSubmit = () => {
        count++;
    };

    return {
        form,
        input: document.querySelector("input"),
        select: document.querySelector("select"),
        submits: () => count,
    };
}

async function setup(html) {
    mounted = await mountController("auto-submit", AutoSubmitController, html);
}

function installFakeSubmitScheduler() {
    const timers = [];

    mounted.controller.setSubmitTimer = (callback, delay) => {
        const timer = { callback, delay, cancelled: false };
        timers.push(timer);

        return timer;
    };

    mounted.controller.clearSubmitTimer = (timer) => {
        if (timer) timer.cancelled = true;
    };

    return {
        pending() {
            return timers.filter((timer) => !timer.cancelled);
        },
        runNext() {
            const timer = this.pending()[0];

            if (!timer) {
                throw new Error("Expected a pending auto-submit timer.");
            }

            timer.cancelled = true;
            timer.callback();
        },
    };
}
