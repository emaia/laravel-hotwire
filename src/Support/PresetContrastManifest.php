<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final readonly class PresetContrastManifest
{
    public function __construct(
        private CssModuleManifest $manifest,
        private CssPresetFiles $presets,
    ) {}

    /**
     * Export validated preset token sources and semantic contrast pairs.
     *
     * @return list<array{name: string, sources: string[], contrast_pairs: array<string, array{foreground: string, background: string}>}>
     */
    public function toArray(): array
    {
        $presets = [];

        foreach ($this->manifest->presetNames() as $name) {
            if ($this->presets->source($name) === null) {
                throw new PresetSourceException("CSS preset [{$name}] entrypoint does not exist.");
            }

            $presets[] = [
                'name' => $name,
                'sources' => [FoundationFacade::TOKEN_SOURCE, ...$this->manifest->baseFor($name)],
                'contrast_pairs' => $this->manifest->contrastPairsFor($name),
            ];
        }

        return $presets;
    }
}
