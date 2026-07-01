# Charts

Three real-world patterns for the `<x-hwc::chart>` component plus the `chart` Stimulus
controller, ordered from simplest to most extensible. All examples use ECharts 6.x — install
via `npm install echarts@^6.1.0` or let `php artisan hotwire:check` flag it for you.

## Where the data lives in an ECharts option

The most common conceptual confusion when wrapping ECharts is "where do the data live?" The
answer: **everywhere in the option object — there is no separate `data` field.** Depending on
the chart, the data can sit in:

- `xAxis.data` / `yAxis.data` (categorical axes)
- `series[N].data` (per-series data arrays)
- `dataset.source` (centralized data, mapped by series via `encode`)

This is why the URL pattern below fetches the **full option**, not just the data — the server
composes config + data together because ECharts treats them as a single object.

## Pattern 1 — Inline option (the 80% case)

The simplest path: build the option in PHP, pass it to the component, render. Best for static or
moderately sized charts (under ~50KB encoded JSON).

```blade
{{-- resources/views/dashboard/sales.blade.php --}}
@php
    $months = $sales->pluck('month')->all();
    $totals = $sales->pluck('total')->all();
@endphp

<x-hwc::chart :option="[
    'title'   => ['text' => 'Sales by month', 'left' => 'center'],
    'tooltip' => ['trigger' => 'axis'],
    'xAxis'   => ['type' => 'category', 'data' => $months],
    'yAxis'   => ['type' => 'value'],
    'series'  => [[
        'type' => 'bar',
        'data' => $totals,
    ]],
]" height="320px" class="rounded border" />
```

No controller wiring, no fetch — Blade emits a `<div>` with the option embedded as JSON, the
controller initializes ECharts on connect.

## Pattern 2 — URL-fetched option (heavy data, dynamic content)

When the dataset is large, comes from heavy aggregation, or benefits from independent HTTP cache,
point `url` at a dedicated endpoint. The component renders an empty container; the controller
fetches and applies on connect.

```blade
<x-hwc::chart url="{{ route('charts.sales', ['q' => $quarter]) }}" height="320px" />
```

```php
// routes/web.php
Route::get('/charts/sales', App\Http\Controllers\Charts\SalesChartController::class)
    ->name('charts.sales');

// app/Http/Controllers/Charts/SalesChartController.php
class SalesChartController extends Controller
{
    public function __invoke(Request $request)
    {
        $months = Order::groupedByMonth()->forQuarter($request->integer('q', 1))->get();

        return response()->json([
            'title'   => ['text' => 'Sales Q'.$request->integer('q', 1)],
            'tooltip' => ['trigger' => 'axis'],
            'xAxis'   => ['type' => 'category', 'data' => $months->pluck('label')],
            'yAxis'   => ['type' => 'value'],
            'series'  => [['type' => 'bar', 'data' => $months->pluck('total')]],
        ])->setMaxAge(300);   // HTTP cache for 5 minutes
    }
}
```

The endpoint returns the same shape the inline `option` would carry — full ECharts option, not
just data. The benefit over inline is:

- HTML stays light; chart endpoint can be HTTP-cached and gzipped independently
- Query parameters drive different cuts of the data (`?q=2`, `?region=br`)
- The data payload doesn't ship on first page render

## Pattern 3 — Subclass extension (custom defaults, event handlers, extra ECharts modules)

When several charts in an app share a baseline (color palette, animation, tooltip behavior) or
need ECharts modules outside the base bundle (scatter, gauge, map, treemap, calendar, SVG
renderer, …), create a subclass. The base controller exposes two hooks: `defaultOption()` and
`afterInit()`.

