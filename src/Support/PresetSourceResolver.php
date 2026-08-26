<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

final class PresetSourceResolver
{
    private string $cssRoot;

    public function __construct(
        private readonly Filesystem $files,
        ?string $cssRoot = null,
    ) {
        $cssRoot = $this->normalize($cssRoot ?? dirname(__DIR__, 2).'/resources/css');
        $this->cssRoot = rtrim($this->normalize(realpath($cssRoot) ?: $cssRoot), '/');
    }

    /**
     * Resolve a preset entrypoint into ordered foundation imports and visual sources.
     *
     * @param  string[]|null  $selectedSources
     */
    public function resolve(string $entrypoint, ?array $selectedSources = null): PresetSource
    {
        $entrypoint = $this->normalize($entrypoint);
        $name = pathinfo($entrypoint, PATHINFO_FILENAME);

        if (! $this->files->isFile($entrypoint)) {
            throw new PresetSourceException("Preset [{$name}] entrypoint does not exist.");
        }

        $entrypoint = $this->normalize(realpath($entrypoint) ?: $entrypoint);

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

        if ($selectedSources === null) {
            return new PresetSource(
                $name,
                $foundations,
                $visualStylesheets,
                array_map($this->relative(...), $visualPaths),
            );
        }

        if (in_array($entrypoint, $visualPaths, true)) {
            throw new PresetSourceException("Selective preset [{$name}] entrypoint must contain only imports.");
        }

        $positions = array_flip(array_map($this->relative(...), $visualPaths));
        $lastPosition = -1;

        foreach ($selectedSources as $selectedSource) {
            $position = $positions[$selectedSource] ?? null;

            if ($position === null) {
                throw new PresetSourceException(
                    "Selected visual source [{$selectedSource}] is not imported by preset [{$name}]."
                );
            }

            if ($position <= $lastPosition) {
                throw new PresetSourceException(
                    "Selected visual sources for preset [{$name}] do not follow canonical import order."
                );
            }

            $lastPosition = $position;
        }

        $selected = array_fill_keys($selectedSources, true);
        $selectedStylesheets = [];
        $selectedPaths = [];

        foreach ($visualStylesheets as $index => $stylesheet) {
            $path = $this->relative($visualPaths[$index]);

            if (isset($selected[$path])) {
                $selectedStylesheets[] = $stylesheet;
                $selectedPaths[] = $path;
            }
        }

        return new PresetSource($name, $foundations, $selectedStylesheets, $selectedPaths);
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
        $cycleAt = array_search($path, $stack, true);

        if ($cycleAt !== false) {
            $cycle = [...array_slice($stack, $cycleAt), $path];
            $chain = implode(' -> ', array_map($this->relative(...), $cycle));

            throw new PresetSourceException("CSS import cycle in preset [{$preset}]: {$chain}.");
        }

        if (isset($visited[$path])) {
            throw new PresetSourceException(
                "Preset [{$preset}] includes visual stylesheet [{$this->relative($path)}] more than once."
            );
        }

        $visited[$path] = true;
        $stack[] = $path;
        $css = $this->files->get($path);
        $imports = $this->imports($css);
        $hasVisualImport = false;

        foreach ($imports as $import) {
            if (! $import['local']) {
                throw new PresetSourceException("Preset [{$preset}] supports only local CSS imports.");
            }

            if ($import['conditions'] !== '') {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] uses unsupported import conditions."
                );
            }

            $target = $this->normalize(dirname($path).'/'.$import['path']);

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

            $target = $this->normalize(realpath($target) ?: $target);

            if (! $this->insideCssRoot($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] from [{$this->relative($path)}] leaves the package CSS directory."
                );
            }

            if ($this->isVisual($target)) {
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

            if (! $entrypoint) {
                throw new PresetSourceException(
                    "Preset [{$preset}] visual source [{$this->relative($path)}] cannot import shared foundations."
                );
            }

            if ($hasVisualImport) {
                throw new PresetSourceException("Preset [{$preset}] must import shared foundations before visual sources.");
            }

            $relative = $this->relative($target);

            if (! isset($foundationSet[$relative])) {
                $foundations[] = $relative;
                $foundationSet[$relative] = true;
            }
        }

        array_pop($stack);

        $visual = trim($this->removeImports($css, $imports));

        if ($visual !== '') {
            $visualStylesheets[] = $visual;
            $visualPaths[] = $path;
        }
    }

    /**
     * @return array<int, array{path: string, local: bool, conditions: string, offset: int, length: int}>
     */
    private function imports(string $css): array
    {
        $pattern = <<<'REGEX'
            ~
                (?:/\*.*?\*/|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')(*SKIP)(*F)
                |
                @import\s+(?:
                    (?<quote>["'])(?<quoted_path>[^"']+)\k<quote>
                    |
                    url\(\s*(?:
                        (?<url_quote>["'])(?<url_quoted_path>[^"']+)\k<url_quote>
                        |
                        (?<url_path>[^)\s]+)
                    )\s*\)
                )(?<conditions>[^;]*);
            ~sx
            REGEX;

        preg_match_all($pattern, $css, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

        $imports = [];

        foreach ($matches as $match) {
            $import = $match['quoted_path'][0] ?? $match['url_quoted_path'][0] ?? $match['url_path'][0];

            $imports[] = [
                'path' => $import,
                'local' => str_starts_with($import, '.'),
                'conditions' => trim($match['conditions'][0]),
                'offset' => $match[0][1],
                'length' => strlen($match[0][0]),
            ];
        }

        return $imports;
    }

    /**
     * @param  array<int, array{offset: int, length: int}>  $imports
     */
    private function removeImports(string $css, array $imports): string
    {
        foreach (array_reverse($imports) as $import) {
            $css = substr_replace($css, '', $import['offset'], $import['length']);
        }

        return $css;
    }

    private function isVisual(string $path): bool
    {
        return str_starts_with($this->comparable($path), $this->comparable($this->cssRoot.'/presets/'));
    }

    private function insideCssRoot(string $path): bool
    {
        $path = $this->comparable($path);
        $root = $this->comparable($this->cssRoot);

        return $path === $root || str_starts_with($path, $root.'/');
    }

    private function relative(string $path): string
    {
        return ltrim(substr($path, strlen($this->cssRoot)), '/');
    }

    private function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = '';

        if (preg_match('/^([A-Za-z]:)(?:\/(.*))?$/', $path, $matches) === 1) {
            $prefix = strtoupper($matches[1]).'/';
            $path = $matches[2] ?? '';
        } elseif (str_starts_with($path, '//')) {
            $prefix = '//';
            $path = ltrim($path, '/');
        } elseif (str_starts_with($path, '/')) {
            $prefix = '/';
            $path = ltrim($path, '/');
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return $prefix.implode('/', $segments);
    }

    private function comparable(string $path): string
    {
        return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '//')
            ? strtolower($path)
            : $path;
    }
}
