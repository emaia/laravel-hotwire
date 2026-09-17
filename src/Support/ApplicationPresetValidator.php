<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Filesystem\Filesystem;

/** @internal */
final readonly class ApplicationPresetValidator
{
    private const array REQUIRED_FOUNDATIONS = ['foundation.css'];

    public function __construct(
        private Filesystem $files,
        private CssImports $imports,
        private CssRules $rules,
        private CssInterpolationSyntax $interpolationSyntax,
        private CssSlots $slots,
        private PresetAxes $axes,
        private FoundationFacade $foundationFacade,
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
        $cssRoot = CssPath::normalize(realpath($cssRootPath) ?: $cssRootPath);
        $entrypoint = CssPath::normalize(realpath($entrypoint) ?: $entrypoint);
        $errors = [];
        $warnings = [];
        $visual = [];
        $foundations = [];
        $visited = [];
        $stack = [];

        if (! $this->files->isFile($entrypoint)) {
            return $this->failed(["Application preset [{$name}] does not exist."]);
        }

        if (! CssPath::contains($cssRoot, $entrypoint)) {
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
            $errors[] = "Preset [{$name}] must import package foundation [foundation.css] exactly once.";
        }

        $css = implode("\n\n", $visual);
        $coverage = $this->axes->inspectCoverage($css);

        if (! $coverage['complete']) {
            $warnings[] = $coverage['visited'] === $coverage['total']
                ? "Preset [{$name}] CSS analysis is incomplete because the stylesheet contains invalid syntax."
                : "Preset [{$name}] CSS analysis is incomplete ({$coverage['visited']} of {$coverage['total']} slot references parsed).";
        }

        return $this->result(
            $name,
            $errors,
            $warnings,
            $css,
            $registry,
            $coverage['unvisitedSlots'],
            $coverage['unvisitedReferences'],
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

            $target = CssPath::normalize(dirname($path).'/'.$importPath);

            if (! $this->files->isFile($target)) {
                throw new PresetSourceException(
                    "Preset [{$preset}] cannot resolve local import [{$import['path']}] from [".basename($path).'].'
                );
            }

            $target = CssPath::normalize(realpath($target) ?: $target);
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

                if ($foundation === 'foundation.css') {
                    $this->foundationFacade->validate($target, dirname($target), $preset);
                }

                $foundations[] = $foundation;

                continue;
            }

            if (! CssPath::contains($cssRoot, $target)) {
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
            if (! $this->rules->scan($css)['valid']) {
                throw new PresetSourceException(
                    "Preset [{$preset}] contains invalid CSS syntax in [".basename($path).'].'
                );
            }

            throw new PresetSourceException(
                "Preset [{$preset}] contains a malformed or misplaced @import in [".basename($path).'].'
            );
        }

        if ($stylesheet !== '') {
            $violation = $this->interpolationSyntax->invalidDeclarations($stylesheet)[0] ?? null;

            if ($violation !== null) {
                $method = $violation['method'];
                $declaration = $violation['declaration'];
                $replacement = str_replace('_', ' ', $method);

                throw new PresetSourceException(
                    "Preset [{$preset}] uses invalid interpolation method [{$method}] in raw CSS declaration [{$declaration}] in [".basename($path)."]. Write [{$replacement}]; Tailwind underscores represent spaces only inside arbitrary values ([...])."
                );
            }

            $visual[] = $stylesheet;
        }
    }

    private function foundation(string $path, string $cssRoot): ?string
    {
        $applicationRoot = dirname($cssRoot, 2);
        $candidate = $applicationRoot.'/vendor/emaia/laravel-hotwire/resources/css';
        $packageRoot = CssPath::normalize(realpath($candidate) ?: $candidate);

        if (! CssPath::contains($packageRoot, $path) || CssPath::contains($packageRoot.'/presets', $path)) {
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
     * @param  string[]  $unvisitedReferences
     * @return array{errors: string[], warnings: string[], styledSlots: string[], referencedSlots: string[]}
     */
    private function result(
        string $name,
        array $errors,
        array $warnings,
        string $css,
        HotwireRegistry $registry,
        array $unvisitedSlots,
        array $unvisitedReferences,
    ): array {
        $definitions = [...array_values($registry->components()), ...array_values($registry->controllers())];
        $required = [];
        $declared = [];
        $requiredProperties = [];

        foreach ($definitions as $definition) {
            $required = [...$required, ...$definition->styling->visualSlots()];
            $declared = [...$declared, ...array_keys($definition->styling->slots)];

            foreach ($definition->styling->presetProperties() as $slot => $properties) {
                $requiredProperties[$slot] = [
                    ...($requiredProperties[$slot] ?? []),
                    ...array_keys($properties),
                ];
            }
        }

        $required = array_values(array_unique($required));
        $declared = array_values(array_unique($declared));
        $styled = $this->slots->withDeclarations($css);
        $referenced = array_values(array_unique([...$this->slots->referenced($css), ...$unvisitedReferences]));
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

        foreach ($requiredProperties as $slot => $properties) {
            $present = $this->slots->customPropertiesFor($css, $slot);

            foreach (array_unique($properties) as $property) {
                if (! in_array($property, $present, true) && ! in_array($slot, $unvisitedSlots, true)) {
                    $errors[] = "Preset [{$name}] is missing required preset property [{$property}] on [data-slot=\"{$slot}\"].";
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'styledSlots' => $styled,
            'referencedSlots' => $referenced,
        ];
    }
}
