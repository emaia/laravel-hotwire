<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

final readonly class CssPresetFiles
{
    public function __construct(private Filesystem $files) {}

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
}
