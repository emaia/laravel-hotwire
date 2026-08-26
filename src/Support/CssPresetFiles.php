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

        foreach ($this->files->glob(dirname(__DIR__, 2).'/resources/css/presets/*.css') ?: [] as $path) {
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

        return $path === null ? null : $this->sources->resolve($path);
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

        return $this->sources->resolve($path, $this->manifest->sourcesFor($name, $modules));
    }
}
