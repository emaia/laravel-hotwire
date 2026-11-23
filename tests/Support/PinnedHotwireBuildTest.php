<?php

use Emaia\LaravelHotwire\Support\PinnedHotwireBuild;

const HOTWIRE_TURBO_BUILD = 'https://github.com/emaia/laravel-hotwire/releases/download/turbo-5350970b085d/hotwired-turbo-5350970b085d.tgz';
const HOTWIRE_STIMULUS_BUILD = 'https://github.com/emaia/laravel-hotwire/releases/download/stimulus-f45f6138ea0e/hotwired-stimulus-f45f6138ea0e.tgz';
const HOTWIRE_STIMULUS_REPACK = 'https://github.com/emaia/laravel-hotwire/releases/download/stimulus-f45f6138ea0e-r2/hotwired-stimulus-f45f6138ea0e-r2.tgz';

// --- Build references ---

it('recognises package-published builds for the right dependency', function (PinnedHotwireBuild $build, string $reference, string $tag) {
    expect($build->tag($reference))->toBe($tag);
})->with([
    'Turbo' => [PinnedHotwireBuild::Turbo, HOTWIRE_TURBO_BUILD, 'turbo-5350970b085d'],
    'Stimulus' => [PinnedHotwireBuild::Stimulus, HOTWIRE_STIMULUS_BUILD, 'stimulus-f45f6138ea0e'],
    'Stimulus packaging revision' => [PinnedHotwireBuild::Stimulus, HOTWIRE_STIMULUS_REPACK, 'stimulus-f45f6138ea0e-r2'],
]);

it('does not recognise app-owned or mismatched build references', function (PinnedHotwireBuild $build, string $reference) {
    expect($build->tag($reference))->toBeNull();
})->with([
    'npm range' => [PinnedHotwireBuild::Turbo, '^8.0.23'],
    'third-party tarball' => [PinnedHotwireBuild::Turbo, str_replace('github.com', 'example.test', HOTWIRE_TURBO_BUILD)],
    'other repository' => [PinnedHotwireBuild::Stimulus, str_replace('emaia/laravel-hotwire', 'acme/app', HOTWIRE_STIMULUS_BUILD)],
    'different SHA' => [PinnedHotwireBuild::Turbo, str_replace('hotwired-turbo-5350970b085d', 'hotwired-turbo-000000000000', HOTWIRE_TURBO_BUILD)],
    'different packaging revision' => [PinnedHotwireBuild::Stimulus, str_replace('-r2.tgz', '-r1.tgz', HOTWIRE_STIMULUS_REPACK)],
    'local file' => [PinnedHotwireBuild::Stimulus, 'file:../hotwired-stimulus-f45f6138ea0e.tgz'],
    'Git' => [PinnedHotwireBuild::Turbo, 'github:hotwired/turbo#5350970b085d'],
    'other runtime' => [PinnedHotwireBuild::Stimulus, HOTWIRE_TURBO_BUILD],
    'extra URL content' => [PinnedHotwireBuild::Stimulus, HOTWIRE_STIMULUS_BUILD."\n"],
]);

// --- Superseded builds ---

it('reports only superseded builds published for the same dependency', function (PinnedHotwireBuild $build, string $current, string $recommended, bool $superseded) {
    expect($build->isSuperseded($current, $recommended))->toBe($superseded);
})->with([
    'Turbo returning to npm' => [PinnedHotwireBuild::Turbo, HOTWIRE_TURBO_BUILD, '^8.0.30', true],
    'Stimulus returning to npm' => [PinnedHotwireBuild::Stimulus, HOTWIRE_STIMULUS_BUILD, '^3.3.0', true],
    'Stimulus repack replaces original' => [PinnedHotwireBuild::Stimulus, HOTWIRE_STIMULUS_BUILD, HOTWIRE_STIMULUS_REPACK, true],
    'current build' => [PinnedHotwireBuild::Stimulus, HOTWIRE_STIMULUS_BUILD, HOTWIRE_STIMULUS_BUILD, false],
    'npm range' => [PinnedHotwireBuild::Turbo, '^8.0.23', HOTWIRE_TURBO_BUILD, false],
    'custom URL' => [PinnedHotwireBuild::Stimulus, 'https://example.test/stimulus.tgz', HOTWIRE_STIMULUS_REPACK, false],
    'local file' => [PinnedHotwireBuild::Turbo, 'file:../turbo.tgz', HOTWIRE_TURBO_BUILD, false],
]);

// --- Switch commands ---

it('builds switch commands with the correct package, manager and dependency section', function (PinnedHotwireBuild $build, string $manager, string $recommended, bool $dev, string $command) {
    expect($build->switchCommand($manager, $recommended, $dev))->toBe($command);
})->with([
    'bun dev Turbo' => [PinnedHotwireBuild::Turbo, 'bun', HOTWIRE_TURBO_BUILD, true, 'bun add -d '.HOTWIRE_TURBO_BUILD],
    'npm dev Stimulus' => [PinnedHotwireBuild::Stimulus, 'npm', HOTWIRE_STIMULUS_REPACK, true, 'npm install -D '.HOTWIRE_STIMULUS_REPACK],
    'pnpm dev Turbo' => [PinnedHotwireBuild::Turbo, 'pnpm', '^8.0.30', true, 'pnpm add -D @hotwired/turbo@^8.0.30'],
    'Yarn dev Stimulus' => [PinnedHotwireBuild::Stimulus, 'yarn', '^3.3.0', true, 'yarn add -D @hotwired/stimulus@^3.3.0'],
    'bun production Turbo' => [PinnedHotwireBuild::Turbo, 'bun', '^8.0.30', false, 'bun add @hotwired/turbo@^8.0.30'],
    'npm production Stimulus' => [PinnedHotwireBuild::Stimulus, 'npm', '^3.3.0', false, 'npm install @hotwired/stimulus@^3.3.0'],
    'pnpm production Turbo' => [PinnedHotwireBuild::Turbo, 'pnpm', '^8.0.30', false, 'pnpm add @hotwired/turbo@^8.0.30'],
    'Yarn production Stimulus' => [PinnedHotwireBuild::Stimulus, 'yarn', HOTWIRE_STIMULUS_REPACK, false, 'yarn add '.HOTWIRE_STIMULUS_REPACK],
]);
