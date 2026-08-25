import { beforeAll, describe, expect, test } from "bun:test";
import baselines from "./preset_build_baselines.json";
import { buildCssContract, compileCssFixture } from "../../scripts/css_build_contract.js";

const slotSelector = (slot) => new RegExp(`\\[data-slot=(?:["'])?${slot}(?:["'])?\\]`);
const carouselMechanic = /\[data-carousel-container\]/;
const unresolvedDirective = /@(import|apply|theme|custom-variant|source|utility|variant|reference|config|plugin)\b/;

let contract;

beforeAll(async () => {
    contract = await buildCssContract();
});

describe("public CSS presets", () => {
    test("compile every discovered preset through an installed-app entrypoint", () => {
        expect(Object.keys(contract.outputs.presets).sort()).toEqual(Object.keys(baselines.presets).sort());

        for (const css of Object.values(contract.outputs.presets)) {
            expect(css).toContain("--color-background:");
            expect(css).toMatch(slotSelector("button"));
            expect(css).toMatch(carouselMechanic);
            expect(css).not.toMatch(unresolvedDirective);
        }
    });

    test("compiles a selective fixture without omitted visual slots", () => {
        const css = contract.outputs.selective;

        expect(css).toContain("--color-background:");
        expect(css).toMatch(slotSelector("button"));
        expect(css).toMatch(slotSelector("card"));
        expect(css).toMatch(carouselMechanic);
        expect(css).not.toMatch(slotSelector("badge"));
        expect(css).not.toMatch(unresolvedDirective);
    });

    test("reports raw and gzip sizes against non-blocking baselines", () => {
        expect(contract.measurements.tailwindVersion).toBe(baselines.tailwindVersion);

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
