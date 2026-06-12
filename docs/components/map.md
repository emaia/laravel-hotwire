# `<x-hwc::map>`

Renders a sized `<div>` with the `map` Stimulus controller mounted and the center/zoom/markers/url values pre-filled. Wraps [Leaflet](https://leafletjs.com/) — see the [controller docs](../controllers/map.md) for the runtime side.

## Quick example

```blade
{{-- Pin at an address --}}
<x-hwc::map
    :center="[-23.5505, -46.6333]"
    :zoom="12"
    :markers="[[-23.5505, -46.6333, 'São Paulo']]"
    height="400px"
/>

{{-- GeoJSON via endpoint --}}
<x-hwc::map url="/api/locations" height="400px" />

{{-- Subclass swap (custom tiles, handlers, clusters) --}}
<x-hwc::map controller="store-locator" url="/api/stores" />
```

## Props

| Prop                | Type            | Default   | Description                                                                                              |
|---------------------|-----------------|-----------|----------------------------------------------------------------------------------------------------------|
| `center`            | `array\|null`   | `null`    | `[lat, lng]` initial view. Required if `markers` and `url` are both null                                 |
| `zoom`              | `int`           | `13`      | Initial zoom level                                                                                       |
| `markers`           | `array\|null`   | `null`    | Inline markers as `[[lat, lng, label?], ...]`; serialized to JSON and embedded as a data attribute       |
| `url`               | `string\|null`  | `null`    | Endpoint that returns a GeoJSON `FeatureCollection`; fetched on connect                                  |
| `scroll-wheel-zoom` | `bool`          | `true`    | Whether mouse-wheel scrolling controls zoom. Disable with `:scroll-wheel-zoom="false"` for long pages    |
| `height`            | `string`        | `'400px'` | CSS height applied inline                                                                                |
| `width`             | `string\|null`  | `null`    | CSS width applied inline; defaults to `100%` when omitted                                                |
| `class`             | `string`        | `''`      | Merged on the wrapper element                                                                            |
| `controller`        | `string`        | `'map'`   | Stimulus identifier — swap for a subclass (e.g. `controller="store-locator"`)                            |

**At least one** of `center`, `markers`, or `url` is required. The component throws `InvalidArgumentException` otherwise.

## Inline markers

Marker tuples accept an optional third element used as the popup HTML when present:

```blade
<x-hwc::map :markers="[
    [-23.5505, -46.6333, 'São Paulo'],
    [-22.9068, -43.1729, 'Rio de Janeiro'],
    [-30.0346, -51.2177, 'Porto Alegre'],
]" />
```

Markers without a label render as plain pins with no popup. Note there's no `:center` or `:zoom` here — the component **auto-fits** to show all markers (see "Auto-fit" below).

## GeoJSON URL

For dynamic or larger collections, point `url` at an endpoint that returns a GeoJSON `FeatureCollection`. The controller fetches on connect and renders the response via `L.geoJSON`:

```blade
<x-hwc::map url="/api/locations" height="320px" />
```

```php
// Laravel route
Route::get('/api/locations', function () {
    return [
        'type' => 'FeatureCollection',
        'features' => Location::all()->map(fn ($loc) => [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [$loc->lng, $loc->lat]],
            'properties' => ['name' => $loc->name],
        ]),
    ];
});
```

The endpoint returns the **complete FeatureCollection**, not just data — server-side filtering keeps the payload small.

## Auto-fit

When you pass `markers` or `url` but **no `center`**, the component automatically frames the map to show everything you provided — Leaflet computes the bounding box of the markers (plus any GeoJSON features once they load) and `fitBounds` the map to it. Padding (20px) and a `maxZoom: 15` cap keep a single-marker case from "zooming until you see the asphalt".

```blade
{{-- Auto-fit: shows all three pins, no manual zoom math --}}
<x-hwc::map :markers="[
    [-23.5505, -46.6333, 'São Paulo'],
    [-22.9068, -43.1729, 'Rio de Janeiro'],
    [-30.0346, -51.2177, 'Porto Alegre'],
]" />
```

When you pass `:center`, the component respects it and skips the auto-fit — you said what you wanted to see. If you want both (a center hint and the auto-fit), override the decision with `:fit="true"`:

```blade
<x-hwc::map :center="[-23.5, -46.6]" :fit="true" :markers="$pins" />
```

To opt out of the auto-fit even when no `:center` is given (rare; usually means you want the default `[0, 0]` center), pass `:fit="false"`.

## Disabling scroll-wheel zoom

By default the map zooms when the user scrolls with the mouse wheel — convenient inside a dashboard, but it traps page scroll on long content. Pass `:scroll-wheel-zoom="false"` for pages where the map is one section among many:

```blade
<x-hwc::map :center="[0, 0]" :scroll-wheel-zoom="false" height="320px" />
```

## Sizing

```blade
<x-hwc::map :center="[0, 0]" height="500px" width="800px" />
```

Both props become inline `style` declarations. `height` is required (Leaflet won't render in a zero-height container); `width` defaults to `100%`.

## Controller swap — subclass extensibility

When you need a custom tile provider, marker icon, plugin (e.g. cluster), or post-init wiring, write a subclass:

```js
// resources/js/controllers/store_locator_controller.js
import MapController from "./map_controller";

export default class extends MapController {
    tileLayerUrl() {
        return "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png";
    }

    tileLayerOptions() {
        return { attribution: "© CartoDB", maxZoom: 19 };
    }

    afterInit() {
        this.map.on("click", (e) => {
            this.dispatch("pin-drop", { detail: { latlng: e.latlng } });
        });
    }
}
```

Then point the component at it via `controller`:

```blade
<x-hwc::map controller="store-locator" url="/api/stores" height="500px" />
```

## OpenStreetMap attribution

OSM's tile usage policy requires the attribution "© OpenStreetMap contributors" to be visible. The component does that automatically — you don't need to add anything. If you swap to a different tile provider via `tileLayerOptions()`, the override replaces the OSM attribution; make sure the new value satisfies the new provider's terms.

## Combining with other behavior

Stack additional controllers on the wrapper via `data-controller`:

```blade
<x-hwc::map
    url="/api/locations"
    height="400px"
    data-controller="lazy-load"
    data-action="resize@window->lazy-load#check"
/>
```

The package merges your `data-controller` with the `map` identifier transparently.

## See also

- [Controller documentation](../controllers/map.md)
- [Leaflet API reference](https://leafletjs.com/reference.html)
- `docs/recipes/maps.md` — patterns for inline markers, GeoJSON endpoints, and custom tile providers
