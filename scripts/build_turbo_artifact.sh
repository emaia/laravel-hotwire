#!/usr/bin/env bash
# Build @hotwired/turbo from an exact upstream commit and pack it as a tarball
# that package managers can install from a URL.
#
# Usage: scripts/build_turbo_artifact.sh <full-commit-sha> [output-dir]
#
# The output directory (default: build/turbo) receives
#   hotwired-turbo-<sha12>.tgz         the installable package
#   hotwired-turbo-<sha12>.tgz.sha256  its checksum
#   hotwired-turbo-<sha12>.json        build provenance (sha, Bun version, checksums)
#
# Upload the tarball as an asset of a dedicated prerelease named turbo-<sha12>;
# never replace an existing asset, so older app lockfiles keep resolving the
# exact bytes they recorded.

set -euo pipefail

UPSTREAM="https://github.com/hotwired/turbo.git"

fail() {
    echo "error: $*" >&2
    exit 1
}

sha="${1:-}"
[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || fail "pass the full 40-character lowercase commit SHA of hotwired/turbo"

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
out="$(mkdir -p "${2:-$root/build/turbo}" && cd "${2:-$root/build/turbo}" && pwd)"
short="${sha:0:12}"
name="hotwired-turbo-$short"
tarball="$out/$name.tgz"

command -v bun >/dev/null || fail "bun is required"
command -v git >/dev/null || fail "git is required"

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

echo "→ Fetching hotwired/turbo@$sha"
git -C "$work" init -q src
git -C "$work/src" remote add origin "$UPSTREAM"
git -C "$work/src" fetch -q --depth 1 origin "$sha"
git -C "$work/src" -c advice.detachedHead=false checkout -q FETCH_HEAD
[[ "$(git -C "$work/src" rev-parse HEAD)" == "$sha" ]] || fail "checked out commit does not match $sha"

cd "$work/src"

# The build recipe below matches upstream's rollup.config.js and yarn.lock
# layout; refuse to guess when a commit changes either.
[[ -f rollup.config.js ]] || fail "rollup.config.js not found; the upstream build changed"
[[ -f yarn.lock ]] || fail "yarn.lock not found; the upstream lockfile changed"

manifest() {
    bun -e "const p = require('./package.json'); console.log(p$1 ?? '')"
}

[[ "$(manifest .name)" == "@hotwired/turbo" ]] || fail "package.json does not declare @hotwired/turbo"
main="$(manifest .main)"
module="$(manifest .module)"
version="$(manifest .version)"
license_file="$(ls MIT-LICENSE LICENSE LICENSE.md 2>/dev/null | head -n 1 || true)"
[[ -n "$license_file" ]] || fail "no license file found upstream"

echo "→ Installing build dependencies from yarn.lock (Bun $(bun --version))"
bun install --frozen-lockfile

echo "→ Compiling with Rollup"
bun --bun run rollup -c

[[ -f "$main" && -f "$module" ]] || fail "build did not produce $main and $module"

# Upstream's "files" list omits the license, so the npm tarball ships without
# it. Redistributed builds must carry the MIT notice.
bun -e "
const fs = require('fs');
const p = JSON.parse(fs.readFileSync('package.json', 'utf8'));
if (!p.files.includes('$license_file')) p.files.push('$license_file');
fs.writeFileSync('package.json', JSON.stringify(p, null, 2) + '\n');
"

echo "→ Packing $name.tgz"
rm -f "$tarball"
bun pm pack --filename "$tarball" >/dev/null

echo "→ Inspecting tarball"
contents="$(tar -tzf "$tarball")"
for entry in package/package.json "package/$main" "package/$module" "package/$license_file"; do
    grep -qx "$entry" <<<"$contents" || fail "tarball is missing $entry"
done
packed_name="$(tar -xzOf "$tarball" package/package.json | bun -e "console.log(JSON.parse(await Bun.stdin.text()).name)")"
[[ "$packed_name" == "@hotwired/turbo" ]] || fail "packed manifest declares $packed_name"

checksum="$(sha256sum "$tarball" | cut -d' ' -f1)"
echo "$checksum  $name.tgz" > "$tarball.sha256"

cat > "$out/$name.json" <<JSON
{
    "package": "@hotwired/turbo",
    "version": "$version",
    "commit": "$sha",
    "source": "https://github.com/hotwired/turbo/tree/$sha",
    "bun": "$(bun --version)",
    "built_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "sha256": "$checksum"
}
JSON

echo
echo "Built $tarball"
echo "  package  @hotwired/turbo $version ($sha)"
echo "  bun      $(bun --version)"
echo "  sha256   $checksum"
echo "  files"
sed 's/^/    /' <<<"$contents"
