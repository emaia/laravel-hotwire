<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

final class PresetSourceResolver
{
    private string $cssRoot;

    private readonly FoundationFacade $foundationFacade;

    public function __construct(
        private readonly Filesystem $files,
        ?string $cssRoot = null,
        ?FoundationFacade $foundationFacade = null,
        private readonly CssImports $imports = new CssImports,
    ) {
        $cssRoot = CssPath::normalize($cssRoot ?? dirname(__DIR__, 2).'/resources/css');
        $this->cssRoot = rtrim(CssPath::normalize(realpath($cssRoot) ?: $cssRoot), '/');
        $this->foundationFacade = $foundationFacade ?? new FoundationFacade($files, $this->imports);
    }

    /** Return the CSS root used to resolve preset entrypoints and imports. */
    public function cssRoot(): string
    {
        return $this->cssRoot;
    }

    /**
     * Resolve a preset entrypoint into ordered foundation imports and visual sources.
     *
     * @param  string[]|null  $selectedSources
     * @param  string[]  $baseSources
     */
    public function resolve(string $entrypoint, ?array $selectedSources = null, array $baseSources = []): PresetSource
    {
        $entrypoint = CssPath::normalize($entrypoint);
        $name = pathinfo($entrypoint, PATHINFO_FILENAME);

        if (! $this->files->isFile($entrypoint)) {
            throw new PresetSourceException("Preset [{$name}] entrypoint does not exist.");
        }

        $entrypoint = CssPath::normalize(realpath($entrypoint) ?: $entrypoint);

        if (! $this->insideCssRoot($entrypoint)) {
            throw new PresetSourceException("Preset [{$name}] entrypoint leaves the package CSS directory.");
        }

        $foundations = [];
        $foundationSet = [];
        $visualStylesheets = [];
        $visualPaths = [];
        $visited = [];
        $stack = [];

        $this->walk(
            path: $entrypoint,
            preset: $name,
            foundations: $foundations,
            foundationSet: $foundationSet,
            visualStylesheets: $visualStylesheets,
            visualPaths: $visualPaths,
            visited: $visited,
            stack: $stack,
            entrypoint: true,
        );

        if ($selectedSources !== null && in_array($entrypoint, $visualPaths, true)) {
            throw new PresetSourceException("Selective preset [{$name}] entrypoint must contain only imports.");
        }

        $source = new PresetSource(
            $name,
            $foundations,
            $visualStylesheets,
            array_map($this->relative(...), $visualPaths),
            $baseSources,
        );

        return $selectedSources === null ? $source : $source->select($selectedSources);
    }

    /**
     * @param  string[]  $foundations
     * @param  array<string, true>  $foundationSet
     * @param  string[]  $visualStylesheets
     * @param  string[]  $visualPaths
     * @param  array<string, true>  $visited
     * @param  string[]  $stack
     */
    private function walk(
        string $path,
        string $preset,
        array &$foundations,
        array &$foundationSet,
        array &$visualStylesheets,
        array &$visualPaths,
        array &$visited,
        array &$stack,
        bool $entrypoint = false,
    ): void {
        if (isset($visited[$path])) {
            throw new PresetSourceException(
                "Preset [{$preset}] includes visual stylesheet [{$this->relative($path)}] more than once."
            );
        }

        $visited[$path] = true;
        $stack[] = $path;
        $css = $this->files->get($path);
        $css = str_starts_with($css, "\xEF\xBB\xBF") ? substr($css, 3) : $css;
        $imports = $this->imports->parse($css);
        $visual = trim($this->imports->remove($css, $imports));

        if ($this->imports->contains($visual)) {
            throw new PresetSourceException(
                "Preset [{$preset}] contains a malformed or misplaced @import in [{$this->relative($path)}]."
            );
        }

        $this->rejectReorderedPrelude($css, $imports, $path, $preset);
        $hasVisualImport = false;

        foreach ($imports as $import) {
            if (str_contains($import['path'], '\\')) {
                throw new PresetSourceException("Preset [{$preset}] import paths cannot contain CSS escapes.");
            }

            if (! str_starts_with($import['path'], '.')) {
                throw new PresetSourceException("Preset [{$preset}] supports only local CSS imports.");
            }

            if ($import['conditions'] !== '') {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] uses unsupported import conditions."
                );
            }

