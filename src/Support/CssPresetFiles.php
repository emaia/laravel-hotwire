<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

final readonly class CssPresetFiles
{
    public function __construct(
        private Filesystem $files,
        private PresetSourceResolver $sources,
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
}
