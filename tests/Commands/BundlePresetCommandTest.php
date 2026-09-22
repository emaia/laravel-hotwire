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
    $this->artisan('hotwire:bundle-preset --components=modal --no-interaction')
        ->expectsOutputToContain('resources/css/hotwire.css')
        ->assertSuccessful();

    $css = File::get($this->output);

    expect($css)
        ->toStartWith('/* '.PackageMarker::TAG.' */')
        ->toContain('@import "../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";')
        ->not->toContain('/resources/css/tokens.css')
        ->not->toContain('/resources/css/custom-variants.css')
        ->not->toContain('/resources/css/structural.css')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="modal-trigger"]')
        ->not->toContain('[data-slot="carousel"]')
        ->toEndWith("\n");
});

it('generates foundations when a selection has no visual module closure', function () {
    $this->artisan('hotwire:bundle-preset --include=color-scheme --no-interaction')->assertSuccessful();

    $css = File::get($this->output);
    $plan = app(GeneratedStyleBundle::class)->planFromContent($css);

    expect($css)
        ->toContain('@import "../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";')
        ->not->toContain('[data-slot=')
        ->and($plan['modules'])->toBe([]);
});

it('includes every component-owned Toaster card slot', function () {
    $this->artisan('hotwire:bundle-preset --components=toaster --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="toast"]')
        ->toContain('[data-slot="toast-content"]')
        ->toContain('[data-slot="toast-icon"]')
        ->toContain('[data-slot="toast-body"]')
        ->toContain('[data-slot="toast-title"]')
        ->toContain('[data-slot="toast-description"]')
        ->toContain('[data-slot="toast-close"]')
        ->not->toContain('[data-slot="modal-panel"]');
});

it('includes the complete Textarea visual contract', function () {
    $this->artisan('hotwire:bundle-preset --components=textarea --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="textarea-wrapper"]')
        ->toContain('[data-slot="textarea"]')
        ->toContain('[data-slot="textarea-counter"]');
});

it('includes Tooltip visuals and shared dependencies for component integrations', function (string $component) {
    $this->artisan("hotwire:bundle-preset --components={$component} --no-interaction")->assertSuccessful();

    $css = File::get($this->output);

    expect($css)
        ->toContain('[data-slot="tooltip"]')
        ->toContain('[data-slot="tooltip-arrow"]')
        ->toContain('[data-slot="kbd"]')
        ->not->toContain('@media (prefers-reduced-motion: reduce)')
        ->not->toContain('[data-slot="toast"]')
        ->not->toContain('[data-slot="video-embed"]');
})->with(['button', 'color-scheme.toggle']);

it('includes Video Embed visuals through the component selection', function () {
    $this->artisan('hotwire:bundle-preset --components=badge,video-embed --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="badge"]')
        ->toContain('[data-slot="video-embed"]')
        ->toContain('[data-slot="video-embed-frame"]')
        ->toContain('[data-slot="video-embed-link"]')
        ->not->toContain('[data-slot="tooltip"]')
        ->not->toContain('[data-slot="toast"]');
});

it('includes every visual dependency used while uploading files', function () {
    $this->artisan('hotwire:bundle-preset --components=file-upload --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="file-upload"]')
        ->toContain('@keyframes hotwire-shimmer')
        ->toContain('[data-shimmer="true"]');
});

it('accepts repeated manual component and controller inclusions', function () {
    $this->artisan('hotwire:bundle-preset --components=badge --include=modal --include=tooltip --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="badge"]')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="tooltip"]')
        ->not->toContain('[data-slot="carousel"]');
});

it('records a canonical component-only v2 regeneration plan with a hash', function () {
    $this->artisan('hotwire:bundle-preset --components=modal --include=turbo/progress --no-interaction')
        ->assertSuccessful();

    $plan = app(GeneratedStyleBundle::class)->planFromContent(File::get($this->output));

    expect($plan)
        ->not->toBeNull()
        ->and($plan['version'])->toBe(2)
        ->and($plan['preset'])->toBe('nova')
        ->and($plan['components'])->toBe(['modal'])
        ->and($plan)->not->toHaveKey('controllers')
        ->and($plan['hash'])->toMatch('/^[a-f0-9]{64}$/')
        ->and($plan['modules'])->toContain('modal', 'button-surfaces')
        ->not->toContain('overlay-motion')
        ->not->toContain('overlay-foundation');
});

