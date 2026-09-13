<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

final readonly class CssPresetFiles
{
    public function __construct(
        private Filesystem $files,
        private PresetSourceResolver $sources,
        private CssModuleManifest $manifest,
    ) {}

    /** @return array<string, string> */
    public function all(): array
    {
        $presets = [];

        foreach ($this->files->glob($this->sources->cssRoot().'/presets/*.css') ?: [] as $path) {
            if (! $this->files->isFile($path)) {
                continue;
            }

            $presets[$this->files->name($path)] = realpath($path) ?: $path;
        }

        ksort($presets);

        return $presets;
    }

    /** @return string[] */
    public function names(): array
    {
        return array_keys($this->all());
    }

    public function path(string $name): ?string
    {
        return $this->all()[$name] ?? null;
    }

    /** Resolve a shipped preset by name. */
    public function source(string $name): ?PresetSource
    {
        $path = $this->path($name);

        if ($path === null) {
            return null;
        }

        $source = $this->sources->resolve($path, baseSources: $this->manifest->baseFor($name));
        $this->validateSource($name, $source);

        return $source;
    }

    /**
     * Resolve a shipped preset for selected catalog owners.
     *
     * @param  string[]  $components
     * @param  string[]  $controllers
     */
    public function sourceForSelection(string $name, array $components = [], array $controllers = []): ?PresetSource
    {
        $path = $this->path($name);

        if ($path === null) {
            return null;
        }

        $modules = $this->manifest->modulesFor($components, $controllers);
        $source = $this->sources->resolve($path, baseSources: $this->manifest->baseFor($name));
        $this->validateSource($name, $source);

        return $source->select($this->manifest->sourcesFor($name, $modules));
    }

    private function validateSource(string $name, PresetSource $source): void
    {
        $this->validateFoundations($name, $source);
        $expected = $this->manifest->allSourcesFor($name);
        $actual = $source->visualStylesheetPaths();

        if ($actual === $expected) {
            return;
        }

        $missing = array_values(array_diff($expected, $actual));
        $unexpected = array_values(array_diff($actual, $expected));

        if ($missing !== []) {
            throw new PresetSourceException(
                "Preset [{$name}] entrypoint does not import declared sources: ".implode(', ', $missing).'.'
            );
        }

        if ($unexpected !== []) {
            throw new PresetSourceException(
                "Preset [{$name}] entrypoint imports undeclared sources: ".implode(', ', $unexpected).'.'
            );
        }

        $base = $this->manifest->baseFor($name);

        throw new PresetSourceException(array_slice($actual, 0, count($base)) !== $base
            ? "Preset [{$name}] entrypoint must import preset base before modules in manifest order."
            : "Preset [{$name}] entrypoint must import module sources in manifest order.");
    }

    private function validateFoundations(string $name, PresetSource $source): void
    {
        if ($source->foundationImports() !== ['foundation.css']) {
            throw new PresetSourceException(
                "Preset [{$name}] must import shared foundation [foundation.css] exactly once before preset sources."
            );
        }
    }
}
