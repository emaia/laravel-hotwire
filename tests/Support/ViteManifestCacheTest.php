<?php

use Emaia\LaravelHotwire\Support\ViteManifestCache;

beforeEach(function () {
    app()->forgetScopedInstances();
});

it('reuses controller candidates for a build directory within a request', function () {
    $cache = app(ViteManifestCache::class);
    $scans = 0;
    $discover = function () use (&$scans): array {
        $scans++;

        return [
            'search' => [
                'local' => ['resources/js/controllers/search_controller.js'],
                'package' => [],
            ],
        ];
    };

    expect($cache->rememberControllerCandidates('build', $discover))->toBe($cache->rememberControllerCandidates('build', $discover))
        ->and($scans)->toBe(1);
});

it('reuses manifest file lookups for a build directory within a request', function () {
    $cache = app(ViteManifestCache::class);
    $scans = 0;
    $discover = function () use (&$scans): ?string {
        $scans++;

        return 'resources/css/reveal.css';
    };

    expect($cache->rememberManifestFile('build', 'assets/reveal.css', $discover))
        ->toBe($cache->rememberManifestFile('build', 'assets/reveal.css', $discover))
        ->toBe('resources/css/reveal.css')
        ->and($scans)->toBe(1);
});

it('reuses a manifest file lookup that found no entry', function () {
    $cache = app(ViteManifestCache::class);
    $scans = 0;
    $discover = function () use (&$scans): ?string {
        $scans++;

        return null;
    };

    expect($cache->rememberManifestFile('build', 'assets/missing.css', $discover))->toBeNull()
        ->and($cache->rememberManifestFile('build', 'assets/missing.css', $discover))->toBeNull()
        ->and($scans)->toBe(1);
});

it('flushes every cached manifest lookup', function () {
    $cache = app(ViteManifestCache::class);
    $candidateScans = 0;
    $fileScans = 0;
    $discoverCandidates = function () use (&$candidateScans): array {
        $candidateScans++;

        return ['scan' => ['local' => [], 'package' => []]];
    };
    $discoverFiles = function () use (&$fileScans): ?string {
        $fileScans++;

        return 'resources/css/reveal.css';
    };
    $cache->rememberControllerCandidates('build', $discoverCandidates);
    $cache->rememberManifestFile('build', 'assets/reveal.css', $discoverFiles);

    $cache->flush();

    expect($cache->rememberControllerCandidates('build', $discoverCandidates))->toBe(['scan' => ['local' => [], 'package' => []]])
        ->and($cache->rememberManifestFile('build', 'assets/reveal.css', $discoverFiles))
        ->toBe('resources/css/reveal.css')
        ->and($candidateScans)->toBe(2)
        ->and($fileScans)->toBe(2);
});

it('discards controller candidates in a fresh request scope', function () {
    $scans = 0;
    $discover = function () use (&$scans): array {
        $scans++;

        return [];
    };
    $cache = app(ViteManifestCache::class);
    $cache->rememberControllerCandidates('build', $discover);

    app()->forgetScopedInstances();

    $freshCache = app(ViteManifestCache::class);
    $freshCache->rememberControllerCandidates('build', $discover);

    expect($freshCache)->not->toBe($cache)
        ->and($scans)->toBe(2);
});
