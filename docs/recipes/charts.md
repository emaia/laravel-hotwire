# Charts

Start with `<hw:chart>` for sizing, option serialization, and controller wiring. See the
[component reference](../components/chart.md) for all props and the
[controller reference](../controllers/chart.md) for values, actions, and subclass hooks. The
examples below focus on complete server-data, cacheable-endpoint, and drill-down tasks.

## Where the data lives in an ECharts option

The most common conceptual confusion when wrapping ECharts is "where do the data live?" The
answer: **everywhere in the option object — there is no separate `data` field.** Depending on
the chart, the data can sit in:

- `xAxis.data` / `yAxis.data` (categorical axes)
- `series[N].data` (per-series data arrays)
- `dataset.source` (centralized data, mapped by series via `encode`)

This is why the URL pattern below fetches the **full option**, not just the data — the server
composes config + data together because ECharts treats them as a single object.

## Render a sales chart from server data

The simplest path: build the option in PHP, pass it to the component, render. Best for static or
moderately sized charts (under ~50KB encoded JSON).

```blade
{{-- resources/views/dashboard/sales.blade.php --}}
@php
    $months = $sales->pluck('month')->all();
    $totals = $sales->pluck('total')->all();
@endphp

<hw:chart
    :option="[
    'title'   => ['text' => 'Sales by month', 'left' => 'center'],
    'tooltip' => ['trigger' => 'axis'],
    'xAxis'   => ['type' => 'category', 'data' => $months],
    'yAxis'   => ['type' => 'value'],
    'series'  => [[
        'type' => 'bar',
        'data' => $totals,
    ]],
]"
    height="320px"
    class="border-border rounded-lg border"
/>
```

