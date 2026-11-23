#!/usr/bin/env bash
# Package the official Stimulus development build without rebuilding its bundles.
#
# Usage: scripts/build_stimulus_artifact.sh <source-sha> <dev-build-sha> [output-dir] [revision]
# Output: stimulus-<sha12>[-rN]/ containing the tarball, checksum, and provenance.
# Run one build at a time per output directory; do not modify it while building.
# Publish as a GitHub prerelease, never as Latest; never replace an existing asset.

set -euo pipefail

fail() {
    echo "error: $*" >&2
    exit 1
}

source_sha="${1:-}"
artifact_sha="${2:-}"
revision="${4:-}"
[[ $# -le 4 && "$source_sha" =~ ^[0-9a-f]{40}$ && "$artifact_sha" =~ ^[0-9a-f]{40}$ ]] ||
    fail "pass the full lowercase commit SHAs of hotwired/stimulus and hotwired/dev-builds"
[[ -z "$revision" || "$revision" =~ ^[1-9][0-9]*$ ]] || fail "revision must be a positive integer"

for command in git node npm tar cmp; do
    command -v "$command" >/dev/null || fail "$command is required"
done

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
out="${3:-$root/build/stimulus}"
short="${source_sha:0:12}"
tag="stimulus-$short${revision:+-r$revision}"
name="hotwired-$tag"
assets=("$name.tgz" "$name.tgz.sha256" "$name.json")
destination="$out/$tag"
work=""
stage=""
finalizing=0
destination_exists=73

cleanup() {
    local status=$?
    # A successful rename removes the staging path even if a signal skips the state reset.
    if (( finalizing && status != 0 && status != destination_exists )) && [[ -d "$stage" && -d "$destination" ]]; then
        echo "Inspect $destination before retrying. Verify the three assets and the checksum; remove it manually only if it is an incomplete local build." >&2
    fi
    [[ -z "$stage" ]] || rm -rf -- "$stage"
    [[ -z "$work" ]] || rm -rf -- "$work"
}
trap cleanup EXIT

ancestor="$out"
while [[ "$ancestor" != / && "$ancestor" == */ ]]; do
    ancestor="${ancestor%/}"
done
while [[ ! -e "$ancestor" ]]; do
    [[ ! -L "$ancestor" ]] || fail "output path is a dangling symlink: $ancestor"
    ancestor="$(dirname -- "$ancestor")"
done
[[ -d "$ancestor" ]] || fail "output path is not a directory: $ancestor"
[[ -w "$ancestor" && -x "$ancestor" ]] || fail "output directory cannot be written or traversed: $ancestor"

if [[ -e "$destination" || -L "$destination" ]]; then
    echo "error: refusing to overwrite $destination" >&2
    exit "$destination_exists"
fi

work="$(mktemp -d)"

fetch_commit() {
    local repo="$1" sha="$2" directory="$3"
    git init -q "$directory"
    git -C "$directory" fetch -q --depth 1 "https://github.com/$repo.git" "$sha"
    [[ "$(git -C "$directory" rev-parse FETCH_HEAD)" == "$sha" ]] || fail "$repo did not resolve to $sha"
}

echo "→ Fetching Stimulus source and development build"
fetch_commit hotwired/stimulus "$source_sha" "$work/source"
fetch_commit hotwired/dev-builds "$artifact_sha" "$work/artifact"
source_url="https://github.com/hotwired/stimulus/tree/$source_sha"
git -C "$work/artifact" log -1 --format=%B FETCH_HEAD | grep -Fx "$source_url" >/dev/null ||
    fail "the development build does not identify Stimulus source commit $source_sha"

package="$work/package"
mkdir -p "$package"
git -C "$work/artifact" archive FETCH_HEAD package.json dist | tar -xf - -C "$package"
git -C "$work/source" archive FETCH_HEAD LICENSE.md README.md | tar -xf - -C "$package"

echo "→ Preparing published files and manifest"
node --input-type=module - "$package" "$source_sha" "$short${revision:+.r$revision}" <<'JS'
import { readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const [directory, gitHead, identifier] = process.argv.slice(2);
const manifestPath = join(directory, "package.json");
const fail = (message) => {
    console.error(message);
    process.exit(1);
};
let manifest;
try {
    manifest = JSON.parse(readFileSync(manifestPath, "utf8"));
} catch (error) {
    fail(`Could not read the Stimulus manifest (${error.code ?? error.name}). Review the upstream build layout.`);
}
if (manifest?.name !== "@hotwired/stimulus" || typeof manifest.version !== "string" || !/^\d+\.\d+\.\d+$/.test(manifest.version)) {
    fail("Expected the official @hotwired/stimulus manifest and a stable upstream version.");
}
const expected = {
    main: "dist/stimulus.umd.js",
    module: "dist/stimulus.js",
    types: "dist/types/index.d.ts",
};
for (const [key, path] of Object.entries(expected)) {
    if (manifest[key] !== path) {
        fail(`Unexpected Stimulus ${key} entry: ${manifest[key]}. Review the upstream build layout.`);
    }
    let isFile = false;
    try {
        isFile = statSync(join(directory, path)).isFile();
    } catch (error) {
        if (error.code !== "ENOENT") {
            fail(`Could not inspect Stimulus ${key} file: ${path} (${error.code}). Review the upstream build layout.`);
        }
    }
    if (!isFile) {
        fail(`Missing Stimulus ${key} file: ${path}. Review the upstream build layout.`);
    }
}
rmSync(join(directory, "dist/types/tests"), { recursive: true, force: true });
delete manifest.scripts;
delete manifest.devDependencies;
manifest.files = [expected.main, expected.module, "dist/types/**/*", "LICENSE.md", "README.md"];
manifest.version += `+hotwire.${identifier}`;
manifest.gitHead = gitHead;
writeFileSync(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`);
JS

echo "→ Packing $name.tgz"
npm pack "$package" --ignore-scripts --pack-destination "$work" --json > "$work/pack.json"
filename="$(node -p 'require(process.argv[1])[0].filename' "$work/pack.json")"
mv "$work/$filename" "$work/$name.tgz"

contents="$(tar -tzf "$work/$name.tgz")"
for entry in package.json dist/stimulus.js dist/stimulus.umd.js dist/types/index.d.ts LICENSE.md README.md; do
    grep -Fx "package/$entry" <<<"$contents" >/dev/null || fail "tarball is missing $entry"
    tar -xzOf "$work/$name.tgz" "package/$entry" | cmp -s "$package/$entry" - ||
        fail "tarball contents differ from the staged $entry"
done
if grep -q '^package/dist/types/tests/' <<<"$contents"; then
    fail "tarball contains test declarations"
fi

checksum="$(node --input-type=module - "$work" "$package" "$source_sha" "$artifact_sha" "$name" "$(npm --version)" <<'JS'
import { createHash } from "node:crypto";
import { readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const [work, directory, commit, artifact, name, npm] = process.argv.slice(2);
const manifest = JSON.parse(readFileSync(join(directory, "package.json"), "utf8"));
const sha256Of = (file) => createHash("sha256").update(readFileSync(file)).digest("hex");
const sha256 = sha256Of(join(work, `${name}.tgz`));
const bundle_sha256 = Object.fromEntries([manifest.module, manifest.main].map(path => [
    path, sha256Of(join(directory, path)),
]));
const provenance = {
    package: manifest.name,
    version: manifest.version,
    commit,
    source: `https://github.com/hotwired/stimulus/tree/${commit}`,
    upstream_build: {
        commit: artifact,
        source: `https://github.com/hotwired/dev-builds/tree/${artifact}`,
    },
    patches: [],
    packaging: { command: "npm pack --ignore-scripts", node: process.versions.node, npm },
    asset: `${name}.tgz`,
    sha256,
    bundle_sha256,
};
writeFileSync(join(work, `${name}.tgz.sha256`), `${sha256}  ${name}.tgz\n`);
writeFileSync(join(work, `${name}.json`), `${JSON.stringify(provenance, null, 4)}\n`);
console.log(sha256);
JS
)"

mkdir -p -- "$out" || fail "could not create output directory: $out"
out="$(CDPATH= cd -- "$out" && pwd -P)"
destination="${out%/}/$tag"
stage="$(mktemp -d "${out%/}/.$tag.XXXXXX")"

# Keep staging and the destination on the same filesystem so the complete set appears at once.
finalizing=1
# Wait for local finalization before cleanup. Do not defer signals during network fetches.
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP
node --input-type=module - "$work" "$stage" "$destination" "$destination_exists" "${assets[@]}" <<'JS'
import { chmodSync, copyFileSync, lstatSync, renameSync } from "node:fs";
import { join } from "node:path";

const [work, stage, destination, destinationExists, ...assets] = process.argv.slice(2);
const fail = (message, status = 1) => {
    console.error(`error: ${message}`);
    process.exit(status);
};
for (const file of assets) {
    try {
        const stagedFile = join(stage, file);
        copyFileSync(join(work, file), stagedFile);
        chmodSync(stagedFile, 0o644);
    } catch (error) {
        fail(`could not stage ${file} (${error.code ?? error.name}): ${error.message}`);
    }
}

try {
    chmodSync(stage, 0o755);
    // This check relies on the documented single-writer output directory.
    if (lstatSync(destination, { throwIfNoEntry: false })) {
        fail(`refusing to overwrite ${destination}`, Number(destinationExists));
    }
    renameSync(stage, destination);
} catch (error) {
    fail(`could not finalize ${destination} (${error.code ?? error.name}): ${error.message}`);
}
JS
finalizing=0
trap - INT TERM HUP

echo "Built $destination/$name.tgz (SHA-256: $checksum)"
echo "After approval, publish the three assets as prerelease $tag (gh release create --prerelease --latest=false)."
