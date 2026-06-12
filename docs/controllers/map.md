# Map

Wraps [Leaflet](https://leafletjs.com/) as an interactive map controller. Loads OpenStreetMap tiles by default, supports inline markers and GeoJSON URL fetching, and exposes hooks for subclass customisation (custom tile providers, event handlers, plugins).

**Identifier:** `map`
**Install:** `php artisan hotwire:controllers map`

## Requirements

- `leaflet` (`bun add leaflet`)

> If any component in your views pulls this controller in (via `<x-hwc::map>`), `php artisan hotwire:check --fix` will add `leaflet` to your `package.json` `devDependencies` automatically.

## Values

| Value             | Type    | Default  | Description                                                                                                  |
|-------------------|---------|----------|--------------------------------------------------------------------------------------------------------------|
| `center`          | Array   | `[0, 0]` | `[lat, lng]` initial view                                                                                    |
| `zoom`            | Number  | `13`     | Initial zoom level                                                                                           |
| `markers`         | Array   | `[]`     | Inline markers as `[[lat, lng, label?], ...]`. The optional `label` is bound as an HTML popup                |
| `url`             | String  | `""`     | Endpoint that returns a GeoJSON `FeatureCollection`; fetched on connect and rendered via `L.geoJSON`          |
| `scrollWheelZoom` | Boolean | `true`   | Whether mouse-wheel scrolling controls zoom. Set to `false` to avoid "scroll trap" in long pages              |

## Actions

| Action      | Description                                                                       |
|-------------|-----------------------------------------------------------------------------------|
| `flyTo`     | Calls `map.flyTo(event.detail.center, event.detail.zoom)` for smooth re-centering |
| `fitBounds` | Calls `map.fitBounds(event.detail.bounds)` to frame a collection of points        |
| `reload`    | Re-fetch the configured `url` and add the new GeoJSON layer. No-op when `url` is not set. Wire to any event your app dispatches (e.g. `incident:created@window`) |

## Events

| Event       | Detail | Description                  |
|-------------|--------|------------------------------|
| `map:ready` | —      | Dispatched after `connect()` |

## Basic usage (raw, without the Blade component)

```html
<div
    data-controller="map"
    data-map-center-value="[-23.5505, -46.6333]"
    data-map-zoom-value="12"
    data-map-markers-value='[[-23.5505, -46.6333, "São Paulo"]]'
    style="width: 100%; height: 400px"
></div>
```

The controller mounts a Leaflet map with OpenStreetMap tiles, an `attribution` automatically set to "© OpenStreetMap contributors" (required by the OSM tile usage policy), and a marker bound to a popup.

## GeoJSON URL

For dynamic data, point `url` at an endpoint that returns a GeoJSON `FeatureCollection`:

```html
<div
    data-controller="map"
    data-map-center-value="[0, 0]"
    data-map-url-value="/api/locations"
    style="width: 100%; height: 400px"
></div>
```

The controller fetches on connect, hands the response to `L.geoJSON`, and adds the layer to the map. Network/parse errors are logged to `console.error` and the rest of the map still initialises.

## Event-driven updates — the `reload` action

When you want the map to refresh exactly when something else on the page changes (a new incident is reported, a delivery moves), wire the `reload` action to any custom event your app dispatches. The controller re-fetches its `url` and adds the new GeoJSON layer on top of the existing map — the Leaflet instance keeps running.

```html
<div
    data-controller="map"
    data-map-url-value="/api/incidents"
    data-action="incident:created@window->map#reload"
    style="width: 100%; height: 500px"
></div>
```

```js
// Anywhere in your app:
window.dispatchEvent(new CustomEvent("incident:created"));
```

If you'd rather replace the existing layer instead of stacking new ones, subclass `loadFromUrl` to remove the previous layer before adding the new one.

## Turbo morph resilience

When Turbo morph preserves the host element but replaces its inner content (`<meta name="turbo-refresh-method" content="morph">`, `data-turbo-permanent` ancestors, some cache restore scenarios), Stimulus doesn't emit `disconnect`/`connect` and Leaflet ends up pointing at an orphaned container. The controller listens to `turbo:morph-element` on its own element and, if the Leaflet pane is gone, recreates the map with the current values. No manual wiring is needed.

## Custom tiles — `tileLayerUrl` / `tileLayerOptions` hooks

Subclass to point at a different tile provider (CartoDB, Mapbox, Stadia, your own tile server):

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
}
```

Then mount with the swapped identifier:

```html
<div data-controller="store-locator" data-store-locator-center-value="[0,0]" ...></div>
```

Or via the `controller` prop on `<x-hwc::map>` (see component docs).

## `defaultView` hook

Returns `{ center?, zoom? }` applied **as a fallback** — values from the server/markup always win. Useful for subclasses that want a sensible default when the consumer doesn't pass one:

```js
defaultView() {
    return { center: [-23.5, -46.6], zoom: 5 };  // São Paulo region
}
```

## `afterInit` hook

Runs once after init, with `this.map` already populated. Use to attach event listeners or plugins:

```js
afterInit() {
    this.map.on("click", (e) => {
        this.dispatch("pin-drop", { detail: { latlng: e.latlng } });
    });
}
```

## Default marker icon

Leaflet's default marker icons reference assets by relative path, which Vite/Webpack don't resolve — out of the box, markers render as broken images. The controller fixes this once at module load with two steps:

1. **`delete L.Icon.Default.prototype._getIconUrl`** — Leaflet's internal `_getIconUrl` prepends a runtime-detected `imagePath` (derived from the URL where `leaflet.js` is served) to the icon URL. Under Vite dev, that path is an absolute URL like `http://127.0.0.1:5173/node_modules/leaflet/dist/images/`, which gets concatenated with the absolute URL we provide via `mergeOptions`, producing a duplicated, broken path. Deleting the method makes Leaflet use the URL fields directly.
2. **`L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl })`** — points the default icon at the bundled asset URLs that Vite/Webpack rewrite to hashed asset paths at build time.

Skipping step 1 is a common papercut: a production build accidentally works because `imagePath` derives to an empty/relative string, but Vite dev breaks visibly with the duplicated URL.

If you need a custom icon, set it per-marker in your `afterInit` hook using `L.icon({ iconUrl: ... })`.

## Lifecycle

- `connect()`: initialise the map, add tile layer, render inline markers, fetch GeoJSON URL (async), attach `ResizeObserver` to keep the map sized correctly when the container resizes, dispatch `map:ready`
- `disconnect()`: disconnect the observer and call `map.remove()` to detach Leaflet handlers and DOM

## Limitations

- No marker clustering. For high marker counts (>500), install `leaflet.markercluster` in a subclass: load the plugin in `afterInit` and replace the loop that adds markers with a cluster group.
- No heatmaps. Same pattern with `leaflet.heat`.
- `url` always uses GET. For authenticated endpoints, include credentials via a server-rendered URL with the right query/cookie context, or subclass `loadFromUrl()`.

## See also

- [Leaflet API reference](https://leafletjs.com/reference.html)
- `docs/components/map.md` — Blade component
- `docs/recipes/maps.md` — patterns for inline markers, GeoJSON endpoints, and custom tile providers
