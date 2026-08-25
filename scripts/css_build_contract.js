import { mkdir, mkdtemp, readFile, readdir, rm, symlink, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import { gzipSync } from "node:zlib";

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const baselinePath = join(root, "tests/Css/preset_build_baselines.json");
const presetDirectory = join(root, "resources/css/presets");
const tailwindBinary = join(root, "node_modules/.bin/tailwindcss");
const unresolvedDirective = /@(import|apply|theme|custom-variant|source|utility|variant|reference|config|plugin)\b/;

async function createInstalledAppFixture() {
    const directory = await mkdtemp(join(tmpdir(), "hotwire-css-build-"));
    const packageDirectory = join(directory, "vendor/emaia/laravel-hotwire");

    await mkdir(join(directory, "resources/css"), { recursive: true });
    await mkdir(dirname(packageDirectory), { recursive: true });
    await mkdir(join(directory, "dist"), { recursive: true });
    await symlink(root, packageDirectory, "dir");
    await symlink(join(root, "node_modules"), join(directory, "node_modules"), "dir");

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

    return stub.replace(/presets\/[^"']+\.css/, `presets/${preset}.css`);
}

export async function compileCssFixture(entrypoint) {
    const directory = await createInstalledAppFixture();

    try {
        await writeFile(join(directory, "resources/css/app.css"), entrypoint);

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
    const tailwindPackage = JSON.parse(await readFile(join(root, "node_modules/tailwindcss/package.json"), "utf8"));

    return {
        outputs: {
            presets: presetOutputs,
            selective,
        },
        measurements: {
            tailwindVersion: tailwindPackage.version,
            presets: presetMeasurements,
            selective: measure(selective),
        },
    };
}

async function run() {
    const contract = await buildCssContract();
    let baselines = JSON.parse(await readFile(baselinePath, "utf8"));

    if (globalThis.process.argv.includes("--update-baselines")) {
        await writeFile(baselinePath, `${JSON.stringify(contract.measurements, null, 4)}\n`);
        baselines = contract.measurements;
    }

    console.table([
        ...Object.entries(contract.measurements.presets).map(([name, measurement]) => ({
            build: `preset:${name}`,
            ...measurement,
            rawDelta: measurement.rawBytes - (baselines.presets[name]?.rawBytes ?? measurement.rawBytes),
            gzipDelta: measurement.gzipBytes - (baselines.presets[name]?.gzipBytes ?? measurement.gzipBytes),
        })),
        {
            build: "selective",
            ...contract.measurements.selective,
            rawDelta: contract.measurements.selective.rawBytes - baselines.selective.rawBytes,
            gzipDelta: contract.measurements.selective.gzipBytes - baselines.selective.gzipBytes,
        },
    ]);
}

if (import.meta.main) {
    await run();
}
