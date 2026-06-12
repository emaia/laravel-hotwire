// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import * as echarts from "echarts/core";
import { BarChart, LineChart, PieChart } from "echarts/charts";
import {
    DatasetComponent,
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";

import { attachMorphRecovery } from "./_turbo_morph_recovery.js";

echarts.use([
    BarChart,
    LineChart,
    PieChart,
    DatasetComponent,
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
    CanvasRenderer,
]);

export default class extends Controller {
    static values = {
        option: { type: Object, default: {} },
        url: { type: String, default: "" },
        theme: { type: String, default: "" },
        poll: { type: Number, default: 0 },
    };

    chart = null;
    observer = null;
    pollTimer = null;
    inflight = false;

    connect() {
        this.initChart();

        this.detachMorphRecovery = attachMorphRecovery(this, {
            isStale: () => !this.element.querySelector("canvas"),
            recover: () => this.initChart(),
        });
    }

    disconnect() {
        this.detachMorphRecovery?.();
        this.destroyChart();
    }

    initChart() {
        if (this.chart && !this.chart.isDisposed()) {
            this.chart.dispose();
        }
        if (this.observer) {
            this.observer.disconnect();
        }

        this.chart = echarts.init(this.element, this.themeValue || null);

        const defaults = this.defaultOption();
        if (Object.keys(defaults).length > 0) {
            this.chart.setOption(defaults);
        }

        if (Object.keys(this.optionValue).length > 0) {
            this.chart.setOption(this.optionValue);
        } else if (this.urlValue !== "") {
            this.loadFromUrl().catch((error) => {
                console.error("Chart initial fetch failed:", error);
            });
            this.startPolling();
        }

        this.afterInit();

        this.observer = new ResizeObserver(() => this.chart?.resize());
        this.observer.observe(this.element);

        this.dispatch("ready");
    }

    destroyChart() {
        this.stopPolling();
        this.observer?.disconnect();
        this.observer = null;
        this.chart?.dispose();
        this.chart = null;
    }

    /** Public action — re-fetch the URL and apply via setOption (animated merge, no canvas teardown).
     *  Wire from the app via any event name: data-action="kanban:updated@window->chart#reload". */
    reload() {
        if (this.urlValue === "") return;
        this.loadFromUrl().catch((error) => {
            console.error("Chart reload fetch failed:", error);
        });
    }

    startPolling() {
        if (this.pollTimer !== null) return;
        if (this.pollValue <= 0) return;
        if (this.urlValue === "") return;

        this.pollTimer = setTimeout(async () => {
            this.pollTimer = null;
            try {
                await this.loadFromUrl();
            } catch (error) {
                console.error("Chart polling fetch failed:", error);
            } finally {
                this.startPolling();
            }
        }, this.pollValue);
    }

    stopPolling() {
        if (this.pollTimer === null) return;
        clearTimeout(this.pollTimer);
        this.pollTimer = null;
    }

    setOption(event) {
        const detail = event?.detail ?? {};
        const option = detail.option ?? detail;
        const replace = detail.replace === true;

        this.chart?.setOption(option, replace);
    }

    async loadFromUrl() {
        if (this.inflight) return;
        this.inflight = true;
        try {
            const response = await fetch(this.urlValue);
            const option = await response.json();
            this.chart?.setOption(option);
        } finally {
            this.inflight = false;
        }
    }

    /** Override in subclass to provide defaults that merge with the user option. */
    defaultOption() {
        return {};
    }

    /** Override in subclass to attach event listeners after init. */
    afterInit() {}
}
