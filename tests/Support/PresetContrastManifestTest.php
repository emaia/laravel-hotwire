<?php

use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\FoundationFacade;
use Emaia\LaravelHotwire\Support\PresetContrastManifest;

it('exports every manifest preset with ordered token sources and inherited contrast pairs', function () {
    $manifest = CssModuleManifest::fromArray(
        require __DIR__.'/../Fixtures/css/preset-package/styles.php',
    );

    expect((new PresetContrastManifest($manifest, syntheticCssPresetFiles()))->toArray())->toBe([
        [
            'name' => 'constellation',
            'sources' => [
                FoundationFacade::TOKEN_SOURCE,
                'presets/constellation/theme.css',
                'presets/constellation/aliases.css',
            ],
            'contrast_pairs' => [
                'fixture' => [
                    'foreground' => '--fixture-foreground',
                    'background' => '--fixture-background',
                ],
                'fixture-surface' => [
                    'foreground' => '--fixture-surface-foreground',
                    'background' => '--fixture-surface',
                ],
            ],
        ],
        [
            'name' => 'orbit',
            'sources' => [FoundationFacade::TOKEN_SOURCE],
            'contrast_pairs' => [
                'fixture' => [
                    'foreground' => '--fixture-foreground',
                    'background' => '--fixture-background',
                ],
            ],
        ],
    ]);
});
