import { beforeAll, describe, expect, test } from "bun:test";
import { mkdtemp, readFile, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { join } from "node:path";
import baselines from "./preset_build_baselines.json";
import {
    buildCssContract,
    compileCssFixture,
    disableAutomaticSources,
    measurementRows,
    minifyCssWithVite,
    packageSourceEntrypoint,
    replacePresetImport,
    resolveBaselines,
} from "../../scripts/css_build_contract.js";

const slotSelector = (slot) => new RegExp(`\\[data-slot=(?:["'])?${slot}(?:["'])?\\]`);
const carouselMechanic = /\[data-carousel-container\]/;
const automaticSourceUtility = String.raw`.w-\[811px\]`;
const packageSources = [
    {
        directive: '@source "../../vendor/emaia/laravel-hotwire/resources/views/**/*.blade.php";',
        utility: String.raw`.min-h-\[137px\]`,
    },
    {
        directive: '@source "../../vendor/emaia/laravel-hotwire/src/**/*.php";',
        utility: String.raw`.max-w-\[913px\]`,
    },
    {
        directive: '@source "../../vendor/emaia/laravel-hotwire/resources/js/**/*.js";',
        utility: String.raw`.z-\[31415\]`,
    },
];

let contract;
let productionNovaCss;

beforeAll(async () => {
    contract = await buildCssContract();
    productionNovaCss = await minifyCssWithVite(contract.outputs.presets.nova);
});

describe("public CSS presets", () => {
    test("compile every discovered preset through an installed-app entrypoint", () => {
        expect(Object.keys(contract.outputs.presets).length).toBeGreaterThan(0);

        for (const css of Object.values(contract.outputs.presets)) {
            expect(css).toContain("--background:");
            expect(css).toContain("--radius:");
            expect(css).toMatch(slotSelector("button"));
            expect(css).toMatch(carouselMechanic);
            expect(css).toContain(".hidden{display:none}");
            expect(css).toContain(".overflow-hidden{overflow:hidden}");
        }
    });

    test("compiles the generated selective bundle without omitted visual slots", () => {
        const css = contract.outputs.selective;
        const source = contract.sources.selective;

        expect(source).toStartWith("/* @hotwire-package */");
        expect(source).toContain(
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
        );
        expect(source).toMatch(slotSelector("button"));
        expect(source).toMatch(slotSelector("card"));
        expect(source).toMatch(slotSelector("tooltip"));
        expect(source).not.toMatch(slotSelector("badge"));

        expect(css).toContain("--background:");
        expect(css).toContain("--radius:");
        expect(css).toMatch(slotSelector("button"));
        expect(css).toMatch(slotSelector("card"));
        expect(css).toMatch(carouselMechanic);
        for (const { utility } of packageSources) {
            expect(css).toContain(utility);
        }
        expect(css).not.toMatch(slotSelector("badge"));
    });

    test("compiles shared forced-colors and print control fallbacks into every bundle", () => {
        for (const css of [...Object.values(contract.outputs.presets), contract.outputs.selective]) {
            expect(css).toContain("@media (forced-colors:active)");
            expect(css).toContain("@media print");
            expect(css).toMatch(slotSelector("switch"));
            expect(css).toMatch(slotSelector("slider"));
            expect(css).toMatch(slotSelector("multi-select-indicator"));
            expect(css).toMatch(slotSelector("progress-indicator"));
            expect(css).toContain("forced-color-adjust:none");
        }
    });

    test("preserves attribute-backed RTL selectors through Vite production minification", () => {
        expect(productionNovaCss).toMatch(/\[data-slot=switch\][^{]*\[dir=rtl\][^{]*:checked:before\{/);
        expect(productionNovaCss).toMatch(/\[data-slot=slider\]\[data-orientation=horizontal\][^{]*\[dir=rtl\][^{]*::-webkit-slider-runnable-track\{[^}]*linear-gradient\(to left/);
        expect(productionNovaCss).toMatch(/\[data-slot=side-panel\]\[data-side=left\][^{]*\[dir=rtl\][^{]*\{flex-direction:row-reverse\}/);
        expect(productionNovaCss).toMatch(/\[data-slot=side-panel\]\[data-side=right\][^{]*\[dir=rtl\][^{]*\{flex-direction:row\}/);
    });

    test("reports raw and gzip sizes against non-blocking baselines", () => {
        expect(contract.measurements.toolchain.tailwindcss).toMatch(/^4\./);
        expect(contract.measurements.toolchain.cli).toMatch(/^4\./);
        expect(baselines.toolchain.tailwindcss).toMatch(/^4\./);
        expect(baselines.toolchain.cli).toMatch(/^4\./);

        for (const measurements of [
            ...Object.values(contract.measurements.presets),
            contract.measurements.selective,
            ...Object.values(baselines.presets),
            baselines.selective,
        ]) {
            expect(measurements.rawBytes).toBeGreaterThan(0);
            expect(measurements.gzipBytes).toBeGreaterThan(0);
        }
    });

    test("compiles runtime utilities supplied only by the structural safelist", async () => {
        const css = await compileCssFixture(`
            @import "tailwindcss" source(none);
            @import "../../vendor/emaia/laravel-hotwire/resources/css/structural.css";
        `);

        expect(css).toContain(".hidden{display:none}");
        expect(css).toContain(".overflow-hidden{overflow:hidden}");
    });

    test("can compile an installed-app fixture without production minification", async () => {
        const css = await compileCssFixture(
            `
                @import "tailwindcss" source(none);
                .unminified-probe { color: red; }
            `,
            { minify: false },
        );

        expect(css).toContain(".unminified-probe {");
        expect(css).toContain("  color: red;");
    });

    test("requires every explicit package source to discover its vendor candidates", async () => {
        const fixture = packageSourceEntrypoint();

        for (const { directive, utility } of packageSources) {
            const withoutSource = fixture.replace(`${directive}\n`, "");
            expect(withoutSource).not.toBe(fixture);

            const css = await compileCssFixture(withoutSource);
            expect(css).not.toContain(utility);
        }
    });

    test("disables automatic sources for exactly one Tailwind import", async () => {
        expect(disableAutomaticSources('@import "tailwindcss";')).toBe('@import "tailwindcss" source(none);');
        expect(disableAutomaticSources('@import "tailwindcss" theme(static);')).toBe(
            '@import "tailwindcss" source(none) theme(static);',
        );
        expect(disableAutomaticSources('@import "tailwindcss" source(none);')).toBe(
            '@import "tailwindcss" source(none);',
        );
        expect(() => disableAutomaticSources('@import "./app.css";')).toThrow(/exactly one Tailwind import/);
        expect(() => disableAutomaticSources('@import "tailwindcss";\n@import "tailwindcss";')).toThrow(
            /exactly one Tailwind import/,
        );
        expect(() => disableAutomaticSources('@import "tailwindcss" source("./app");')).toThrow(/incompatible source/);

        const css = await compileCssFixture('@import "tailwindcss" theme(static);');
        expect(css).not.toContain(automaticSourceUtility);
        for (const { utility } of packageSources) {
            expect(css).not.toContain(utility);
        }
    });

    test("requires exactly one public preset import in the app stub", () => {
        const presetImport = '@import "../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css";';

        expect(replacePresetImport(presetImport, "bloom")).toContain("presets/bloom.css");
        expect(() => replacePresetImport('@import "tailwindcss";', "bloom")).toThrow(
            /exactly one public preset import/,
        );
        expect(() => replacePresetImport(`${presetImport}\n${presetImport}`, "bloom")).toThrow(
            /exactly one public preset import/,
        );
    });

    test("rewrites missing or malformed baselines in update mode", async () => {
        const directory = await mkdtemp(join(tmpdir(), "hotwire-css-baseline-"));
        const path = join(directory, "baselines.json");

        try {
            expect(
                await resolveBaselines(contract.measurements, {
                    path,
                    update: true,
                }),
            ).toEqual(contract.measurements);

            await writeFile(path, "not json");

            expect(
                await resolveBaselines(contract.measurements, {
                    path,
                    update: true,
                }),
            ).toEqual(contract.measurements);
        } finally {
            await rm(directory, { recursive: true, force: true });
        }
    });

    test("reports zero deltas when optional baseline entries are absent", () => {
        const rows = measurementRows(contract.measurements, {
            toolchain: {},
            presets: {},
        });

        expect(rows.find(({ build }) => build === "selective")).toMatchObject({
            rawDelta: 0,
            gzipDelta: 0,
        });
    });

    test("fails when Tailwind cannot resolve a utility", async () => {
        await expect(
            compileCssFixture(`
                @import "tailwindcss";

                [data-slot="broken"] {
                    @apply hotwire-utility-that-does-not-exist;
                }
            `),
        ).rejects.toThrow();
    });

    test("fails when an imported stylesheet is missing", async () => {
        await expect(
            compileCssFixture(`
                @import "tailwindcss";
                @import "./missing-stylesheet.css";
            `),
        ).rejects.toThrow();
    });
});
