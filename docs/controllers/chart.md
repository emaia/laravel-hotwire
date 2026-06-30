# Chart

Apache ECharts wrapper. Initializes a chart on the controller element, applies a server-rendered
or URL-fetched `option`, auto-resizes via `ResizeObserver`, and exposes a `setOption` action plus
hooks for subclasses to provide defaults or attach event listeners.

**Identifier:** `chart`  
**Install:** `php artisan hotwire:controllers chart`  
**npm dep:** `echarts ^6.1.0`

## Requirements

- ECharts ^6.1.0 (`hotwire:check` reports it as required when the controller is in use)

## Values

| Value    | Type    | Default | Description                                                                                  |
|----------|---------|---------|----------------------------------------------------------------------------------------------|
| `option` | Object  | `{}`    | Inline ECharts option, embedded server-side via `@json`                                      |
| `url`    | String  | `""`    | Endpoint that returns a complete ECharts option as JSON. Fetched on connect when `option` is empty |
| `theme`  | String  | `""`    | Registered ECharts theme name (`'dark'`, `'v5'`, or any custom theme registered with `echarts.registerTheme`) |
| `poll`   | Number  | `0`     | Polling interval in milliseconds. When set with `url`, the controller re-fetches the URL on every cycle and applies the response via `setOption` (partial merge — no flicker, keeps zoom/selection). `0` (default) disables polling |

## Actions

| Action      | Description                                                                                  |
|-------------|----------------------------------------------------------------------------------------------|
| `setOption` | Apply a partial or full option. Accepts `event.detail` as the option directly, or `event.detail = { option, replace }` for `notMerge` control |
| `reload`    | Re-fetch the configured `url` and apply the response via `setOption` (animated merge, no canvas teardown). No-op when `url` is not set. Wire to any event your app dispatches (e.g. `kanban:updated@window`) |

## Events

| Event         | Detail | Description                       |
|---------------|--------|-----------------------------------|
| `chart:ready` | `{}`   | Fires after the chart initializes |

## Basic usage (raw, without the Blade component)

```html

<div data-controller="chart"
     data-chart-option-value='{"title":{"text":"Sales"},"xAxis":{"type":"category","data":["Jan","Feb","Mar"]},"yAxis":{"type":"value"},"series":[{"type":"bar","data":[120,200,150]}]}'
     style="width: 100%; height: 320px"></div>
```

The `option` value is the standard ECharts configuration object. Anything the ECharts API accepts
goes here.

## Loading from a URL

When the option is large, dynamic, or needs caching, point the controller at an endpoint that
returns the full option as JSON:

```html

<div data-controller="chart"
     data-chart-url-value="/api/charts/sales"
     style="width: 100%; height: 320px"></div>
```

```php
// Laravel controller returning the full ECharts option
class SalesChartController extends Controller
{
    public function __invoke(Request $request)
    {
        return [
            'title' => ['text' => 'Sales Q'.$request->integer('q', 1)],
            'tooltip' => ['trigger' => 'axis'],
            'xAxis' => ['type' => 'category', 'data' => Order::monthsForQuarter($request->integer('q', 1))],
            'yAxis' => ['type' => 'value'],
            'series' => [['type' => 'bar', 'data' => Order::totalsForQuarter($request->integer('q', 1))]],
        ];
    }
}
```

When both `option` and `url` are set, **inline `option` wins** — `url` is only fetched when
`option` is empty.

## Live polling

When `url` is set and `poll` is a positive number of milliseconds, the controller re-fetches the URL
on each cycle and applies the response via `setOption`. Because `setOption` does a partial merge by
default, the chart updates without flicker and keeps user state (zoom, brush, hovered series):

```html
<div
    data-controller="chart"
    data-chart-url-value="/api/charts/sales"
    data-chart-poll-value="30000"
    style="width: 100%; height: 400px"
></div>
```

The next cycle is only scheduled after the current fetch settles, so a slow endpoint never queues
overlapping requests. `disconnect()` clears any pending timer.

If the endpoint fails (404, 500, network error), the polling loop continues and the failure is
reported via `console.error`. There is no built-in retry-with-backoff. For unrecoverable errors,
remove the `poll` value (Stimulus re-renders / server update) or subclass to add custom error
handling.

## Event-driven updates — the `reload` action

When polling isn't the right model — you want the chart to refresh exactly when *something else*
on the page changes (a card moved to another column, an incident closed, a deal won) — wire the
`reload` action to any custom event your app dispatches. The chart re-fetches its `url` and
applies the response via `setOption`, which ECharts merges into the running instance with
animation; the canvas is never disposed.

```html
<div
    data-controller="chart"
    data-chart-url-value="/api/charts/totals"
    data-action="kanban:updated@window->chart#reload"
    style="width: 100%; height: 320px"
></div>
```

In this example, anywhere in your app that dispatches `new CustomEvent("kanban:updated")` on
`window` triggers the reload. Multiple charts can listen to the same event for a coordinated
dashboard refresh; or you can use distinct event names to scope updates to specific groups.

```js
// Anywhere in your app — e.g. another controller after a successful Turbo Stream:
window.dispatchEvent(new CustomEvent("kanban:updated"));
```

For element-scoped (not global) dispatch, drop the `@window` modifier and target a specific chart
by id; the event bubbles up to the controller from anywhere inside that subtree.

## Turbo morph resilience

