import { afterEach, beforeEach, expect, mock, test } from "bun:test";

import { mountController, wait } from "../../resources/js/helpers/test_stimulus.js";

// --- ECharts mock ---
// Controller does `import * as echarts from "echarts/core"` + use([...]) calls.
// Swap the modules for a controllable fake.

const chartState = {
    instance: null,
    initCalls: [],
    useCalls: [],
    setOptionCalls: [],
    disposeCalls: 0,
    resizeCalls: 0,
    onCalls: [],
};

function createInstance() {
    const instance = {
        setOption: mock((option, notMerge, lazyUpdate) => {
            chartState.setOptionCalls.push({ option, notMerge, lazyUpdate });
        }),
        dispose: mock(() => {
            chartState.disposeCalls++;
        }),
        resize: mock(() => {
            chartState.resizeCalls++;
        }),
        on: mock((event, handler) => {
            chartState.onCalls.push({ event, handler });
        }),
    };
    return instance;
}

const initFn = mock((element, theme) => {
    const instance = createInstance();
    chartState.instance = instance;
    chartState.initCalls.push({ element, theme });
    return instance;
});

const useFn = mock((modules) => {
    chartState.useCalls.push(modules);
});

mock.module("echarts/core", () => ({ init: initFn, use: useFn }));
mock.module("echarts/charts", () => ({
    BarChart: "BarChart",
    LineChart: "LineChart",
    PieChart: "PieChart",
}));
mock.module("echarts/components", () => ({
    GridComponent: "GridComponent",
    TooltipComponent: "TooltipComponent",
    LegendComponent: "LegendComponent",
    TitleComponent: "TitleComponent",
    DatasetComponent: "DatasetComponent",
}));
mock.module("echarts/renderers", () => ({
    CanvasRenderer: "CanvasRenderer",
}));

// --- ResizeObserver stub ---
// happy-dom does not provide ResizeObserver. Stub a minimal one that captures observe/disconnect calls.

let lastResizeObserver = null;

class FakeResizeObserver {
    constructor(callback) {
        this.callback = callback;
        this.observedElements = [];
        this.disconnected = false;
        lastResizeObserver = this;
    }
    observe(element) {
        this.observedElements.push(element);
    }
    disconnect() {
        this.disconnected = true;
    }
    trigger() {
        this.callback();
    }
}

globalThis.ResizeObserver = FakeResizeObserver;

const ChartController = (await import("../../resources/js/controllers/chart_controller.js")).default;

let mounted;

beforeEach(() => {
    chartState.instance = null;
    chartState.initCalls = [];
    chartState.useCalls = [];
    chartState.setOptionCalls = [];
    chartState.disposeCalls = 0;
    chartState.resizeCalls = 0;
    chartState.onCalls = [];
    lastResizeObserver = null;
    globalThis.ResizeObserver = FakeResizeObserver;
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- connect / init ---

test.serial("connect initializes the chart on the controller element", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    expect(chartState.initCalls).toHaveLength(1);
    expect(chartState.initCalls[0].element).toBe(mounted.root);
});

test.serial("connect passes theme value to echarts.init when set", async () => {
    await mount(`<div data-controller="chart" data-chart-theme-value="dark" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    expect(chartState.initCalls[0].theme).toBe("dark");
});

test.serial("connect passes null theme when value is empty", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    expect(chartState.initCalls[0].theme).toBeNull();
});

// --- option application ---

test.serial("user option is applied via setOption on connect", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"Sales"}}'></div>`);

    expect(chartState.setOptionCalls).toHaveLength(1);
    expect(chartState.setOptionCalls[0].option).toEqual({ title: { text: "Sales" } });
});

test.serial("no setOption call when option is empty and no url", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{}'></div>`);

    expect(chartState.setOptionCalls).toHaveLength(0);
});

// --- url fetching ---

test.serial("urlValue triggers fetch and applies the response via setOption", async () => {
    const fetched = { title: { text: "From URL" }, series: [{ type: "bar", data: [1, 2, 3] }] };
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve(fetched) }));

    await mount(`<div data-controller="chart" data-chart-url-value="/api/charts/sales"></div>`);
    await wait(0);
    await wait(0);

    expect(globalThis.fetch).toHaveBeenCalledWith("/api/charts/sales");
    expect(chartState.setOptionCalls).toHaveLength(1);
    expect(chartState.setOptionCalls[0].option).toEqual(fetched);
});

test.serial("inline option takes precedence over url", async () => {
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve({}) }));

    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"Inline"}}' data-chart-url-value="/api/charts/sales"></div>`);
    await wait(0);

    expect(globalThis.fetch).not.toHaveBeenCalled();
    expect(chartState.setOptionCalls[0].option).toEqual({ title: { text: "Inline" } });
});

// --- ResizeObserver ---

test.serial("connect registers ResizeObserver on the element", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    expect(lastResizeObserver).not.toBeNull();
    expect(lastResizeObserver.observedElements).toContain(mounted.root);
});

test.serial("ResizeObserver callback triggers chart.resize", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    expect(chartState.resizeCalls).toBe(0);
    lastResizeObserver.trigger();
    expect(chartState.resizeCalls).toBe(1);
});

// --- setOption action ---