            $target = CssPath::normalize(dirname($path).'/'.$import['path']);

            if (! $this->insideCssRoot($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] from [{$this->relative($path)}] leaves the package CSS directory."
                );
            }

            if (! $this->files->isFile($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] cannot resolve local import [{$import['path']}] from [{$this->relative($path)}]."
                );
            }

            $target = CssPath::normalize(realpath($target) ?: $target);

            if (! $this->insideCssRoot($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] from [{$this->relative($path)}] leaves the package CSS directory."
                );
            }

            $this->rejectImportCycle($target, $preset, $stack);

            if ($this->isVisual($target, $preset)) {
                $hasVisualImport = true;
                $this->walk(
                    path: $target,
                    preset: $preset,
                    foundations: $foundations,
                    foundationSet: $foundationSet,
                    visualStylesheets: $visualStylesheets,
                    visualPaths: $visualPaths,
                    visited: $visited,
                    stack: $stack,
                );

                continue;
            }

            if ($this->insidePresetsRoot($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] cannot import stylesheet [{$this->relative($target)}] outside [presets/{$preset}/]."
                );
            }

            if (! $entrypoint) {
                throw new PresetSourceException(
                    "Preset [{$preset}] visual source [{$this->relative($path)}] cannot import shared foundations."
                );
            }

            if ($hasVisualImport) {
                throw new PresetSourceException("Preset [{$preset}] must import shared foundations before visual sources.");
            }

            $relative = $this->relative($target);

            if (isset($foundationSet[$relative])) {
                throw new PresetSourceException(
                    "Preset [{$preset}] imports shared foundation [{$relative}] more than once."
                );
            }

            if ($relative === 'foundation.css') {
                $this->foundationFacade->validate($target, $this->cssRoot, $preset);
            }

            $foundations[] = $relative;
            $foundationSet[$relative] = true;
        }

        array_pop($stack);

        if ($visual !== '') {
            $visualStylesheets[] = $visual;
            $visualPaths[] = $path;
        }
    }

    /** @param string[] $stack */
    private function rejectImportCycle(string $path, string $preset, array $stack): void
    {
        $cycleAt = array_search($path, $stack, true);

        if ($cycleAt === false) {
            return;
        }

        $cycle = [...array_slice($stack, $cycleAt), $path];
        $chain = implode(' -> ', array_map($this->relative(...), $cycle));

        throw new PresetSourceException("CSS import cycle in preset [{$preset}]: {$chain}.");
    }

    /**
     * Reject legal preludes whose position would change when imports are emitted first.
     *
     * @param  list<array{offset: int, length: int}>  $imports
     */
    private function rejectReorderedPrelude(string $css, array $imports, string $path, string $preset): void
    {
        if ($imports === []) {
            return;
        }

        $last = $imports[array_key_last($imports)];
        $prefix = $this->imports->remove(substr($css, 0, $last['offset'] + $last['length']), $imports);
        $prefix = preg_replace('~/\*.*?\*/~s', '', $prefix) ?? $prefix;

        if (trim($prefix) !== '') {
            throw new PresetSourceException(
                "Preset [{$preset}] cannot flatten CSS prelude rules before imports in [{$this->relative($path)}]."
            );
        }
    }

    private function isVisual(string $path, string $preset): bool
    {
        $root = $this->cssRoot."/presets/{$preset}";

        return CssPath::comparable($path) !== CssPath::comparable($root)
            && CssPath::contains($root, $path);
    }

    private function insidePresetsRoot(string $path): bool
    {
        return CssPath::contains($this->cssRoot.'/presets', $path);
    }

    private function insideCssRoot(string $path): bool
    {
        return CssPath::contains($this->cssRoot, $path);
    }

    private function relative(string $path): string
    {
        return ltrim(substr($path, strlen($this->cssRoot)), '/');
    }
}
