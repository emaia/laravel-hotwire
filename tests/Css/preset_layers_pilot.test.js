import { beforeAll, describe, expect, test } from "bun:test";
import { readFile } from "node:fs/promises";
import { gzipSync } from "node:zlib";
import { compileCssFixture } from "../../scripts/css_build_contract.js";

const fixtures = {
    legacy: new URL("../Fixtures/css/preset_layers_legacy.css", import.meta.url),
    pilot: new URL("../Fixtures/css/preset_layers_pilot.css", import.meta.url),
};

const slotSelector = (slot) => new RegExp(`\\[data-slot=(?:["'])?${slot}(?:["'])?\\]`);
const count = (value, pattern) => value.match(pattern)?.length ?? 0;
const measure = (value) => ({
    rawBytes: Buffer.byteLength(value),
    gzipBytes: gzipSync(value, { level: 9 }).byteLength,
});

let source;
let output;

beforeAll(async () => {
    source = {
        legacy: await readFile(fixtures.legacy, "utf8"),
        pilot: await readFile(fixtures.pilot, "utf8"),
    };
    output = {
        legacy: await compileCssFixture(source.legacy),
        pilot: await compileCssFixture(source.pilot),
    };
});

describe("preset-local variables and semantic layers pilot", () => {
    test("compiles equivalent Button and form-control surfaces", () => {
        for (const css of Object.values(output)) {
            expect(css).toMatch(slotSelector("button"));
            expect(css).toMatch(slotSelector("input"));
            expect(css).toMatch(slotSelector("select"));
            expect(css).toMatch(slotSelector("textarea"));
        }

        expect(output.pilot).toContain("components.nova.base");
        expect(output.pilot).toContain("components.nova.variant");
        expect(output.pilot).toContain("components.nova.state");
        expect(output.pilot).toContain("components.nova.motion");
        expect(output.pilot).toContain("--nova-button-background");
        expect(output.pilot).toContain("--nova-control-border");
    });

    test("centralizes shared states without a material gzip regression", () => {
        expect(count(source.pilot, /:focus-visible/g)).toBeLessThan(count(source.legacy, /focus-visible:/g));
        expect(count(source.pilot, /\[aria-invalid="true"\]/g)).toBeLessThan(count(source.legacy, /aria-invalid:/g));

        const legacy = measure(output.legacy);
        const pilot = measure(output.pilot);

        expect(pilot.rawBytes).toBeLessThan(legacy.rawBytes);
        expect(pilot.gzipBytes).toBeLessThanOrEqual(Math.ceil(legacy.gzipBytes * 1.05));
    });

    test("keeps preset scope and alternate visual structure local", () => {
        expect(source.pilot).toContain('[data-preset="compact"]');
        expect(source.pilot).toContain("@layer components.poster");
        expect(source.pilot).not.toContain("@theme");
        expect(source.pilot).not.toContain("--button-background");
        expect(source.pilot).not.toContain("--control-border");
    });
});
