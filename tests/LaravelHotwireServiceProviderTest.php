<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Contracts\Console\Kernel;
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

it('publishes the package config under the documented tag', function () {
    $paths = ServiceProvider::pathsToPublish(
        LaravelHotwireServiceProvider::class,
        'hotwire-config',
    );

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(dirname(__DIR__).'/config/hotwire.php'))
        ->and(array_values($paths))->toBe([config_path('hotwire.php')]);
});

it('publishes package views under the documented tag', function () {
    $paths = ServiceProvider::pathsToPublish(
        LaravelHotwireServiceProvider::class,
        'hotwire-views',
    );

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(dirname(__DIR__).'/resources/views'))
        ->and(array_values($paths))->toBe([base_path('resources/views/vendor/hotwire')]);
});

it('merges the package config so defaults resolve without publishing', function () {
    expect(config('hotwire.prefix'))->toBe('hw');
});

it('registers the package view namespace', function () {
    expect(view()->exists('hotwire::component-views.accordion'))->toBeTrue();
});

it('registers every package command', function () {
    $commands = array_keys(app(Kernel::class)->all());

    expect($commands)->toContain(
        'hotwire:install',
        'hotwire:make-controller',
        'hotwire:make-preset',
        'hotwire:controllers',
        'hotwire:components',
        'hotwire:check',
        'hotwire:docs',
        'hotwire:ide-json',
    );
});
