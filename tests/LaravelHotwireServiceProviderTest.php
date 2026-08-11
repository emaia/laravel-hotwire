<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\ServiceProvider;

it('loads default rich text validation translations without publishing', function (string $key, array $replace, string $expected) {
    expect(trans("hotwire::validation.rich_text.{$key}", $replace))->toBe($expected);
})->with([
    'required' => ['required', ['attribute' => 'content'], 'The content field is required.'],
    'minimum' => ['min', ['attribute' => 'content', 'min' => 3], 'The content field must be at least 3 characters.'],
    'maximum' => ['max', ['attribute' => 'content', 'max' => 10], 'The content field must not be greater than 10 characters.'],
    'invalid' => ['invalid', ['attribute' => 'content'], 'The content field must contain valid rich text.'],
]);

it('publishes package translations under the documented tag', function () {
    $paths = ServiceProvider::pathsToPublish(
        LaravelHotwireServiceProvider::class,
        'hotwire-translations',
    );

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(dirname(__DIR__).'/resources/lang'))
        ->and(array_values($paths))->toBe([lang_path('vendor/hotwire')]);
});
