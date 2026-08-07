<?php

use Emaia\LaravelHotwire\Support\CssPresetFiles;

it('discovers shipped css presets in sorted order', function () {
    $presets = app(CssPresetFiles::class);
    $expected = collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
        ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => realpath($path)])
        ->sortKeys()
        ->all();

    expect($presets->all())->toBe($expected)
        ->and($presets->names())->toBe(array_keys($expected))
        ->and($presets->path('nova'))->toBe($expected['nova'])
        ->and($presets->path('missing'))->toBeNull();
});
