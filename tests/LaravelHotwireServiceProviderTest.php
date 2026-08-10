<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\ServiceProvider;

it('loads the default rich text validation translation', function () {
    expect(trans('hotwire::validation.invalid_rich_text', ['attribute' => 'content']))
        ->toBe('The content field must contain valid rich text.');
});

it('publishes package translations under the documented tag', function () {
    $paths = ServiceProvider::pathsToPublish(
        LaravelHotwireServiceProvider::class,
        'hotwire-translations',
    );

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(dirname(__DIR__).'/resources/lang'))
        ->and(array_values($paths))->toBe([lang_path('vendor/hotwire')]);
});