No raw controller wiring is needed. Use the inline option path for static or moderately sized
charts; the [component reference](../components/chart.md#inline-option-size--what-to-watch-for)
covers the size cutoff.

## Serve a cacheable chart option

When the dataset is large, comes from heavy aggregation, or benefits from independent HTTP cache,
point `url` at a dedicated endpoint. The component renders an empty container; the controller
fetches and applies on connect.

```blade
<hw:chart url="{{ route('charts.sales', ['q' => $quarter]) }}" height="320px" />
```

```php
// routes/web.php
Route::get('/charts/sales', App\Http\Controllers\Charts\SalesChartController::class)
    ->middleware('auth')
    ->name('charts.sales');

// app/Http/Controllers/Charts/SalesChartController.php
class SalesChartController extends Controller
{
    public function __invoke(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('viewSalesReports');

        $months = Order::groupedByMonth()->forQuarter($request->integer('q', 1))->get();

        return response()->json([
            'title'   => ['text' => 'Sales Q'.$request->integer('q', 1)],
            'tooltip' => ['trigger' => 'axis'],
            'xAxis'   => ['type' => 'category', 'data' => $months->pluck('label')],
            'yAxis'   => ['type' => 'value'],
            'series'  => [[
                'id' => 'sales',
                'type' => 'bar',
                'data' => $months->pluck('total'),
                'universalTransition' => true,
            ]],
        ])->setPrivate()->setMaxAge(300);
    }
}
```

The authenticated endpoint authorizes the private report and returns the same shape the inline
`option` would carry — full ECharts option, not just data. The benefit over inline is:

- HTML stays light; chart endpoint can be HTTP-cached and gzipped independently
- Query parameters drive different cuts of the data (`?q=2`, `?region=br`)
- The data payload doesn't ship on first page render

## Add a chart type and handle point clicks

The base bundle includes bar, line, and pie charts. Register other ECharts modules in an app-owned
subclass and attach app-specific events in `afterInit()`:

```js
// resources/js/controllers/quality_chart_controller.js
import ChartController from "@hotwire/chart_controller.js";
import * as echarts from "echarts/core";
import { ScatterChart } from "echarts/charts";

echarts.use([ScatterChart]);

export default class extends ChartController {
    defaultOption() {
        return {
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
<hw:chart controller="quality-chart" :option="$qualityOption" />
```

The defaults apply via a first `chart.setOption(defaults)` call; the user option then merges on
top using ECharts' built-in setOption semantics. `afterInit` runs once after both are applied.

Subclasses are lazy-loaded by `@emaia/stimulus-lazy-loader` — the extra ECharts modules they
register only ship to clients that actually render them.

## Drill from monthly bars into daily totals

When a click on one chart should navigate to a more detailed view in the same container, combine
the subclass pattern with ECharts' `setOption` + universal transitions. The base controller
doesn't include the `UniversalTransition` feature module — register it from the subclass when
you need it.

Add a named detail endpoint alongside the `charts.sales` route defined above:

```php
// routes/web.php
Route::get('/charts/sales/detail', App\Http\Controllers\Charts\SalesChartDetailController::class)
    ->middleware('auth')
    ->name('charts.sales.detail');

// app/Http/Controllers/Charts/SalesChartDetailController.php
class SalesChartDetailController extends Controller
{
    public function __invoke(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('viewSalesReports');

        $month = $request->string('month')->toString();
        $days = Order::dailyTotalsForMonth($month)->get();

        return response()->json([
            'title'   => ['text' => "Sales {$month}"],
            'tooltip' => ['trigger' => 'axis'],
            'xAxis'   => ['type' => 'category', 'data' => $days->pluck('day')],
            'yAxis'   => ['type' => 'value'],
            'series'  => [[
                'id' => 'sales',
                'type' => 'line',
                'data' => $days->pluck('total'),
                'smooth' => true,
                'universalTransition' => true,
            ]],
        ]);
    }
}
```

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

    activeRequest = null;
    requestVersion = 0;

    disconnect() {
        this.requestVersion += 1;
        this.activeRequest?.abort();
        this.activeRequest = null;
        super.disconnect();
    }

    afterInit() {
        this.chart.on("click", "series", (params) => this.drillDown(params));
    }

    async drillDown(params) {
        if (!this.detailUrlValue || !this.chart) return;

        this.activeRequest?.abort();
        const request = new AbortController();
        const version = ++this.requestVersion;
        this.activeRequest = request;
        this.chart.showLoading();

        try {
            const url = `${this.detailUrlValue}?month=${encodeURIComponent(params.name)}`;
            const response = await fetch(url, { signal: request.signal });
            if (!response.ok) throw new Error(`Drill-down failed: ${response.status}`);
            const detail = await response.json();

            if (request.signal.aborted || version !== this.requestVersion || !this.chart) return;

            this.chart.setOption(detail, true);
            this.dispatch("drill-down", { detail: { name: params.name } });
        } catch (error) {
            if (error?.name !== "AbortError" && version === this.requestVersion) {
                console.error("Drill-down failed", error);
            }
        } finally {
            if (this.activeRequest === request) {
                this.activeRequest = null;
                this.chart?.hideLoading();
            }
        }
    }
}
```

```blade
<hw:chart
    controller="sales-drill-chart"
    url="{{ route('charts.sales', ['q' => $quarter]) }}"
    :data-sales-drill-chart-detail-url-value="route('charts.sales.detail')"
    height="400px"
/>
```

The initial response supplies month categories and a bar series; the detail response supplies day
categories and a line series. Both use `series.id = "sales"` and enable `universalTransition`, so
ECharts can morph the monthly bars into the daily line without replacing the component. Define the
`viewSalesReports` Gate ability for the users allowed to access this private data. For live polling,
event-driven reloads, outlets, and direct `setOption` calls, use the [controller reference](../controllers/chart.md).

## See also

- [Chart component](../components/chart.md) — props, sizing, polling, and controller swap
- [Chart controller](../controllers/chart.md) — actions, lifecycle, security, and hooks
- [Extending controllers](../extending-controllers.md) — `@hotwire` subclass and explicit fork paths
