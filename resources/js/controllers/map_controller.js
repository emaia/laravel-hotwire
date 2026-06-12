// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

import iconRetinaUrl from "leaflet/dist/images/marker-icon-2x.png";
import iconUrl from "leaflet/dist/images/marker-icon.png";
import shadowUrl from "leaflet/dist/images/marker-shadow.png";

// Vite/Webpack don't resolve Leaflet's default icon paths; merge the bundled
// assets once so markers render with the standard pin instead of broken images.
L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

const DEFAULT_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
const DEFAULT_ATTRIBUTION = "© OpenStreetMap contributors";

export default class extends Controller {
    static values = {
        center: { type: Array, default: [0, 0] },
        zoom: { type: Number, default: 13 },
        url: { type: String, default: "" },
        scrollWheelZoom: { type: Boolean, default: true },
        markers: { type: Array, default: [] },
    };

    map = null;
    observer = null;

    connect() {
        const defaults = this.defaultView();
        const center = this.centerValue ?? defaults.center ?? [0, 0];
        const zoom = this.zoomValue ?? defaults.zoom ?? 13;

        this.map = L.map(this.element, {
            center,
            zoom,
            scrollWheelZoom: this.scrollWheelZoomValue,
        });

        const tileUrl = this.tileLayerUrl() ?? DEFAULT_TILE_URL;
        const tileOpts = { attribution: DEFAULT_ATTRIBUTION, ...this.tileLayerOptions() };
        L.tileLayer(tileUrl, tileOpts).addTo(this.map);

        this.markersValue.forEach(([lat, lng, label]) => {
            const marker = L.marker([lat, lng]).addTo(this.map);
            if (label) marker.bindPopup(label);
        });

        if (this.urlValue !== "") {
            this.loadFromUrl().catch((error) => {
                console.error("Map GeoJSON fetch failed:", error);
            });
        }

        this.afterInit();

        this.observer = new ResizeObserver(() => this.map?.invalidateSize());
        this.observer.observe(this.element);

        this.dispatch("ready");
    }

    disconnect() {
        this.observer?.disconnect();
        this.observer = null;
        this.map?.remove();
        this.map = null;
    }

    async loadFromUrl() {
        const response = await fetch(this.urlValue);
        const geojson = await response.json();
        if (this.map) {
            L.geoJSON(geojson).addTo(this.map);
        }
    }

    flyTo(event) {
        const { center, zoom } = event?.detail ?? {};
        if (center) this.map?.flyTo(center, zoom);
    }

    fitBounds(event) {
        const { bounds } = event?.detail ?? {};
        if (bounds) this.map?.fitBounds(bounds);
    }

    /** Override in subclass — applied before values, so server-rendered values still win. */
    defaultView() {
        return {};
    }

    /** Override in subclass — return a tile URL template or null to use OSM. */
    tileLayerUrl() {
        return null;
    }

    /** Override in subclass — extra L.tileLayer options (attribution override, maxZoom, subdomains). */
    tileLayerOptions() {
        return {};
    }

    /** Override in subclass — attach event listeners after init (e.g. map.on("click", …)). */
    afterInit() {}
}
