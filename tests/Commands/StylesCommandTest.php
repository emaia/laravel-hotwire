<?php

use Emaia\LaravelHotwire\Support\GeneratedStyleBundle;
use Emaia\LaravelHotwire\Support\PackageMarker;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
    $this->output = resource_path('css/hotwire.css');
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

it('generates a marked selective bundle with shared foundation imports', function () {
    $this->artisan('hotwire:styles --components=modal --no-interaction')
        ->expectsOutputToContain('resources/css/hotwire.css')
        ->assertSuccessful();

    $css = File::get($this->output);

    expect($css)
        ->toStartWith('/* '.PackageMarker::TAG.' */')
        ->toContain('@import "../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";')
        ->toContain('@import "../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";')
        ->toContain('@import "../../vendor/emaia/laravel-hotwire/resources/css/structural.css";')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="modal-trigger"]')
        ->not->toContain('[data-slot="carousel"]')
        ->toEndWith("\n");
});

it('includes visual controllers mounted by selected components', function () {
    $this->artisan('hotwire:styles --components=toaster --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="toast"]')
        ->not->toContain('[data-slot="modal-panel"]');
});

it('includes every visual dependency used while uploading files', function () {
    $this->artisan('hotwire:styles --components=file-upload --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="file-upload"]')
        ->toContain('@keyframes hotwire-shimmer')
        ->toContain('[data-shimmer="true"]');
});

it('accepts repeated manual component and controller inclusions', function () {
    $this->artisan('hotwire:styles --components=badge --include=modal --include=tooltip --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="badge"]')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="tooltip"]')
        ->not->toContain('[data-slot="carousel"]');
});

it('records a canonical regeneration plan with effective modules and controllers', function () {
    $this->artisan('hotwire:styles --components=modal --include=turbo/progress --no-interaction')
        ->assertSuccessful();

    $plan = app(GeneratedStyleBundle::class)->planFromContent(File::get($this->output));

    expect($plan)
        ->not->toBeNull()
        ->and($plan['version'])->toBe(1)
        ->and($plan['preset'])->toBe('nova')
        ->and($plan['components'])->toBe(['modal'])
        ->and($plan['controllers'])->toContain('modal', 'turbo--progress')
        ->and($plan['modules'])->toContain('modal', 'button-surfaces', 'overlay-foundation');
});

it('treats equivalent selection order as an idempotent generation', function () {
    $this->artisan('hotwire:styles --components=modal,badge --no-interaction')->assertSuccessful();

    $this->artisan('hotwire:styles --components=badge,modal --no-interaction')
        ->expectsOutputToContain('Up to date')
        ->assertSuccessful();
});

it('parses comma-separated components and adjusts imports for a custom output depth', function () {
    $output = resource_path('css/generated/front.css');

    $this->artisan('hotwire:styles --components=badge,modal --output=resources/css/generated/front.css --no-interaction')
        ->assertSuccessful();

    expect(File::get($output))
        ->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";')
        ->toContain('[data-slot="badge"]')
        ->toContain('[data-slot="modal-panel"]');
});

it('fails before writing for unknown presets and selections', function (string $command, string $message) {
    $this->artisan($command)
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(File::exists($this->output))->toBeFalse();
})->with([
    'preset' => ['hotwire:styles --preset=missing --components=modal --no-interaction', 'Unknown preset "missing"'],
    'component' => ['hotwire:styles --components=modla --no-interaction', 'Unknown component "modla"'],
    'include' => ['hotwire:styles --components=modal --include=missing --no-interaction', 'Unknown component or controller "missing"'],
]);

it('requires an explicit selection', function () {
    $this->artisan('hotwire:styles --no-interaction')
        ->expectsOutputToContain('Select at least one component or controller')
        ->assertFailed();

    expect(File::exists($this->output))->toBeFalse();
});

