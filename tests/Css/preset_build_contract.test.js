import { beforeAll, describe, expect, test } from "bun:test";
import { mkdtemp, readFile, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { join } from "node:path";
import baselines from "./preset_build_baselines.json";
import {
    buildCssContract,
    compileCssFixture,
    measurementRows,
    replacePresetImport,
    resolveBaselines,
} from "../../scripts/css_build_contract.js";

const slotSelector = (slot) => new RegExp(`\\[data-slot=(?:["'])?${slot}(?:["'])?\\]`);
const carouselMechanic = /\[data-carousel-container\]/;
const unresolvedDirective = /@(import|apply|theme|custom-variant|source|utility|variant|reference|config|plugin)\b/;
const scannedPackageUtility = String.raw`.min-h-\[137px\]`;

let contract;

beforeAll(async () => {
    contract = await buildCssContract();
});

describe("public CSS presets", () => {
    test("compile every discovered preset through an installed-app entrypoint", () => {
        expect(Object.keys(contract.outputs.presets).length).toBeGreaterThan(0);

        for (const css of Object.values(contract.outputs.presets)) {
            expect(css).toContain("--background:");
            expect(css).toContain("--radius-md:");
            expect(css).toMatch(slotSelector("button"));
            expect(css).toMatch(carouselMechanic);
            expect(css).not.toMatch(unresolvedDirective);
        }
    });

    test("compiles a selective fixture without omitted visual slots", () => {
        const css = contract.outputs.selective;

        expect(css).toContain("--background:");
        expect(css).toContain("--radius-md:");
        expect(css).toMatch(slotSelector("button"));
        expect(css).toMatch(slotSelector("card"));
        expect(css).toMatch(carouselMechanic);
        expect(css).toContain(scannedPackageUtility);
        expect(css).not.toMatch(slotSelector("badge"));
        expect(css).not.toMatch(unresolvedDirective);
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

    test("requires explicit package sources to discover vendor candidates", async () => {
        const fixture = await readFile(new URL("../Fixtures/css/selective.css", import.meta.url), "utf8");
        const css = await compileCssFixture(fixture.replace(/^@source .*;\n/gm, ""));

        expect(css).not.toContain(scannedPackageUtility);
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
        await expect(compileCssFixture('@import "./missing-stylesheet.css";')).rejects.toThrow();
    });
});
