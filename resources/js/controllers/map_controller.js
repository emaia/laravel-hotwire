// @hotwire-package
import { Controller } from "@hotwired/stimulus";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

import iconRetinaUrl from "leaflet/dist/images/marker-icon-2x.png";
import iconUrl from "leaflet/dist/images/marker-icon.png";
import shadowUrl from "leaflet/dist/images/marker-shadow.png";

import { attachMorphRecovery } from "./_turbo_morph_recovery.js";

// Vite/Webpack don't resolve Leaflet's default icon paths.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

const DEFAULT_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
const DEFAULT_ATTRIBUTION = "© OpenStreetMap contributors";
const DEFAULT_FIT_OPTIONS = { padding: [20, 20], maxZoom: 15 };

export default class extends Controller {
    static values = {
        center: { type: Array, default: [0, 0] },
        zoom: { type: Number, default: 13 },
        url: { type: String, default: "" },
        scrollWheelZoom: { type: Boolean, default: true },
        markers: { type: Array, default: [] },
        fit: { type: Boolean, default: false },
    };

    map = null;
    observer = null;

    connect() {
        this.initMap();

        this.detachMorphRecovery = attachMorphRecovery(this, {
            isStale: () => !this.element.querySelector(".leaflet-pane"),
            recover: () => this.initMap(),
        });
    }

    disconnect() {
        this.detachMorphRecovery?.();
        this.destroyMap();
    }

    initMap() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        if (this.observer) {
            this.observer.disconnect();
        }

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

        const markerLayers = [];
        this.markersValue.forEach(([lat, lng, label]) => {
            const marker = L.marker([lat, lng]).addTo(this.map);
            if (label) marker.bindPopup(label);
            markerLayers.push(marker);
        });

        if (this.urlValue !== "") {
            this.loadFromUrl()
                .then((geoLayer) => {
                    if (this.fitValue) this.fitToData(markerLayers, geoLayer);
                })
                .catch((error) => {
                    console.error("Map GeoJSON fetch failed:", error);
                    if (this.fitValue) this.fitToData(markerLayers, null);
                });
        } else if (this.fitValue) {
            this.fitToData(markerLayers, null);
        }

        this.afterInit();

        this.observer = new ResizeObserver(() => this.map?.invalidateSize());
        this.observer.observe(this.element);

        this.dispatch("ready");
    }

    destroyMap() {
        this.observer?.disconnect();
        this.observer = null;
        this.map?.remove();
        this.map = null;
    }

    /** Public action — re-fetch the GeoJSON URL and add as a new layer.
     *  Wire from the app via any event name: data-action="incident:created@window->map#reload". */
    reload() {
        if (this.urlValue === "") return;
        this.loadFromUrl().catch((error) => {
            console.error("Map reload fetch failed:", error);
        });
    }

    async loadFromUrl() {
        const response = await fetch(this.urlValue);
        const geojson = await response.json();
        if (!this.map) return null;
        return L.geoJSON(geojson).addTo(this.map);
    }

    /** Override in subclass to customize fit options (padding, maxZoom, etc.). */
    fitToData(markerLayers, geoLayer) {
        const layers = [...markerLayers];
        if (geoLayer) layers.push(geoLayer);
        if (layers.length === 0) return;

        const bounds = L.featureGroup(layers).getBounds();
        if (bounds.isValid()) {
            this.map.fitBounds(bounds, DEFAULT_FIT_OPTIONS);
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

    /** Override in subclass — attach event listeners after init (e.g., map.on("click", …)). */
    afterInit() {}
}
