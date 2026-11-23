import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { compileCssFixture, replacePresetImport } from "../../scripts/css_build_contract.js";

const presets = ["nova", "bloom"];
const css = {};
let leafletCss;

test.beforeAll(async () => {
    const stub = await readFile("stubs/resources/css/app.css", "utf8");

    leafletCss = await readFile("node_modules/leaflet/dist/leaflet.css", "utf8");
    await Promise.all(
        presets.map(async (preset) => {
            css[preset] = await compileCssFixture(replacePresetImport(stub, preset));
        }),
    );
});

// The markup Leaflet 1.9 builds inside the element it mounts on, reduced to the parts the stylesheet colours.
const leafletMap = (id, attributes = "") => `
    <div id="${id}" data-slot="map" ${attributes} class="leaflet-container leaflet-touch" style="width: 300px; height: 200px">
        <div class="leaflet-pane leaflet-map-pane">
            <div id="${id}-tiles" class="leaflet-pane leaflet-tile-pane">
                <div class="leaflet-layer">
                    <div class="leaflet-tile-container">
                        <img id="${id}-tile" class="leaflet-tile leaflet-tile-loaded" alt="">
                    </div>
                </div>
            </div>
            <div id="${id}-overlays" class="leaflet-pane leaflet-overlay-pane"></div>
            <div id="${id}-markers" class="leaflet-pane leaflet-marker-pane"></div>
            <div class="leaflet-pane leaflet-tooltip-pane">
                <div id="${id}-tooltip" class="leaflet-tooltip leaflet-tooltip-top">Tooltip</div>
            </div>
            <div class="leaflet-pane leaflet-popup-pane">
                <div class="leaflet-popup">
                    <div id="${id}-popup" class="leaflet-popup-content-wrapper">
                        <div class="leaflet-popup-content">Popup</div>
                    </div>
                    <div class="leaflet-popup-tip-container"><div class="leaflet-popup-tip"></div></div>
                    <a id="${id}-close" class="leaflet-popup-close-button" href="#close">×</a>
                </div>
            </div>
        </div>
        <div class="leaflet-control-container">
            <div class="leaflet-top leaflet-left">
                <div class="leaflet-control-zoom leaflet-bar leaflet-control">
                    <a id="${id}-zoom" class="leaflet-control-zoom-in" href="#in">+</a>
                    <a class="leaflet-control-zoom-out" href="#out">−</a>
                </div>
            </div>
            <div class="leaflet-bottom leaflet-left">
                <div class="leaflet-control-scale leaflet-control">
                    <div id="${id}-scale" class="leaflet-control-scale-line">100 m</div>
                </div>
            </div>
            <div class="leaflet-bottom leaflet-right">
                <div id="${id}-attribution" class="leaflet-control-attribution leaflet-control">
                    Data <a id="${id}-attribution-link" href="#osm">OpenStreetMap</a>
                </div>
            </div>
        </div>
    </div>
`;

// The controller imports Leaflet's stylesheet from JavaScript, so it reaches the page unlayered and its
// position relative to the application stylesheet depends on the bundler.
async function mount(page, preset, { leafletFirst = false, body } = {}) {
    const styles = [`<style>${css[preset]}</style>`, `<style>${leafletCss}</style>`];

    await page.setContent(`
        ${(leafletFirst ? styles.reverse() : styles).join("\n")}
        ${body}
    `);
}

const filterOf = (page, selector) =>
    page.locator(selector).evaluate((element) => getComputedStyle(element).filter);