```js
// resources/js/controllers/branded_chart_controller.js
import ChartController from "@hotwire/chart_controller.js";
import * as echarts from "echarts/core";
import { GaugeChart, ScatterChart } from "echarts/charts";

echarts.use([GaugeChart, ScatterChart]);

export default class extends ChartController {
    defaultOption() {
        return {
            color: ["#5470c6", "#91cc75", "#fac858", "#ee6666"],
            tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
            grid: { left: 48, right: 24, top: 48, bottom: 32, containLabel: true },
            animationDuration: 600,
        };
    }

    afterInit() {
        this.chart.on("click", "series", (params) => {
            this.dispatch("data-click", { detail: params });
        });
    }
}
```

```blade
<x-hwc::chart controller="branded-chart" :option="$option" />
```

The defaults apply via a first `chart.setOption(defaults)` call; the user option then merges on
top using ECharts' built-in setOption semantics. `afterInit` runs once after both are applied.

Subclasses are lazy-loaded by `@emaia/stimulus-lazy-loader` — the extra ECharts modules they
register only ship to clients that actually render them.

## Advanced — drill-down with smooth transitions

When a click on one chart should navigate to a more detailed view in the same container, combine
the subclass pattern with ECharts' `setOption` + universal transitions. The base controller
doesn't include the `UniversalTransition` feature module — register it from the subclass when
you need it.

```js
// resources/js/controllers/sales_drill_chart_controller.js
import ChartController from "@hotwire/chart_controller.js";
import * as echarts from "echarts/core";
import { LineChart } from "echarts/charts";
import { UniversalTransition } from "echarts/features";

echarts.use([LineChart, UniversalTransition]);

export default class extends ChartController {
    static values = {
        ...ChartController.values,
        detailUrl: { type: String, default: "" },
    };

    history = [];

    afterInit() {
        this.chart.on("click", "series", (params) => this.drillDown(params));
    }

    async drillDown(params) {
        if (!this.detailUrlValue) return;

        this.history.push(this.chart.getOption());
        this.chart.showLoading();

        try {
            const url = `${this.detailUrlValue}?month=${encodeURIComponent(params.name)}`;
            const detail = await fetch(url).then((r) => r.json());

            this.chart.hideLoading();
            this.chart.setOption(detail, true);   // notMerge=true + universalTransition
            this.dispatch("drill-down", { detail: { name: params.name } });
        } catch (err) {
            this.chart.hideLoading();
            console.error("Drill-down failed", err);
        }
    }

    back() {
        if (this.history.length === 0) return;
        this.chart.setOption(this.history.pop(), true);
    }
}
```

```blade
<div>
    <x-hwc::chart
        controller="sales-drill-chart"
        url="/api/charts/sales"
        :data-sales-drill-chart-detail-url-value="route('charts.sales.detail')"
        height="400px" />

    <button data-action="click->sales-drill-chart#back" class="mt-2">← Back</button>
</div>
```

Both endpoints share the same `series.id` and set `universalTransition: true` so ECharts morphs
the bars into the line when the new option arrives:

```php
// initial (bars)
'series' => [[
    'id' => 'sales',
    'type' => 'bar',
    'data' => $months->pluck('total'),
    'universalTransition' => true,
]],

// detail (line)
'series' => [[
    'id' => 'sales',                       // same id → morph
    'type' => 'line',
    'data' => $days->pluck('total'),
    'smooth' => true,
    'universalTransition' => true,
]],
```

The user sees the bars animate into the line over ~1s, with the axis labels fading between
month names and day numbers. Click "Back" to reverse — `setOption` of the saved previous option
runs the morph in reverse.

## Other patterns to combine

- **Cross-controller updates via outlets** — let another controller drive `setOption` on the
  chart. See the [controller doc](../controllers/chart.md#updating-from-another-controller--outlets).
- **Live updates via Turbo Stream** — emit a `replace` stream targeting the chart wrapper; the
  controller disposes and re-initializes on disconnect/connect.
- **Multiple charts on one page** — every `<x-hwc::chart>` instance is independent; share
  configuration via a subclass `defaultOption()`.

## See also

- [Chart component](../components/chart.md) — prop reference, sizing, controller swap
- [Chart controller](../controllers/chart.md) — values, actions, hooks, security note
