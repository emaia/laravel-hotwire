import { afterEach, beforeEach, expect, mock, test } from "bun:test";

// --- Leaflet mock ---
// The controller does `import L from "leaflet"` + uses L.map, L.tileLayer,
// L.marker, L.geoJSON and L.Icon.Default.mergeOptions. Provide a controllable
// fake we can introspect from tests.

const mapState = {
    instance: null,
    initCalls: [],
    tileLayerCalls: [],
    markerCalls: [],
    geoJsonCalls: [],
    iconMergeOptions: null,
    flyToCalls: [],
    fitBoundsCalls: [],
    invalidateSizeCalls: 0,
    removeCalls: 0,
    onCalls: [],
};

function createMapInstance() {
    const instance = {
        setView: mock(() => instance),
        invalidateSize: mock(() => { mapState.invalidateSizeCalls++; }),
        remove: mock(() => { mapState.removeCalls++; }),
        flyTo: mock((center, zoom) => { mapState.flyToCalls.push({ center, zoom }); }),
        fitBounds: mock((bounds) => { mapState.fitBoundsCalls.push({ bounds }); }),
        on: mock((event, handler) => { mapState.onCalls.push({ event, handler }); }),
    };
    return instance;
}

function createLayer(kind) {
    const layer = {
        addTo: mock((m) => { layer._addedTo = m; return layer; }),
        bindPopup: mock((html) => { layer._popup = html; return layer; }),
        _kind: kind,
    };
    return layer;
}

const mapFn = mock((element, options) => {
    const instance = createMapInstance();
    instance._element = element;
    instance._options = options;
    mapState.instance = instance;
    mapState.initCalls.push({ element, options });
    return instance;
});

const tileLayerFn = mock((url, options) => {
    const layer = createLayer("tile");
    layer._url = url;
    layer._options = options;
    mapState.tileLayerCalls.push({ url, options });
    return layer;
});

const markerFn = mock((latlng) => {
    const layer = createLayer("marker");
    layer._latlng = latlng;
    mapState.markerCalls.push({ latlng });
    return layer;
});

const geoJsonFn = mock((data) => {
    const layer = createLayer("geojson");
    layer._data = data;
    mapState.geoJsonCalls.push({ data });
    return layer;
});

const iconDefault = {
    mergeOptions: mock((options) => { mapState.iconMergeOptions = options; }),
};

mock.module("leaflet", () => ({
    default: {
        map: mapFn,
        tileLayer: tileLayerFn,
        marker: markerFn,
        geoJSON: geoJsonFn,
        Icon: { Default: iconDefault },
    },
}));
mock.module("leaflet/dist/leaflet.css", () => ({}));
mock.module("leaflet/dist/images/marker-icon-2x.png", () => ({ default: "icon-2x.png" }));
mock.module("leaflet/dist/images/marker-icon.png", () => ({ default: "icon.png" }));
mock.module("leaflet/dist/images/marker-shadow.png", () => ({ default: "shadow.png" }));

const { mountController, wait } = await import("../../resources/js/helpers/test_stimulus.js");
const { default: MapController } = await import(
    "../../resources/js/controllers/map_controller.js"
);

let mounted;
let lastResizeObserver;

beforeEach(() => {
    mapState.instance = null;
    mapState.initCalls = [];
    mapState.tileLayerCalls = [];
    mapState.markerCalls = [];
    mapState.geoJsonCalls = [];
    mapState.flyToCalls = [];
    mapState.fitBoundsCalls = [];
    mapState.invalidateSizeCalls = 0;
    mapState.removeCalls = 0;
    mapState.onCalls = [];
    mapFn.mockClear();
    tileLayerFn.mockClear();
    markerFn.mockClear();
    geoJsonFn.mockClear();
    iconDefault.mergeOptions.mockClear();

    lastResizeObserver = null;
    globalThis.ResizeObserver = class {
        constructor(cb) {
            this.cb = cb;
            this.observed = null;
            this.disconnected = false;
            lastResizeObserver = this;
        }
        observe(el) { this.observed = el; }
        disconnect() { this.disconnected = true; }
    };
});

