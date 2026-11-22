<?php

use Emaia\LaravelHotwire\Support\TurboBuild;

const TURBO_OLD_BUILD = 'https://github.com/emaia/laravel-hotwire/releases/download/turbo-000000000000/hotwired-turbo-000000000000.tgz';
const TURBO_NEW_BUILD = 'https://github.com/emaia/laravel-hotwire/releases/download/turbo-5350970b085d/hotwired-turbo-5350970b085d.tgz';

// --- Package builds ---

it('recognises Turbo builds published by the package', function () {
    expect(TurboBuild::tag(TURBO_NEW_BUILD))->toBe('turbo-5350970b085d');
});

it('does not treat other references as package builds', function (string $reference) {
    expect(TurboBuild::tag($reference))->toBeNull();
})->with([
    'semver' => '^8.0.23',
    'third-party tarball' => 'https://example.test/hotwired-turbo-5350970b085d.tgz',
    'other repository release' => 'https://github.com/acme/app/releases/download/turbo-5350970b085d/hotwired-turbo-5350970b085d.tgz',
    'mismatched asset' => 'https://github.com/emaia/laravel-hotwire/releases/download/turbo-5350970b085d/hotwired-turbo-000000000000.tgz',
    'local file' => 'file:../hotwired-turbo-5350970b085d.tgz',
    'git' => 'github:hotwired/turbo#5350970b085d',
]);

// --- Superseded builds ---

it('reports a package build that differs from the recommended reference', function (string $recommended) {
    expect(TurboBuild::isSuperseded(TURBO_OLD_BUILD, $recommended))->toBeTrue();
})->with([
    'newer build' => TURBO_NEW_BUILD,
    'official release' => '^8.0.30',
]);

it('leaves current builds and app-owned references alone', function (string $current) {
    expect(TurboBuild::isSuperseded($current, TURBO_NEW_BUILD))->toBeFalse();
})->with([
    'current build' => TURBO_NEW_BUILD,
    'semver' => '^8.0.23',
    'third-party tarball' => 'https://example.test/turbo.tgz',
    'local file' => 'file:../turbo.tgz',
]);

// --- Switch command ---

it('builds the switch command for the detected package manager', function (string $manager, string $recommended, string $command) {
    expect(TurboBuild::switchCommand($manager, $recommended, dev: true))->toBe($command);
})->with([
    'bun build' => ['bun', TURBO_NEW_BUILD, 'bun add -d '.TURBO_NEW_BUILD],
    'npm build' => ['npm', TURBO_NEW_BUILD, 'npm install -D '.TURBO_NEW_BUILD],
    'pnpm release' => ['pnpm', '^8.0.30', 'pnpm add -D @hotwired/turbo@^8.0.30'],
    'yarn release' => ['yarn', '^8.0.30', 'yarn add -D @hotwired/turbo@^8.0.30'],
]);

it('keeps Turbo in production dependencies when the app declares it there', function (string $manager, string $command) {
    expect(TurboBuild::switchCommand($manager, '^8.0.30', dev: false))->toBe($command);
})->with([
    'bun' => ['bun', 'bun add @hotwired/turbo@^8.0.30'],
    'npm' => ['npm', 'npm install @hotwired/turbo@^8.0.30'],
    'pnpm' => ['pnpm', 'pnpm add @hotwired/turbo@^8.0.30'],
    'yarn' => ['yarn', 'yarn add @hotwired/turbo@^8.0.30'],
]);