it('requires force to replace a generated bundle', function () {
    $this->artisan('hotwire:styles --components=badge --no-interaction')->assertSuccessful();

    $this->artisan('hotwire:styles --components=modal --no-interaction')
        ->expectsOutputToContain('Use --force to overwrite')
        ->assertFailed();

    expect(File::get($this->output))->toContain('[data-slot="badge"]');

    $this->artisan('hotwire:styles --components=modal --force --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="modal-panel"]')
        ->not->toContain('[data-slot="badge"]');
});

it('repairs a generated bundle that retains its plan but loses its signature', function () {
    $this->artisan('hotwire:styles --components=badge --no-interaction')->assertSuccessful();
    $css = File::get($this->output);
    $css = str_replace('/* Generated by `php artisan hotwire:styles`. Regenerate instead of editing. */', '', $css);
    File::put($this->output, $css);

    $this->artisan('hotwire:styles --components=badge --force --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->output))->toContain('Generated by `php artisan hotwire:styles`.');
});

it('never replaces a user-owned output even with force', function () {
    File::ensureDirectoryExists(dirname($this->output));
    File::put($this->output, '/* application-owned */');

    $this->artisan('hotwire:styles --components=modal --force --no-interaction')
        ->expectsOutputToContain('not generated by Laravel Hotwire')
        ->assertFailed();

    expect(File::get($this->output))->toBe('/* application-owned */');
});

it('never replaces other package-marked CSS even with force', function () {
    $content = '/* '.PackageMarker::TAG." */\n[data-slot=package-owned] {}\n";
    File::ensureDirectoryExists(dirname($this->output));
    File::put($this->output, $content);

    $this->artisan('hotwire:styles --components=modal --force --no-interaction')
        ->expectsOutputToContain('not generated by Laravel Hotwire')
        ->assertFailed();

    expect(File::get($this->output))->toBe($content);
});

it('only writes bundles under resources css', function (string $output) {
    $this->artisan("hotwire:styles --components=modal --output={$output} --no-interaction")
        ->expectsOutputToContain('under resources/css')
        ->assertFailed();

    expect(File::exists(base_path($output)))->toBeFalse();
})->with([
    'storage' => 'storage/hotwire.css',
    'vendor' => 'vendor/emaia/laravel-hotwire/resources/css/presets/probe.css',
    'hidden file' => 'resources/css/.hotwire.css',
    'hidden directory' => 'resources/css/.generated/hotwire.css',
]);

it('rejects output paths outside the application', function () {
    $this->artisan('hotwire:styles --components=modal --output=../hotwire.css --no-interaction')
        ->expectsOutputToContain('under resources/css')
        ->assertFailed();

    expect(File::exists(dirname($this->appBase).'/hotwire.css'))->toBeFalse();
});

it('rejects output paths that escape through a symlink', function () {
    $outside = sys_get_temp_dir().'/hotwire-styles-outside-'.uniqid();
    File::ensureDirectoryExists(resource_path('css'));
    File::ensureDirectoryExists($outside);
    symlink($outside, resource_path('css/generated'));

    try {
        $this->artisan('hotwire:styles --components=modal --output=resources/css/generated/hotwire.css --no-interaction')
            ->expectsOutputToContain('Output must resolve inside resources/css')
            ->assertFailed();

        expect(File::exists($outside.'/hotwire.css'))->toBeFalse();
    } finally {
        File::deleteDirectory($outside);
    }
});

it('rejects output paths through symlinks that the coverage scan would not follow', function () {
    File::ensureDirectoryExists(resource_path('css/real'));
    symlink(resource_path('css/real'), resource_path('css/generated'));

    $this->artisan('hotwire:styles --components=modal --output=resources/css/generated/hotwire.css --no-interaction')
        ->expectsOutputToContain('Output must resolve inside resources/css')
        ->assertFailed();

    expect(File::exists(resource_path('css/real/hotwire.css')))->toBeFalse();
});