afterEach(async () => {
    await mounted?.cleanup();
    mounted = null;
});

// --- init ---

test.serial("connect initialises L.map with center, zoom and scrollWheelZoom", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[-23.55, -46.63]" data-map-zoom-value="12"></div>`);

    expect(mapFn).toHaveBeenCalledTimes(1);
    expect(mapState.initCalls[0].options.center).toEqual([-23.55, -46.63]);
    expect(mapState.initCalls[0].options.zoom).toBe(12);
    expect(mapState.initCalls[0].options.scrollWheelZoom).toBe(true);
});

test.serial("scrollWheelZoom value of false is forwarded to L.map", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]" data-map-scroll-wheel-zoom-value="false"></div>`);

    expect(mapState.initCalls[0].options.scrollWheelZoom).toBe(false);
});

test.serial("adds OSM tile layer by default with attribution", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]"></div>`);

    expect(tileLayerFn).toHaveBeenCalledTimes(1);
    expect(mapState.tileLayerCalls[0].url).toContain("openstreetmap.org");
    expect(mapState.tileLayerCalls[0].options.attribution).toContain("OpenStreetMap");
});

// --- markers ---

test.serial("renders inline markers with popups when label is present", async () => {
    const markers = JSON.stringify([[-23.55, -46.63, "São Paulo"], [-22.91, -43.17]]);
    await mount(`<div data-controller="map" data-map-center-value="[0,0]" data-map-markers-value='${markers}'></div>`);

    expect(markerFn).toHaveBeenCalledTimes(2);
    expect(mapState.markerCalls[0].latlng).toEqual([-23.55, -46.63]);

    // First marker has a label → bindPopup called; second doesn't.
    const firstLayer = markerFn.mock.results[0].value;
    const secondLayer = markerFn.mock.results[1].value;
    expect(firstLayer.bindPopup).toHaveBeenCalledWith("São Paulo");
    expect(secondLayer.bindPopup).not.toHaveBeenCalled();
});

// --- GeoJSON URL ---

test.serial("urlValue triggers fetch and renders the response via L.geoJSON", async () => {
    const payload = { type: "FeatureCollection", features: [] };
    globalThis.fetch = mock(() => Promise.resolve({ json: () => Promise.resolve(payload) }));

    await mount(`<div data-controller="map" data-map-center-value="[0,0]" data-map-url-value="/api/locations"></div>`);
    await wait(0);
    await wait(0);

    expect(globalThis.fetch).toHaveBeenCalledWith("/api/locations");
    expect(geoJsonFn).toHaveBeenCalledTimes(1);
    expect(mapState.geoJsonCalls[0].data).toEqual(payload);
});

test.serial("URL fetch failure is caught and logged, does not throw", async () => {
    globalThis.fetch = mock(() => Promise.reject(new Error("network")));
    const originalError = console.error;
    console.error = mock(() => {});

    await mount(`<div data-controller="map" data-map-center-value="[0,0]" data-map-url-value="/api/locations"></div>`);
    await wait(0);

    console.error = originalError;

    // Map still initialised even though the geojson layer was never added.
    expect(mapState.instance).not.toBeNull();
    expect(geoJsonFn).not.toHaveBeenCalled();
});

// --- ResizeObserver ---

test.serial("connect registers a ResizeObserver on the element", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]"></div>`);

    expect(lastResizeObserver).not.toBeNull();
    expect(lastResizeObserver.observed).toBe(mounted.root);
});

test.serial("ResizeObserver callback calls map.invalidateSize", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]"></div>`);

    lastResizeObserver.cb();

    expect(mapState.invalidateSizeCalls).toBe(1);
});

// --- default icon fix ---