for (const preset of presets) {
    test.describe(`${preset} preset`, () => {
        test("inverts only the tile pane, and only in a dark scope", async ({ page }) => {
            await mount(page, preset, {
                body: `
                    ${leafletMap("default")}
                    <div data-theme="dark">
                        ${leafletMap("dark")}
                        <div data-theme="light">${leafletMap("light-inside-dark")}</div>
                    </div>
                    <div data-theme="light">
                        <div data-theme="dark">${leafletMap("dark-inside-light")}</div>
                    </div>
                    ${leafletMap("self", 'data-theme="dark"')}
                `,
            });

            for (const id of ["dark", "dark-inside-light", "self"]) {
                expect(await filterOf(page, `#${id}-tiles`), id).toContain("invert");
            }

            for (const id of ["default", "light-inside-dark"]) {
                expect(await filterOf(page, `#${id}-tiles`), id).toBe("none");
            }

            // Leaflet sets `filter: inherit` on every tile; a filter on the tile's own container would be
            // applied twice and cancel itself out.
            expect(await filterOf(page, "#dark-tile")).toBe("none");
            expect(await filterOf(page, "#dark-overlays")).toBe("none");
            expect(await filterOf(page, "#dark-markers")).toBe("none");
        });

        test("lets the application replace or drop the tile filter", async ({ page }) => {
            await mount(page, preset, {
                body: `
                    <div data-theme="dark">
                        ${leafletMap("none", 'style="--map-tile-filter: none"')}
                        ${leafletMap("custom", 'style="--map-tile-filter: grayscale(1)"')}
                    </div>
                `,
            });

            expect(await filterOf(page, "#none-tiles")).toBe("none");
            expect(await filterOf(page, "#custom-tiles")).toBe("grayscale(1)");
        });

        for (const leafletFirst of [false, true]) {
            const order = leafletFirst ? "before" : "after";

            test(`keeps Leaflet controls readable in both schemes when Leaflet loads ${order} the preset`, async ({
                page,
            }) => {
                await mount(page, preset, {
                    leafletFirst,
                    body: `
                        <div data-theme="light">${leafletMap("light")}</div>
                        <div data-theme="dark">${leafletMap("dark")}</div>
                    `,
                });

                const results = await page.evaluate(() => {
                    const canvas = document.createElement("canvas");
                    canvas.width = 1;
                    canvas.height = 1;
                    const context = canvas.getContext("2d", { colorSpace: "srgb", willReadFrequently: true });
                    const rgba = (color) => {
                        context.clearRect(0, 0, 1, 1);
                        context.fillStyle = color;
                        context.fillRect(0, 0, 1, 1);
                        const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;

                        return [r, g, b, a / 255];
                    };
                    const over = (top, base) => top.slice(0, 3).map((channel, i) => channel * top[3] + base[i] * (1 - top[3]));
                    const luminance = (rgb) =>
                        rgb
                            .map((channel) => channel / 255)
                            .map((channel) => (channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4))
                            .reduce((sum, channel, index) => sum + channel * [0.2126, 0.7152, 0.0722][index], 0);
                    // Translucent surfaces sit on the map, so they are measured against the container's own
                    // background, which is what shows through while tiles load.
                    const surfaceOf = (element, map) => {
                        const base = rgba(getComputedStyle(map).backgroundColor);
                        const chain = [];

                        for (let node = element; node && node !== map; node = node.parentElement) {
                            chain.unshift(rgba(getComputedStyle(node).backgroundColor));
                        }

                        return chain.reduce((surface, layer) => over(layer, surface), over(base, [255, 255, 255]));
                    };

                    return ["light", "dark"].flatMap((scheme) => {
                        const map = document.getElementById(scheme);

                        return ["zoom", "popup", "close", "tooltip", "scale", "attribution", "attribution-link"].map((part) => {
                            const element = document.getElementById(`${scheme}-${part}`);
                            const surface = luminance(surfaceOf(element, map));
                            const text = luminance(over(rgba(getComputedStyle(element).color), surfaceOf(element, map)));
                            const [lighter, darker] = [Math.max(surface, text), Math.min(surface, text)];

                            return {
                                scheme,
                                part,
                                contrast: Math.round(((lighter + 0.05) / (darker + 0.05)) * 100) / 100,
                                lightOnDark: text > surface,
                            };
                        });
                    });
                });

                for (const { scheme, part, contrast, lightOnDark } of results) {
                    // The close button is an icon-like glyph, held to the non-text threshold.
                    expect(contrast, `${scheme} ${part}`).toBeGreaterThanOrEqual(part === "close" ? 3 : 4.5);
                    expect(lightOnDark, `${scheme} ${part}`).toBe(scheme === "dark");
                }
            });
        }

        test("paints the tooltip arrow with the tooltip surface", async ({ page }) => {
            await mount(page, preset, { body: `<div data-theme="dark">${leafletMap("dark")}</div>` });

            const [arrow, surface] = await page.locator("#dark-tooltip").evaluate((element) => [
                getComputedStyle(element, "::before").borderTopColor,
                getComputedStyle(element).backgroundColor,
            ]);

            expect(arrow).toBe(surface);
        });
    });
}
