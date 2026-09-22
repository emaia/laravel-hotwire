<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

it('loads default rich text validation translations without publishing', function (string $key, array $replace, string $expected) {
    expect(trans("hotwire::validation.rich_text.{$key}", $replace))->toBe($expected);
})->with([
    'required' => ['required', ['attribute' => 'content'], 'The content field is required.'],
    'minimum' => ['min', ['attribute' => 'content', 'min' => 3], 'The content field must be at least 3 characters.'],
    'maximum' => ['max', ['attribute' => 'content', 'max' => 10], 'The content field must not be greater than 10 characters.'],
    'invalid' => ['invalid', ['attribute' => 'content'], 'The content field must contain valid rich text.'],
]);

it('keeps conventional application translation overrides working', function () {
    $override = lang_path('vendor/hotwire/fixture/validation.php');
    File::ensureDirectoryExists(dirname($override));
    File::put($override, <<<'PHP'
<?php

return ['rich_text' => ['required' => 'Application translation']];
PHP);

    try {
        expect(trans('hotwire::validation.rich_text.required', locale: 'fixture'))->toBe('Application translation');
    } finally {
        File::deleteDirectory(lang_path('vendor/hotwire/fixture'));
    }
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

it('publishes the package config under the documented tag', function () {
    $paths = ServiceProvider::pathsToPublish(
        LaravelHotwireServiceProvider::class,
        'hotwire-config',
    );

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(dirname(__DIR__).'/config/hotwire.php'))
        ->and(array_values($paths))->toBe([config_path('hotwire.php')]);
});

it('does not expose package views through vendor publish', function () {
    $paths = ServiceProvider::pathsToPublish(
        LaravelHotwireServiceProvider::class,
        'hotwire-views',
    );
    $providerPaths = ServiceProvider::pathsToPublish(LaravelHotwireServiceProvider::class);

    expect($paths)->toBe([])
        ->and(array_keys($providerPaths))->not->toContain(dirname(__DIR__).'/resources/views')
        ->and(array_values($providerPaths))->not->toContain(base_path('resources/views/vendor/hotwire'));
});

it('merges the package config so defaults resolve without publishing', function () {
    expect(config('hotwire.prefix'))->toBe('hw');
});

it('registers the package view namespace', function () {
    expect(view()->exists('hotwire::component-views.accordion'))->toBeTrue();
});

it('keeps conventional application view overrides working', function () {
    $override = resource_path('views/vendor/hotwire/component-views/badge.blade.php');
    File::ensureDirectoryExists(dirname($override));
    File::put($override, 'application override');
    config()->set('view.paths', [resource_path('views')]);
    view()->getFinder()->replaceNamespace('hotwire', []);
    $resources = new ReflectionMethod(LaravelHotwireServiceProvider::class, 'bootPackageResources');
    $resources->invoke(new LaravelHotwireServiceProvider(app()));
    view()->getFinder()->flush();

    try {
        expect(view()->getFinder()->find('hotwire::component-views.badge'))->toBe($override);
    } finally {
        File::deleteDirectory(resource_path('views/vendor/hotwire'));
        view()->getFinder()->flush();
    }
});

it('registers every package command', function () {
    $commands = array_keys(app(Kernel::class)->all());

    expect($commands)->toContain(
        'hotwire:install',
        'hotwire:make-controller',
        'hotwire:make-preset',
        'hotwire:bundle-preset',
        'hotwire:controllers',
        'hotwire:components',
        'hotwire:check',
        'hotwire:docs',
        'hotwire:ide-json',
    )->not->toContain('hotwire:styles');
});