it('regenerates a bundle in place from its recorded component selection', function () {
    $this->artisan('hotwire:bundle-preset --preset=bloom --components=badge,modal --output=resources/css/generated/front.css --no-interaction')
        ->assertSuccessful();
    $path = resource_path('css/generated/front.css');
    $canonical = File::get($path);
    File::put($path, str_replace('[data-slot="badge"]', '[data-slot="edited"]', $canonical));

    $this->artisan('hotwire:bundle-preset --from=resources/css/generated/front.css --no-interaction')
        ->expectsOutputToContain('Use --force to overwrite')
        ->assertFailed();

    $this->artisan('hotwire:bundle-preset --from=resources/css/generated/front.css --force --no-interaction')
        ->expectsOutputToContain('Generated: resources/css/generated/front.css')
        ->assertSuccessful();

    expect(File::get($path))->toBe($canonical);
});

it('reports a removed recorded preset as a manual recreation', function () {
    $this->artisan('hotwire:bundle-preset --components=badge --no-interaction')->assertSuccessful();
    File::put($this->output, str_replace('"preset":"nova"', '"preset":"missing"', File::get($this->output)));

    $this->artisan('hotwire:bundle-preset --from=resources/css/hotwire.css --force --no-interaction')
        ->expectsOutputToContain('Recorded preset "missing" is no longer available')
        ->assertFailed();
});

it('migrates a readable v1 bundle to a component-only v2 plan', function () {
    File::ensureDirectoryExists(dirname($this->output));
    File::put($this->output, implode("\n", [
        '/* @hotwire-package */',
        '/* hotwire-preset-bundle-plan: {"version":1,"preset":"nova","components":["badge"],"controllers":["tooltip"],"modules":["stale"]} */',
        '/* Generated by `php artisan hotwire:bundle-preset`. Regenerate instead of editing. */',
        '',
    ]));

    $this->artisan('hotwire:bundle-preset --from=resources/css/hotwire.css --force --no-interaction')
        ->assertSuccessful();

    $plan = app(GeneratedStyleBundle::class)->planFromContent(File::get($this->output));

    expect($plan['version'])->toBe(2)
        ->and($plan['components'])->toBe(['badge'])
        ->and($plan['modules'])->toBe(['badge'])
        ->and($plan)->not->toHaveKey('controllers');
});

it('does not combine from with explicit generation options', function (string $option) {
    $this->artisan('hotwire:bundle-preset --components=badge --no-interaction')->assertSuccessful();

    $this->artisan("hotwire:bundle-preset --from=resources/css/hotwire.css {$option} --force --no-interaction")
        ->expectsOutputToContain('Cannot combine --from')
        ->assertFailed();
})->with([
    '--preset=nova',
    '--components=modal',
    '--include=tooltip',
    '--output=resources/css/other.css',
]);

it('requires from to reference an existing generated css file under resources css', function (string $from, string $message) {
    $this->artisan("hotwire:bundle-preset --from={$from} --force --no-interaction")
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'missing' => ['resources/css/missing.css', 'existing generated CSS file'],
    'outside' => ['storage/hotwire.css', '--from must be a relative .css path under resources/css.'],
    'absolute' => ['/tmp/hotwire.css', '--from must be a relative .css path under resources/css.'],
]);

it('generates a selective Bloom bundle with its preset recorded', function () {
    $this->artisan('hotwire:bundle-preset --preset=bloom --components=modal --no-interaction')
        ->assertSuccessful();

    $css = File::get($this->output);
    $plan = app(GeneratedStyleBundle::class)->planFromContent($css);

    expect($plan)
        ->not->toBeNull()
        ->and($plan['preset'])->toBe('bloom')
        ->and($css)
        ->toContain('--radius:')
        ->toContain('--primary:')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="modal-trigger"]')
        ->not->toContain('[data-slot="carousel"]');
});

it('treats equivalent selection order as an idempotent generation', function () {
    $this->artisan('hotwire:bundle-preset --components=modal,badge --no-interaction')->assertSuccessful();

    $this->artisan('hotwire:bundle-preset --components=badge,modal --no-interaction')
        ->expectsOutputToContain('Up to date')
        ->assertSuccessful();
});

it('parses comma-separated components and adjusts imports for a custom output depth', function () {
    $output = resource_path('css/generated/front.css');

    $this->artisan('hotwire:bundle-preset --components=badge,modal --output=resources/css/generated/front.css --no-interaction')
        ->assertSuccessful();

    expect(File::get($output))
        ->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";')
        ->toContain('[data-slot="badge"]')
        ->toContain('[data-slot="modal-panel"]');
});

