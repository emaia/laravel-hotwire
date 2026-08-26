import { cp, mkdir, mkdtemp, readFile, readdir, rm, symlink, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import { gzipSync } from "node:zlib";

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const baselinePath = join(root, "tests/Css/preset_build_baselines.json");
const presetDirectory = join(root, "resources/css/presets");
const tailwindBinary = join(root, "node_modules/.bin/tailwindcss");
const packageSourceFixtures = [
    ["package_source.blade.php", "resources/views/css_build_contract.blade.php"],
    ["package_source.php", "src/css_build_contract.php"],
    ["package_source.js", "resources/js/css_build_contract.js"],
];
const automaticSourceFixture = '<div class="w-[811px]"></div>\n';
const unresolvedDirective = /@(import|apply|theme|custom-variant|source|utility|variant|reference|config|plugin)\b/;

async function createInstalledAppFixture() {
    const directory = await mkdtemp(join(tmpdir(), "hotwire-css-build-"));
    const packageDirectory = join(directory, "vendor/emaia/laravel-hotwire");
    const packageResources = join(packageDirectory, "resources");

    await mkdir(join(directory, "resources/css"), { recursive: true });
    await mkdir(join(directory, "resources/views"), { recursive: true });
    await mkdir(packageDirectory, { recursive: true });
    await mkdir(join(directory, "dist"), { recursive: true });
    await Promise.all([
        cp(join(root, "resources/css"), join(packageResources, "css"), { recursive: true }),
        cp(join(root, "resources/js"), join(packageResources, "js"), { recursive: true }),
        cp(join(root, "resources/views"), join(packageResources, "views"), { recursive: true }),
        cp(join(root, "src"), join(packageDirectory, "src"), { recursive: true }),
    ]);
    await symlink(join(root, "node_modules"), join(directory, "node_modules"), "dir");
    await writeFile(join(directory, "resources/views/css_build_contract.blade.php"), automaticSourceFixture);
    await Promise.all(
        packageSourceFixtures.map(async ([source, destination]) => {
            await writeFile(
                join(packageDirectory, destination),
                await readFile(join(root, "tests/Fixtures/css", source), "utf8"),
            );
        }),
    );

    return directory;
}

async function runTailwind(directory) {
    const process = Bun.spawn(
        [tailwindBinary, "--input", "resources/css/app.css", "--output", "dist/app.css", "--minify"],
        {
            cwd: directory,
            env: processEnv(),
            stdout: "pipe",
            stderr: "pipe",
        },
    );
    const [exitCode, stdout, stderr] = await Promise.all([
        process.exited,
        new Response(process.stdout).text(),
        new Response(process.stderr).text(),
    ]);

    if (exitCode !== 0) {
        throw new Error(`Tailwind CSS build failed (exit ${exitCode}).\n${stderr || stdout}`);
    }

    const css = await readFile(join(directory, "dist/app.css"), "utf8");

    if (unresolvedDirective.test(css)) {
        throw new Error("Tailwind CSS build left an unresolved source directive.");
    }

    return css;
}

function processEnv() {
    return {
        ...globalThis.process.env,
        NO_UPDATE_NOTIFIER: "1",
    };
}

function measure(css) {
    return {
        rawBytes: Buffer.byteLength(css),
        gzipBytes: gzipSync(css, { level: 9 }).byteLength,
    };
}

async function publicPresetNames() {
    const entries = await readdir(presetDirectory, { withFileTypes: true });

    return entries
        .filter((entry) => entry.isFile() && entry.name.endsWith(".css"))
        .map((entry) => entry.name.slice(0, -4))
        .sort();
}

async function appEntrypointFor(preset) {
    const stub = await readFile(join(root, "stubs/resources/css/app.css"), "utf8");

    return replacePresetImport(stub, preset);
}

export function replacePresetImport(stub, preset) {
    const presetImport = /@import\s+["'][^"']*\/resources\/css\/presets\/[^"']+\.css["']\s*;/g;
    const imports = stub.match(presetImport) ?? [];

    if (imports.length !== 1) {
        throw new Error(`Expected exactly one public preset import in the app stub, found ${imports.length}.`);
    }

    return stub.replace(imports[0], imports[0].replace(/presets\/[^"']+\.css/, `presets/${preset}.css`));
}

export function disableAutomaticSources(entrypoint) {
    const tailwindImport = /@import\s+(["'])tailwindcss\1[^;]*;/g;
    const imports = entrypoint.match(tailwindImport) ?? [];

    if (imports.length !== 1) {
        throw new Error(`Expected exactly one Tailwind import in the CSS entrypoint, found ${imports.length}.`);
    }

    if (/\bsource\(none\)/.test(imports[0])) {
        return entrypoint;
    }

    if (/\bsource\(/.test(imports[0])) {
        throw new Error("The Tailwind import already configures an incompatible source.");
    }

    const replacement = imports[0].replace(/(["']tailwindcss["'])/, "$1 source(none)");

    if (replacement === imports[0]) {
        throw new Error("Could not disable automatic Tailwind source detection.");
    }

    return entrypoint.replace(imports[0], replacement);
}

export async function compileCssFixture(entrypoint) {
    const directory = await createInstalledAppFixture();

    try {
        // Keep the fixture deterministic and make explicit package sources observable.
        await writeFile(join(directory, "resources/css/app.css"), disableAutomaticSources(entrypoint));

        return await runTailwind(directory);
    } finally {
        await rm(directory, { recursive: true, force: true });
    }
}

export async function buildCssContract() {
    const presets = await publicPresetNames();

    if (presets.length === 0) {
        throw new Error("No public CSS presets were found.");
    }

    const presetOutputs = {};
    const presetMeasurements = {};

    for (const preset of presets) {
        const css = await compileCssFixture(await appEntrypointFor(preset));
        presetOutputs[preset] = css;
        presetMeasurements[preset] = measure(css);
    }

    const selective = await compileCssFixture(await readFile(join(root, "tests/Fixtures/css/selective.css"), "utf8"));
    const [tailwindPackage, cliPackage] = await Promise.all([
        readPackage("tailwindcss"),
        readPackage("@tailwindcss/cli"),
    ]);

    return {
        outputs: {
            presets: presetOutputs,
            selective,
        },
        measurements: {
            toolchain: {
                tailwindcss: tailwindPackage.version,
                cli: cliPackage.version,
            },
            presets: presetMeasurements,
            selective: measure(selective),
        },
    };
}

async function readPackage(name) {
    return JSON.parse(await readFile(join(root, "node_modules", name, "package.json"), "utf8"));
}

export async function resolveBaselines(measurements, options = {}) {
    const path = options.path ?? baselinePath;

    if (options.update) {
        await writeFile(path, `${JSON.stringify(measurements, null, 4)}\n`);

        return measurements;
    }

    return JSON.parse(await readFile(path, "utf8"));
}

export function measurementRows(measurements, baselines = {}) {
    const row = (build, measurement, baseline) => ({
        build,
        tailwindcss: measurements.toolchain.tailwindcss,
        cli: measurements.toolchain.cli,
        ...measurement,
        rawDelta: measurement.rawBytes - (baseline?.rawBytes ?? measurement.rawBytes),
        gzipDelta: measurement.gzipBytes - (baseline?.gzipBytes ?? measurement.gzipBytes),
    });

    return [
        ...Object.entries(measurements.presets).map(([name, measurement]) =>
            row(`preset:${name}`, measurement, baselines.presets?.[name]),
        ),
        row("selective", measurements.selective, baselines.selective),
    ];
}

async function run() {
    const contract = await buildCssContract();
    const baselines = await resolveBaselines(contract.measurements, {
        update: globalThis.process.argv.includes("--update-baselines"),
    });

    console.table(measurementRows(contract.measurements, baselines));
}

if (import.meta.main) {
    await run();
}
