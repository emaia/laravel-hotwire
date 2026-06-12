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

Leaflet's default marker icons reference assets by relative path, which Vite/Webpack don't resolve, so out of the box markers render as broken images. The controller fixes this once at module load by importing the bundled icons and calling `L.Icon.Default.mergeOptions(...)`. You don't need to do anything; markers render correctly with the standard pin.

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