it('fails before writing for unknown presets and selections', function (string $command, string $message) {
    $this->artisan($command)
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(File::exists($this->output))->toBeFalse();
})->with([
    'preset' => ['hotwire:bundle-preset --preset=missing --components=modal --no-interaction', 'Unknown preset "missing"'],
    'component' => ['hotwire:bundle-preset --components=modla --no-interaction', 'Unknown component "modla"'],
    'include' => ['hotwire:bundle-preset --components=modal --include=missing --no-interaction', 'Unknown component or controller "missing"'],
]);

it('requires an explicit selection', function () {
    $this->artisan('hotwire:bundle-preset --no-interaction')
        ->expectsOutputToContain('Select at least one component or controller')
        ->assertFailed();

    expect(File::exists($this->output))->toBeFalse();
});

it('requires force to replace a generated bundle', function () {
    $this->artisan('hotwire:bundle-preset --components=badge --no-interaction')->assertSuccessful();

    $this->artisan('hotwire:bundle-preset --components=modal --no-interaction')
        ->expectsOutputToContain('Use --force to overwrite')
        ->assertFailed();

    expect(File::get($this->output))->toContain('[data-slot="badge"]');

    $this->artisan('hotwire:bundle-preset --components=modal --force --no-interaction')->assertSuccessful();

    expect(File::get($this->output))
        ->toContain('[data-slot="modal-panel"]')
        ->not->toContain('[data-slot="badge"]');
});

it('repairs a generated bundle that retains its plan but loses its signature', function () {
    $this->artisan('hotwire:bundle-preset --components=badge --no-interaction')->assertSuccessful();
    $css = File::get($this->output);
    $css = str_replace('/* Generated by `php artisan hotwire:bundle-preset`. Regenerate instead of editing. */', '', $css);
    File::put($this->output, $css);

    $this->artisan('hotwire:bundle-preset --components=badge --force --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->output))->toContain('Generated by `php artisan hotwire:bundle-preset`.');
});

it('never replaces a user-owned output even with force', function () {
    File::ensureDirectoryExists(dirname($this->output));
    File::put($this->output, '/* application-owned */');

    $this->artisan('hotwire:bundle-preset --components=modal --force --no-interaction')
        ->expectsOutputToContain('not generated by Laravel Hotwire')
        ->assertFailed();

    expect(File::get($this->output))->toBe('/* application-owned */');
});

it('never replaces other package-marked CSS even with force', function () {
    $content = '/* '.PackageMarker::TAG." */\n[data-slot=package-owned] {}\n";
    File::ensureDirectoryExists(dirname($this->output));
    File::put($this->output, $content);

    $this->artisan('hotwire:bundle-preset --components=modal --force --no-interaction')
        ->expectsOutputToContain('not generated by Laravel Hotwire')
        ->assertFailed();

    expect(File::get($this->output))->toBe($content);
});

it('only writes bundles under resources css', function (string $output) {
    $this->artisan("hotwire:bundle-preset --components=modal --output={$output} --no-interaction")
        ->expectsOutputToContain('under resources/css')
        ->assertFailed();

    expect(File::exists(base_path($output)))->toBeFalse();
})->with([
    'storage' => 'storage/hotwire.css',
    'vendor' => 'vendor/emaia/laravel-hotwire/resources/css/presets/probe.css',
    'hidden file' => 'resources/css/.hotwire.css',
    'hidden directory' => 'resources/css/.generated/hotwire.css',
    'NTFS alternate stream' => 'resources/css/hotwire.css:stream.css',
]);

it('rejects output paths outside the application', function () {
    $this->artisan('hotwire:bundle-preset --components=modal --output=../hotwire.css --no-interaction')
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
        $this->artisan('hotwire:bundle-preset --components=modal --output=resources/css/generated/hotwire.css --no-interaction')
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

    $this->artisan('hotwire:bundle-preset --components=modal --output=resources/css/generated/hotwire.css --no-interaction')
        ->expectsOutputToContain('Output must resolve inside resources/css')
        ->assertFailed();

    expect(File::exists(resource_path('css/real/hotwire.css')))->toBeFalse();
});

it('rejects output paths whose earlier segment is a symlink', function () {
    File::ensureDirectoryExists(resource_path('css/.real/sub'));
    symlink(resource_path('css/.real'), resource_path('css/generated'));

    $this->artisan('hotwire:bundle-preset --components=modal --output=resources/css/generated/sub/hotwire.css --no-interaction')
        ->expectsOutputToContain('Output must resolve inside resources/css')
        ->assertFailed();

    expect(File::exists(resource_path('css/.real/sub/hotwire.css')))->toBeFalse();
});
