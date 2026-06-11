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
    };

    chart = null;
    observer = null;

    connect() {
        this.chart = echarts.init(this.element, this.themeValue || null);

        const defaults = this.defaultOption();
        if (Object.keys(defaults).length > 0) {
            this.chart.setOption(defaults);
        }

        if (Object.keys(this.optionValue).length > 0) {
            this.chart.setOption(this.optionValue);
        } else if (this.urlValue !== "") {
            this.loadFromUrl();
        }

        this.afterInit();

        this.observer = new ResizeObserver(() => this.chart?.resize());
        this.observer.observe(this.element);

        this.dispatch("ready");
    }

    disconnect() {
        this.observer?.disconnect();
        this.observer = null;
        this.chart?.dispose();
        this.chart = null;
    }

    setOption(event) {
        const detail = event?.detail ?? {};
        const option = detail.option ?? detail;
        const replace = detail.replace === true;

        this.chart?.setOption(option, replace);
    }

    async loadFromUrl() {
        const response = await fetch(this.urlValue);
        const option = await response.json();
        this.chart?.setOption(option);
    }

    /** Override in subclass to provide defaults that merge with the user option. */
    defaultOption() {
        return {};
    }

    /** Override in subclass to attach event listeners after init. */
    afterInit() {}
}