test.serial("setOption action delegates to chart.setOption with event.detail", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    chartState.setOptionCalls = []; // reset after connect

    mounted.controller.setOption({ detail: { series: [{ type: "line", data: [10, 20] }] } });

    expect(chartState.setOptionCalls).toHaveLength(1);
    expect(chartState.setOptionCalls[0].option).toEqual({ series: [{ type: "line", data: [10, 20] }] });
});

test.serial("setOption action respects replace flag for notMerge semantics", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);
    chartState.setOptionCalls = [];

    mounted.controller.setOption({ detail: { option: { title: { text: "Replaced" } }, replace: true } });

    expect(chartState.setOptionCalls[0].option).toEqual({ title: { text: "Replaced" } });
    expect(chartState.setOptionCalls[0].notMerge).toBe(true);
});

// --- hooks ---

test.serial("defaultOption hook is called and applied before user option", async () => {
    class WithDefaults extends ChartController {
        defaultOption() {
            return { animation: false, color: ["#000"] };
        }
    }

    mounted = await mountController(
        "chart",
        WithDefaults,
        `<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`
    );

    expect(chartState.setOptionCalls).toHaveLength(2);
    expect(chartState.setOptionCalls[0].option).toEqual({ animation: false, color: ["#000"] });
    expect(chartState.setOptionCalls[1].option).toEqual({ title: { text: "x" } });
});

test.serial("afterInit hook is called after chart init", async () => {
    let afterInitCalled = false;
    class WithHook extends ChartController {
        afterInit() {
            afterInitCalled = true;
        }
    }

    mounted = await mountController(
        "chart",
        WithHook,
        `<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`
    );

    expect(afterInitCalled).toBe(true);
});

// --- ready dispatch ---

test.serial("dispatches chart:ready event after init", async () => {
    const events = [];

    class WithSpy extends ChartController {
        dispatch(eventName, options) {
            events.push(eventName);
            return super.dispatch(eventName, options);
        }
    }

    mounted = await mountController(
        "chart",
        WithSpy,
        `<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`
    );

    expect(events).toContain("ready");
});

// --- disconnect cleanup ---

test.serial("disconnect disposes the chart and the observer", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}'></div>`);

    expect(chartState.disposeCalls).toBe(0);
    expect(lastResizeObserver.disconnected).toBe(false);

    mounted.controller.disconnect();

    expect(chartState.disposeCalls).toBe(1);
    expect(lastResizeObserver.disconnected).toBe(true);
});

// --- live polling ---

test.serial("pollValue > 0 with url schedules a poll cycle on connect", async () => {
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve({}) }));

    await mount(`<div data-controller="chart" data-chart-url-value="/api/sales" data-chart-poll-value="20"></div>`);

    expect(mounted.controller.pollTimer).not.toBeNull();
});

test.serial("polling fetches again after the configured interval", async () => {
    const payload = { title: { text: "tick" } };
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve(payload) }));

    await mount(`<div data-controller="chart" data-chart-url-value="/api/sales" data-chart-poll-value="20"></div>`);
    await wait(0); // first immediate loadFromUrl resolves

    expect(globalThis.fetch).toHaveBeenCalledTimes(1);

    await wait(40); // wait past the poll interval — next cycle should have fired

    expect(globalThis.fetch.mock.calls.length).toBeGreaterThanOrEqual(2);
});

test.serial("startPolling is idempotent: a second call while a timer is pending is a no-op", async () => {
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve({}) }));

    await mount(`<div data-controller="chart" data-chart-url-value="/api/sales" data-chart-poll-value="1000"></div>`);
    await wait(0);

    const firstTimer = mounted.controller.pollTimer;
    expect(firstTimer).not.toBeNull();

    mounted.controller.startPolling();

    expect(mounted.controller.pollTimer).toBe(firstTimer);
});

test.serial("pollValue = 0 does not schedule any timer", async () => {
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve({}) }));

    await mount(`<div data-controller="chart" data-chart-url-value="/api/sales"></div>`);

    expect(mounted.controller.pollTimer).toBeNull();
});

test.serial("polling does not start when there is no url, even with pollValue set", async () => {
    await mount(`<div data-controller="chart" data-chart-option-value='{"title":{"text":"x"}}' data-chart-poll-value="20"></div>`);

    expect(mounted.controller.pollTimer).toBeNull();
});

test.serial("polling continues after a fetch error", async () => {
    let attempt = 0;
    globalThis.fetch = mock(() => {
        attempt++;
        if (attempt === 1) return Promise.reject(new Error("network"));
        return Promise.resolve({ json: () => Promise.resolve({}) });
    });

    // Silence the unhandled rejection log from the setTimeout async callback.
    const originalError = console.error;
    console.error = mock(() => {});

    await mount(`<div data-controller="chart" data-chart-url-value="/api/sales" data-chart-poll-value="20"></div>`);
    await wait(40);

    console.error = originalError;

    // First fetch rejected; polling should still have fired at least one more cycle.
    expect(globalThis.fetch.mock.calls.length).toBeGreaterThanOrEqual(2);
});

test.serial("disconnect clears the pending poll timer", async () => {
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve({}) }));

    await mount(`<div data-controller="chart" data-chart-url-value="/api/sales" data-chart-poll-value="20"></div>`);
    await wait(0);

    expect(mounted.controller.pollTimer).not.toBeNull();

    mounted.controller.disconnect();

    expect(mounted.controller.pollTimer).toBeNull();
});

async function mount(html) {
    mounted = await mountController("chart", ChartController, html);
}
