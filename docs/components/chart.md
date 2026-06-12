# Chart

Server-rendered Blade wrapper around the [`chart`](../controllers/chart.md) Stimulus controller.
Renders a sized `<div>` with the controller mounted and the option/url/theme values pre-filled —
the controller then initializes Apache ECharts on it.

## Quick example

```blade
<x-hwc::chart :option="[
    'title' => ['text' => 'Sales'],
    'xAxis' => ['type' => 'category', 'data' => ['Jan', 'Feb', 'Mar']],
    'yAxis' => ['type' => 'value'],
    'series' => [['type' => 'bar', 'data' => [120, 200, 150]]],
]" height="320px" />
```

## Props

| Prop         | Type             | Default     | Description                                                                                              |
|--------------|------------------|-------------|----------------------------------------------------------------------------------------------------------|
| `option`     | `array\|null`    | `null`      | Inline ECharts option, serialized to JSON via `json_encode` and embedded as a data attribute             |
| `url`        | `string\|null`   | `null`      | Endpoint that returns the complete ECharts option as JSON; fetched on connect when no inline option      |
| `theme`      | `string\|null`   | `null`      | Registered ECharts theme name (`'dark'`, `'v5'`, custom themes registered via `echarts.registerTheme`)   |
| `poll`       | `int`            | `0`         | Polling interval in milliseconds. Combined with `url`, re-fetches the endpoint on every cycle. `0` disables polling |
| `height`     | `string`         | `'400px'`   | CSS height applied inline                                                                                |
| `width`      | `string\|null`   | `null`      | CSS width applied inline; defaults to `100%` when omitted                                                |
| `class`      | `string`         | `''`        | Merged on the wrapper element                                                                            |
| `controller` | `string`         | `'chart'`   | Stimulus identifier — swap for a subclass (e.g. `controller="sales-chart"` for drill-down behavior)      |

`option` and `url` are **mutually optional but at least one is required.** The component throws
`InvalidArgumentException` when neither is provided. When both are present, the inline `option`
wins — `url` is only fetched when the inline option is absent.

Any other HTML attribute (`id`, `data-*`, `aria-*`, etc.) passes through to the wrapper.

## URL-fetched option

For large datasets, dynamic data, or cacheable endpoints, point `url` at a route that returns the
full ECharts option as JSON:

```blade
<x-hwc::chart url="/api/charts/sales" height="320px" />
```

```php
// app/Http/Controllers/Charts/SalesChartController.php
class SalesChartController extends Controller
{
    public function __invoke(Request $request)
    {
        $months = Order::groupedByMonth()->forQuarter($request->integer('q', 1))->get();

        return [
            'title'   => ['text' => 'Sales Q'.$request->integer('q', 1)],
            'tooltip' => ['trigger' => 'axis'],
            'xAxis'   => ['type' => 'category', 'data' => $months->pluck('label')],
            'yAxis'   => ['type' => 'value'],
            'series'  => [['type' => 'bar', 'data' => $months->pluck('total')]],
        ];
    }
}
```

The endpoint returns the **complete option**, not just the data. See the recipe for the
mental-model explanation of "where the data lives in an option".

## Live polling

Combine `url` with `:poll` to keep the chart fresh against a moving data source. The interval is in
milliseconds; the controller re-fetches the URL on every cycle and applies the response via partial
`setOption` merge (no flicker, user interactions like zoom/brush survive):

```blade
<x-hwc::chart url="/api/charts/sales" :poll="30_000" height="320px" />
```

The next cycle is only scheduled after the current fetch settles, so a slow endpoint can never queue
overlapping requests. Set `:poll="0"` (or omit) to disable polling. Endpoint errors (404, 500,
network) don't stop the loop — failures are logged to `console.error`. For unrecoverable failures,
re-render the component without `:poll` or subclass to add custom error handling.

## Theme

```blade
<x-hwc::chart :option="$option" theme="dark" />
```

Theme names are passed to `echarts.init(element, theme)`. Register custom themes
(`echarts.registerTheme('corporate', {...})`) in your app's entry script before any chart connects.

ECharts 6 ships a refreshed default theme — for the v5 look, import `echarts/theme/v5` in your
entry and use `theme="v5"`.

## Sizing

```blade
<x-hwc::chart :option="$option" height="240px" width="640px" />
```

The component renders inline `style="width: ...; height: ..."` so the chart container always has
explicit dimensions — ECharts requires a sized container to render.

## Controller swap — subclass extensibility

Following the carousel pattern, override `controller` to mount a Stimulus subclass instead of the
base `chart`. All data attribute prefixes follow the new identifier automatically:

```blade
<x-hwc::chart controller="sales-chart" :option="$option" />
```

Renders:

```html
<div data-controller="sales-chart"
     data-sales-chart-option-value="..."
     style="width: 100%; height: 400px"></div>
```

The subclass JS file lives at `resources/js/controllers/sales_chart_controller.js` and extends
the base — see the [controller doc](../controllers/chart.md#extending-via-subclass--the-defaultoption-and-afterinit-hooks).

## Inline option size — what to watch for

| JSON size  | Recommended path                                                                          |
|------------|-------------------------------------------------------------------------------------------|
| `< 50KB`   | `option` inline — no trade-off                                                            |
| `50-500KB` | `option` inline still works; consider `url` if the chart is below the fold                |
| `> 500KB`  | `url` strongly recommended — frees the HTML, allows HTTP cache + gzip on the data payload |

In the local environment, the component logs a warning to the Laravel logger when the encoded
option exceeds 500KB, with a pointer to the `url` prop. Production stays silent.

## Combining with other behavior

`data-controller` passes through — your own controller mounts alongside the chart:

```blade
<x-hwc::chart :option="$option" data-controller="analytics-track" />
```

Renders `data-controller="chart analytics-track"`.

## See also

- [Chart controller](../controllers/chart.md) — values, actions, subclass hooks
- [Charts recipe](../recipes/charts.md) — basic, URL-fetched, and subclass patterns end-to-end
