<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Filesystem\Filesystem;

/** @internal */
final readonly class ApplicationPresetValidator
{
    private const array REQUIRED_FOUNDATIONS = [
        'tokens.css',
        'custom-variants.css',
        'structural.css',
    ];

    public function __construct(
        private Filesystem $files,
        private CssImports $imports,
        private CssSlots $slots,
        private PresetAxes $axes,
    ) {}

    /**
     * Validate an application-owned complete preset against the preset-neutral public contract.
     *
     * @return array{errors: string[], warnings: string[], styledSlots: string[], referencedSlots: string[]}
     */
    public function validate(string $entrypoint, HotwireRegistry $registry, ?string $cssRoot = null): array
    {
        $name = pathinfo($entrypoint, PATHINFO_FILENAME);
        $cssRootPath = $cssRoot ?? resource_path('css');
        $cssRoot = $this->canonical(realpath($cssRootPath) ?: $cssRootPath);
        $entrypoint = $this->canonical(realpath($entrypoint) ?: $entrypoint);
        $errors = [];
        $warnings = [];
        $visual = [];
        $foundations = [];
        $visited = [];
        $stack = [];

        if (! $this->files->isFile($entrypoint)) {
            return $this->failed(["Application preset [{$name}] does not exist."]);
        }

        if (! $this->inside($entrypoint, $cssRoot)) {
            return $this->failed(['Application preset must be a CSS file under resources/css.']);
        }

        try {
            $this->walk(
                $entrypoint,
                $entrypoint,
                $cssRoot,
                $name,
                $foundations,
                $visual,
                $visited,
                $stack,
            );
        } catch (PresetSourceException $exception) {
            return $this->failed([$exception->getMessage()]);
        }

        if ($foundations !== self::REQUIRED_FOUNDATIONS) {
            $errors[] = "Preset [{$name}] must import package foundations once in this order: ".implode(', ', self::REQUIRED_FOUNDATIONS).'.';
        }

        $css = implode("\n\n", $visual);
        $coverage = $this->axes->coverage($css);

        if ($coverage['visited'] !== $coverage['total']) {
            $warnings[] = "Preset [{$name}] CSS analysis is incomplete ({$coverage['visited']} of {$coverage['total']} slot references parsed).";
        }

        return $this->result(
            $name,
            $errors,
            $warnings,
            $css,
            $registry,
            $coverage['visited'] === $coverage['total'] ? [] : $this->axes->unvisitedSlots($css),
        );
    }

    /**
     * @param  string[]  $foundations
     * @param  string[]  $visual
     * @param  array<string, true>  $visited
     * @param  string[]  $stack
     */
    private function walk(
        string $path,
        string $entrypoint,
        string $cssRoot,
        string $preset,
        array &$foundations,
        array &$visual,
        array &$visited,
        array &$stack,
    ): void {
        if (($cycleAt = array_search($path, $stack, true)) !== false) {
            $cycle = [...array_slice($stack, $cycleAt), $path];
            throw new PresetSourceException(
                "CSS import cycle in preset [{$preset}]: ".implode(' -> ', array_map(basename(...), $cycle)).'.'
            );
        }

        if (isset($visited[$path])) {
            throw new PresetSourceException("Preset [{$preset}] includes stylesheet [".basename($path).'] more than once.');
        }

        $visited[$path] = true;
        $stack[] = $path;
        $css = $this->files->get($path);
        $imports = $this->imports->parse($css);
        $hasVisualImport = false;

        foreach ($imports as $import) {
            if (str_contains($import['path'], '\\')) {
                throw new PresetSourceException("Preset [{$preset}] import paths cannot contain CSS escapes.");
            }

            $importPath = preg_replace('/[?#].*$/', '', $import['path']) ?? $import['path'];

            if (! str_starts_with($importPath, '.')) {
                throw new PresetSourceException("Preset [{$preset}] supports only local CSS imports.");
            }

            if ($import['conditions'] !== '') {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] uses unsupported import conditions."
                );
            }

            $target = $this->canonical(dirname($path).'/'.$importPath);

            if (! $this->files->isFile($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] cannot resolve local import [{$import['path']}] from [".basename($path).'].'
                );
            }

            $target = $this->canonical(realpath($target) ?: $target);
            $foundation = $this->foundation($target, $cssRoot);

            if ($foundation !== null) {
                if ($path !== $entrypoint) {
                    throw new PresetSourceException("Preset [{$preset}] foundations must be imported by its entrypoint.");
                }

                if ($hasVisualImport) {
                    throw new PresetSourceException(
                        "Preset [{$preset}] must import shared foundations before visual stylesheets."
                    );
                }

                $foundations[] = $foundation;

                continue;
            }

            if (! $this->inside($target, $cssRoot)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] local import [{$import['path']}] leaves the application CSS directory."
                );
            }

            $hasVisualImport = true;
            $this->walk($target, $entrypoint, $cssRoot, $preset, $foundations, $visual, $visited, $stack);
        }

        array_pop($stack);
        $stylesheet = trim($this->imports->remove($css, $imports));

        if ($this->containsImport($stylesheet)) {
            throw new PresetSourceException(
                "Preset [{$preset}] contains a malformed or misplaced @import in [".basename($path).'].'
            );
        }

        if ($stylesheet !== '') {
            $visual[] = $stylesheet;
        }
    }

    private function foundation(string $path, string $cssRoot): ?string
    {
        $applicationRoot = dirname($cssRoot, 2);
        $candidate = $applicationRoot.'/vendor/emaia/laravel-hotwire/resources/css';
        $packageRoot = $this->canonical(realpath($candidate) ?: $candidate);

        if (! $this->inside($path, $packageRoot) || $this->inside($path, $packageRoot.'/presets')) {
            return null;
        }

        return ltrim(substr($path, strlen($packageRoot)), '/');
    }

    private function containsImport(string $css): bool
    {
        return preg_match(
            '~(?:/\*.*?\*/|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')(*SKIP)(*F)|@import\b~is',
            $css,
        ) === 1;
    }

    /**
     * @param  string[]  $errors
     * @return array{errors: string[], warnings: string[], styledSlots: string[], referencedSlots: string[]}
     */
    private function failed(array $errors): array
    {
        return [
            'errors' => $errors,
            'warnings' => [],
            'styledSlots' => [],
            'referencedSlots' => [],
        ];
    }

    /**
     * @param  string[]  $errors
     * @param  string[]  $warnings
     * @param  string[]  $unvisitedSlots
     * @return array{errors: string[], warnings: string[], styledSlots: string[], referencedSlots: string[]}
     */
    private function result(
        string $name,
        array $errors,
        array $warnings,
        string $css,
        HotwireRegistry $registry,
        array $unvisitedSlots,
    ): array {
        $definitions = [...array_values($registry->components()), ...array_values($registry->controllers())];
        $required = [];
        $declared = [];

        foreach ($definitions as $definition) {
            $required = [...$required, ...$definition->styling->visualSlots()];
            $declared = [...$declared, ...array_keys($definition->styling->slots)];
        }

        $required = array_values(array_unique($required));
        $declared = array_values(array_unique($declared));
        $styled = $this->slots->withDeclarations($css);
        $referenced = $this->slots->referenced($css);
        $missing = array_values(array_diff($required, $styled));
        $unprovenMissing = array_values(array_intersect($missing, $unvisitedSlots));
        $provenMissing = array_values(array_diff($missing, $unprovenMissing));
        $unknown = array_values(array_diff($referenced, $declared));
        sort($provenMissing);
        sort($unprovenMissing);
        sort($unknown);

        if ($provenMissing !== []) {
            $errors[] = "Preset [{$name}] is missing visual slots: ".implode(', ', $provenMissing).'.';
        }

        if ($unprovenMissing !== []) {
            $warnings[] = "Preset [{$name}] could not prove visual coverage: ".implode(', ', $unprovenMissing).'.';
        }

        if ($unknown !== []) {
            $errors[] = "Preset [{$name}] references undeclared slots: ".implode(', ', $unknown).'.';
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'styledSlots' => $styled,
            'referencedSlots' => $referenced,
        ];
    }

    private function canonical(string $path): string
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

    private function inside(string $path, string $root): bool
    {
        $path = $this->comparable($this->canonical($path));
        $root = rtrim($this->comparable($this->canonical($root)), '/');

        return $path === $root || str_starts_with($path, $root.'/');
    }

    private function comparable(string $path): string
    {
        return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '//')
            ? strtolower($path)
            : $path;
    }
}