When Turbo morph preserves the controller's host element but replaces its inner content (`<meta
name="turbo-refresh-method" content="morph">`, `data-turbo-permanent` ancestors, some cache
restore scenarios), Stimulus doesn't emit `disconnect`/`connect` and the ECharts instance ends up
pointing at an orphaned `<canvas>`. The controller listens to `turbo:morph-element` on its own
element and, if the canvas is gone, recreates the chart with the current values. No manual wiring
is needed.

## Theme

```html

<div data-controller="chart"
     data-chart-theme-value="dark"
     data-chart-option-value="..."
     style="..."></div>
```

The theme name is passed to `echarts.init(element, theme)`. Register custom themes via
`echarts.registerTheme(...)` in your entry script before any chart connects.

### ECharts 6 visual defaults

ECharts 6 ships a refreshed default theme. To restore the v5 appearance, import the legacy
theme in your entry and pass `theme="v5"`:

```js
import "echarts/theme/v5";
```

```html
<div data-controller="chart" data-chart-theme-value="v5" ...></div>
```

The v6 release also enables label overflow / overlap prevention by default — labels may shift
slightly compared to v5. Tune via `grid.outerBoundsMode` and `xAxis.nameMoveOverlap` in the
option when needed.

## Default bundle and tree-shaking

The shipped base controller registers a focused set of ECharts modules to keep the bundle
manageable (~120KB):

- Charts: `BarChart`, `LineChart`, `PieChart`
- Components: `GridComponent`, `TooltipComponent`, `LegendComponent`, `TitleComponent`, `DatasetComponent`
- Renderer: `CanvasRenderer`

This covers most common dashboards out of the box. To use other chart types (scatter, gauge,
map, heatmap, calendar, etc.) or the SVG renderer, **register them from a subclass** — see
below.

## Programmatic updates — the `setOption` action

```html

<div data-controller="chart filter-bar"
     data-action="filter-bar:change->chart#setOption"
     data-chart-option-value="..."></div>
```

```js
// resources/js/controllers/filter_bar_controller.js
this.dispatch("change", { detail: { series: [{ data: newData }] } });
```

The detail can be:

- **An option object directly** (most common): `event.detail = { series: [...] }`
- **An envelope with `option` and `replace`**: `event.detail = { option: {...}, replace: true }` — when `replace: true` is set, ECharts uses `notMerge` semantics and the new option replaces the previous one entirely instead of merging incrementally.

## Updating from another controller — outlets

Cleaner than custom events for tight coupling. The other controller declares the chart as an
outlet and calls `setOption` directly:

```html

<div id="sales-chart" data-controller="chart" data-chart-option-value="..."></div>

<div data-controller="filter-bar"
     data-filter-bar-chart-outlet="#sales-chart">
    <select data-action="change->filter-bar#refresh">...</select>
</div>
```

```js
// filter_bar_controller.js
static outlets = ["chart"];

refresh(event) {
    this.chartOutlet.setOption({ detail: { series: [{ data: computeData(event.target.value) }] } });
}
```

## Extending via subclass — the `defaultOption` and `afterInit` hooks

The base controller exposes two hooks for subclasses, matching the carousel extensibility pattern.
Subclasses are lazy-loaded by `@emaia/stimulus-dynamic-loader`, so the extra ECharts modules they
register only ship to clients that actually use them.

```js
// resources/js/controllers/sales_chart_controller.js
import ChartController from "@hotwire/chart_controller.js";
import * as echarts from "echarts/core";
import { GaugeChart, ScatterChart } from "echarts/charts";

echarts.use([GaugeChart, ScatterChart]);

export default class extends ChartController {
    defaultOption() {
        return {
            color: ["#5470c6", "#91cc75", "#fac858"],
            tooltip: { trigger: "axis" },
            animation: false,
        };
    }

    afterInit() {
        this.chart.on("click", (params) => {
            this.dispatch("point-click", { detail: params });
        });
    }
}
```

Use it via the Stimulus identifier matching the file name:

```html
<div data-controller="sales-chart" data-chart-option-value="..."></div>
```

### `defaultOption()`

Return an option object that should apply before the user's option. The controller calls
`chart.setOption(defaults)` first, then `chart.setOption(userOption)` — ECharts' built-in merge
semantics handle the combination (`series` merges by index/id, tooltip/legend/etc. deep-merge).

This is the right place for:

- Brand color palettes
- Animation defaults
- Tooltip behavior conventions
- Anything you want consistent across every chart of this subclass

### `afterInit()`

Called once after the chart initializes and the option is applied. Attach event listeners
(`this.chart.on(...)`) and any other side effects here.

## Lifecycle

- `connect()` — initializes the chart, applies defaults + option (or fetches URL), wires up the
  `ResizeObserver`, dispatches `chart:ready`
- `disconnect()` — disposes the chart instance and disconnects the observer
- Turbo Drive cache restore re-runs `connect()` with a fresh chart instance

## Security note

ECharts accepts `formatter` callbacks in several places (tooltip, axis labels, etc.) that can
return HTML strings. When user-controlled data flows through a formatter without escaping, that
becomes an XSS vector. Either build the formatter output as plain text, or use
`echarts.format.encodeHTML()` on any interpolated values:

```js
formatter: (params) => `<b>${echarts.format.encodeHTML(params.name)}</b>: ${params.value}`
```

## Limitations

- **No SSR.** The chart renders client-side once the controller connects. For server-rendered
  chart images (email, PDF export), use a separate render path (e.g., ECharts SSR mode driven
  from a queued job).
- **Large inline options.** When the JSON encoded option exceeds ~500KB, prefer the `url` value
  — the HTML stays light and the chart endpoint can be cached / gzipped independently. See the
  recipe for the cutoff guidance.