test.serial("merges Leaflet's default icon options to fix bundler-resolved paths", () => {
    // mergeOptions runs once at module load (not on connect). Verified by the
    // state captured at import time, before beforeEach clears the spy.
    expect(mapState.iconMergeOptions).not.toBeNull();
    expect(mapState.iconMergeOptions).toHaveProperty("iconUrl");
    expect(mapState.iconMergeOptions).toHaveProperty("iconRetinaUrl");
    expect(mapState.iconMergeOptions).toHaveProperty("shadowUrl");
});

// --- hooks ---

test.serial("defaultView hook supplies center/zoom only as a fallback (values still win)", async () => {
    class HookedMap extends MapController {
        defaultView() { return { center: [10, 20], zoom: 5 }; }
    }

    mounted = await mountController(
        "map",
        HookedMap,
        `<div data-controller="map" data-map-center-value="[-23.55, -46.63]" data-map-zoom-value="12"></div>`,
    );

    // Values from the markup are used; defaultView just provides fallbacks.
    expect(mapState.initCalls[0].options.center).toEqual([-23.55, -46.63]);
    expect(mapState.initCalls[0].options.zoom).toBe(12);
});

test.serial("tileLayerUrl + tileLayerOptions hooks override the default tile source", async () => {
    class CartoMap extends MapController {
        tileLayerUrl() { return "https://{s}.carto.example/{z}/{x}/{y}.png"; }
        tileLayerOptions() { return { attribution: "© Carto", maxZoom: 19 }; }
    }

    mounted = await mountController("map", CartoMap,
        `<div data-controller="map" data-map-center-value="[0,0]"></div>`,
    );

    expect(mapState.tileLayerCalls[0].url).toBe("https://{s}.carto.example/{z}/{x}/{y}.png");
    expect(mapState.tileLayerCalls[0].options.attribution).toBe("© Carto");
    expect(mapState.tileLayerCalls[0].options.maxZoom).toBe(19);
});

test.serial("afterInit hook runs after the map is initialised", async () => {
    let mapAtHook = null;
    class HookedMap extends MapController {
        afterInit() { mapAtHook = this.map; }
    }

    mounted = await mountController("map", HookedMap,
        `<div data-controller="map" data-map-center-value="[0,0]"></div>`,
    );

    expect(mapAtHook).toBe(mapState.instance);
});

// --- actions ---

test.serial("flyTo action delegates to map.flyTo with event detail", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]"></div>`);

    mounted.controller.flyTo({ detail: { center: [-23.55, -46.63], zoom: 14 } });

    expect(mapState.flyToCalls).toHaveLength(1);
    expect(mapState.flyToCalls[0].center).toEqual([-23.55, -46.63]);
    expect(mapState.flyToCalls[0].zoom).toBe(14);
});

test.serial("fitBounds action delegates to map.fitBounds with event detail", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]"></div>`);
    const bounds = [[0, 0], [10, 10]];

    mounted.controller.fitBounds({ detail: { bounds } });

    expect(mapState.fitBoundsCalls).toHaveLength(1);
    expect(mapState.fitBoundsCalls[0].bounds).toBe(bounds);
});

// --- ready dispatch ---

test.serial("dispatches map:ready event after init", async () => {
    const events = [];

    class WithSpy extends MapController {
        dispatch(eventName, options) {
            events.push(eventName);
            return super.dispatch(eventName, options);
        }
    }

    mounted = await mountController(
        "map",
        WithSpy,
        `<div data-controller="map" data-map-center-value="[0,0]"></div>`,
    );

    expect(events).toContain("ready");
});

// --- disconnect cleanup ---

test.serial("disconnect removes the map and disconnects the observer", async () => {
    await mount(`<div data-controller="map" data-map-center-value="[0,0]"></div>`);

    expect(mapState.removeCalls).toBe(0);
    expect(lastResizeObserver.disconnected).toBe(false);

    mounted.controller.disconnect();

    expect(mapState.removeCalls).toBe(1);
    expect(lastResizeObserver.disconnected).toBe(true);
});

async function mount(html) {
    mounted = await mountController("map", MapController, html);
}
