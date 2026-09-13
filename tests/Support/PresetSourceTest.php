<?php

use Emaia\LaravelHotwire\Support\PresetSource;
use Emaia\LaravelHotwire\Support\PresetSourceException;

beforeEach(function () {
    $this->source = new PresetSource(
        'demo',
        ['foundation.css'],
        ['/* theme */', '/* modal */', '/* button */'],
        [
            'presets/demo/theme.css',
            'presets/demo/modal.css',
            'presets/demo/button.css',
        ],
        ['presets/demo/theme.css'],
    );
});

it('selects visual sources in canonical order', function () {
    $selected = $this->source->select([
        'presets/demo/theme.css',
        'presets/demo/modal.css',
    ]);

    expect($selected->foundationImports())->toBe(['foundation.css'])
        ->and($selected->visualStylesheetPaths())->toBe([
            'presets/demo/theme.css',
            'presets/demo/modal.css',
        ])
        ->and($selected->visualStylesheets())->toBe(['/* theme */', '/* modal */'])
        ->and($selected->baseStylesheetPaths())->toBe(['presets/demo/theme.css']);
});

it('rejects visual sources not imported by the preset', function () {
    $this->source->select(['presets/demo/missing.css']);
})->throws(
    PresetSourceException::class,
    'Selected visual source [presets/demo/missing.css] is not imported by preset [demo].',
);

it('rejects visual sources outside canonical order', function () {
    $this->source->select([
        'presets/demo/button.css',
        'presets/demo/modal.css',
    ]);
})->throws(
    PresetSourceException::class,
    'Selected visual sources for preset [demo] do not follow canonical import order.',
);
