# Maps

Start with `<hw:map>` for sizing, value serialization, and controller wiring. See the
[component reference](../components/map.md) for all props and the
[controller reference](../controllers/map.md) for values, actions, and subclass hooks. This
recipe focuses on complete pin, endpoint, pin-drop, and clustering tasks.

## Show a pin at an address

The simplest case: known coordinates, single marker, default OSM tiles.

```blade
{{-- show.blade.php --}}
<hw:map
    :center="[$store->lat, $store->lng]"
    :zoom="14"
    :markers="[[$store->lat, $store->lng, $store->name]]"
    height="320px"
/>
```

The marker shows a popup with the store name on click.

For multiple known points (say, a list of branches), pass an array:

```blade
<hw:map
    :center="[-23.5505, -46.6333]"
    :zoom="11"
    :markers="$stores->map(fn ($store) => [$store->lat, $store->lng, $store->name])->all()"
    height="400px"
/>
```

## Load incidents from a GeoJSON endpoint

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
<hw:map url="/api/incidents" height="500px" />
```

The map renders with no markers initially, fetches the URL after init, and adds the GeoJSON layer when the response lands. If the endpoint fails the map still shows; the error is logged to `console.error`.

> **Note** — Leaflet's GeoJSON in coordinates is `[lng, lat]`, not `[lat, lng]`. The GeoJSON spec uses east-then-north order. Inline markers use `[lat, lng]` because that's Leaflet's `L.marker` argument order.

## Let a user drop a pin

Use an app-owned subclass when a map click needs to update another part of the page. Import the
package controller through `@hotwire`, then mount the subclass through the component's
`controller` prop.

### Subclass

```js
// resources/js/controllers/store_locator_controller.js
import MapController from "@hotwire/map_controller.js";

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

    afterInit() {
        this.map.on("click", (e) => {
            this.dispatch("pin-drop", {
                detail: { latlng: e.latlng },
            });
        });
    }
}
```

### Wire the map and form

```blade
<hw:map controller="store-locator" :center="[-23.5505, -46.6333]" :zoom="11" height="500px" />

<hw:form
    :action="route('stores.location.update', $store)"
    method="patch"
    data-controller="pin-form"
    data-action="store-locator:pin-drop@window->pin-form#captureLatLng"
>
    <hw:input type="hidden" name="latitude" data-pin-form-target="lat" />
    <hw:input type="hidden" name="longitude" data-pin-form-target="lng" />
    <hw:button type="submit">Save pin</hw:button>
</hw:form>
```

The component still requires `center`, `markers`, or `url`; a subclass's `defaultView()` cannot
satisfy that server-side validation. Here the explicit center also makes the initial viewport
clear in the Blade template.

The subclass dispatches a `store-locator:pin-drop` event on click; the form controller listens,
puts the coordinates into named hidden fields, and the user submits the PATCH request. `<hw:form>`
adds the CSRF token and method-spoofing field automatically; the authorized endpoint below persists
the new location.

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

### Persist the coordinates

```php
// routes/web.php
Route::patch(
    '/stores/{store}/location',
    [\App\Http\Controllers\StoreLocationController::class, 'update'],
)->middleware('auth')->name('stores.location.update');
```

```php
// app/Http/Controllers/StoreLocationController.php
namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreLocationController extends Controller
{
    public function update(Request $request, Store $store): RedirectResponse
    {
        Gate::authorize('update', $store);

        $coordinates = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $store->update($coordinates);

        return to_route('stores.show', $store)->with('status', 'Store location updated.');
    }
}
```

The route name matches the form action. Authentication rejects guests, the Store policy controls
who may update the location, and the normal redirect works with both Turbo Drive and a full-page
submission.

## Cluster a large marker set

For hundreds of markers, install `leaflet.markercluster` and give the subclass a dedicated
`clusterMarkers` value:

```bash
bun add leaflet.markercluster
```

```js
// resources/js/controllers/cluster_map_controller.js
import MapController from "@hotwire/map_controller.js";
import L from "leaflet";
import "leaflet.markercluster/dist/leaflet.markercluster.js";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

export default class extends MapController {
    static values = {
        ...MapController.values,
        clusterMarkers: { type: Array, default: [] },
    };

    afterInit() {
        if (this.clusterMarkersValue.length === 0) return;

        const cluster = L.markerClusterGroup();
        this.clusterMarkersValue.forEach(([lat, lng, label]) => {
            const marker = L.marker([lat, lng]);
            if (label) marker.bindPopup(label);
            cluster.addLayer(marker);
        });
        this.map.addLayer(cluster);

        const bounds = cluster.getBounds();
        if (bounds.isValid()) {
            this.map.fitBounds(bounds, { padding: [20, 20], maxZoom: 15 });
        }
    }
}
```

Pass cluster data through the component's `stimulus` prop and do not pass its `markers` prop:

```blade
<hw:map
    controller="cluster-map"
    :center="[-23.5505, -46.6333]"
    :stimulus="stimulus()->controller('cluster-map', [
        'clusterMarkers' => $stores
            ->map(fn ($store) => [$store->lat, $store->lng, $store->name])
            ->values()
            ->all(),
    ])"
    height="500px"
/>
```

The Stimulus builder JSON-encodes the marker array and merges its `cluster-map` controller token
with the component's `controller` prop. The base controller therefore mounts no individual
markers; only the cluster group owns them. Using `markers` and then clearing `this.markersValue`
in `afterInit()` is too late because the base controller adds those markers before the hook runs.

## See also

- [Map component](../components/map.md) — props, auto-fit, sizing, and controller swap
- [Map controller](../controllers/map.md) — values, actions, lifecycle, and hooks
- [Extending controllers](../extending-controllers.md) — `@hotwire` subclass and explicit fork paths
- [Leaflet plugins index](https://leafletjs.com/plugins.html) — heatmap, draw, fullscreen, locate, and other extensions
