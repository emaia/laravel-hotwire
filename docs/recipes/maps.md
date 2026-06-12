# Maps — three patterns

`<x-hwc::map>` covers the 90% case of "show a pin on a map" with very little code. This recipe walks through the three common patterns: inline markers, a GeoJSON endpoint, and a subclass with custom tiles and click handlers.

## Pattern 1 — Pin at an address

The simplest case: known coordinates, single marker, default OSM tiles.

```blade
{{-- show.blade.php --}}
<x-hwc::map
    :center="[$store->lat, $store->lng]"
    :zoom="14"
    :markers="[[$store->lat, $store->lng, $store->name]]"
    height="320px"
/>
```

That's it. The marker shows a popup with the store name on click.

For multiple known points (say, a list of branches), pass an array:

```blade
<x-hwc::map
    :center="[-23.5505, -46.6333]"
    :zoom="11"
    :markers="$stores->map(fn (\$s) => [\$s->lat, \$s->lng, \$s->name])->all()"
    height="400px"
/>
```

## Pattern 2 — GeoJSON endpoint

When the dataset is large, dynamic, or already lives in a service that speaks GeoJSON, skip the inline markers and point `url` at the endpoint.

### Laravel route

```php
// routes/web.php
Route::get('/api/incidents', function () {
    return [
        'type' => 'FeatureCollection',
        'features' => Incident::query()
            ->where('status', 'open')
            ->get()
            ->map(fn ($incident) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$incident->lng, $incident->lat],
                ],
                'properties' => [
                    'name' => $incident->title,
                    'severity' => $incident->severity,
                ],
            ])
            ->all(),
    ];
});
```

### Blade

```blade
<x-hwc::map url="/api/incidents" height="500px" />
```

The map renders with no markers initially, fetches the URL after init, and adds the GeoJSON layer when the response lands. If the endpoint fails the map still shows; the error is logged to `console.error`.

> **Note** — Leaflet's GeoJSON in coordinates is `[lng, lat]`, not `[lat, lng]`. The GeoJSON spec uses east-then-north order. Inline markers use `[lat, lng]` because that's Leaflet's `L.marker` argument order.

## Pattern 3 — Custom tiles + click handlers (subclass)

Override the tile provider and wire click events when you need pin-drop, store-locator search, or custom map styling.

### Subclass

```js
// resources/js/controllers/store_locator_controller.js
import MapController from "./map_controller";

export default class extends MapController {
    tileLayerUrl() {
        // CartoDB Positron — a clean light basemap that doesn't fight your UI
        return "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png";
    }

    tileLayerOptions() {
        return {
            attribution: "© <a href='https://carto.com/attributions'>CARTO</a>",
            maxZoom: 19,
            subdomains: "abcd",
        };
    }

    defaultView() {
        return { center: [-23.5505, -46.6333], zoom: 11 };
    }

    afterInit() {
        this.map.on("click", (e) => {
            this.dispatch("pin-drop", {
                detail: { latlng: e.latlng },
            });
        });
    }
}
```

### Blade — wire the swap

```blade
<x-hwc::map
    controller="store-locator"
    height="500px"
    data-controller="store-locator pin-form"
/>

<form
    data-controller="pin-form"
    data-action="store-locator:pin-drop@window->pin-form#captureLatLng"
>
    <input type="hidden" name="lat" data-pin-form-target="lat">
    <input type="hidden" name="lng" data-pin-form-target="lng">
    <button type="submit">Save pin</button>
</form>
```

The subclass dispatches a `store-locator:pin-drop` event on click; the form controller listens, stuffs the coords into hidden inputs, and the user submits.

### Companion `pin_form_controller.js`

```js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["lat", "lng"];

    captureLatLng(event) {
        const { lat, lng } = event.detail.latlng;
        this.latTarget.value = lat;
        this.lngTarget.value = lng;
    }
}
```

## Marker clustering (out of scope, but easy)

For >500 markers, install `leaflet.markercluster` and replace the inline marker loop in a subclass:

```js
import MapController from "./map_controller";
import L from "leaflet";
import "leaflet.markercluster/dist/leaflet.markercluster.js";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

export default class extends MapController {
    afterInit() {
        const cluster = L.markerClusterGroup();
        this.markersValue.forEach(([lat, lng, label]) => {
            const marker = L.marker([lat, lng]);
            if (label) marker.bindPopup(label);
            cluster.addLayer(marker);
        });
        this.map.addLayer(cluster);

        // Suppress the default per-marker loop by clearing the value.
        // (We've already added them via the cluster group.)
        this.markersValue = [];
    }
}
```

The cluster plugin adds ~80KB but turns 5,000 markers from "browser cries" into "smooth".

## See also

- `docs/controllers/map.md`
- `docs/components/map.md`
- [Leaflet plugins index](https://leafletjs.com/plugins.html) — heatmap, draw, fullscreen, locate, fly-to-bounds, and 200+ others; each is "subclass + import in `afterInit`"
