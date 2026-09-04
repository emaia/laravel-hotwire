<?php

use Emaia\LaravelHotwire\Support\ViteControllerCandidateCache;

beforeEach(function () {
    app()->forgetScopedInstances();
});

it('reuses controller candidates for a build directory within a request', function () {
    $cache = app(ViteControllerCandidateCache::class);
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

    expect($cache->remember('build', $discover))->toBe($cache->remember('build', $discover))
        ->and($scans)->toBe(1);
});

it('flushes cached controller candidates', function () {
    $cache = app(ViteControllerCandidateCache::class);
    $scans = 0;
    $discover = function () use (&$scans): array {
        $scans++;

        return ['scan' => ['local' => [], 'package' => []]];
    };
    $cache->remember('build', $discover);

    $cache->flush();

    expect($cache->remember('build', $discover))->toBe(['scan' => ['local' => [], 'package' => []]])
        ->and($scans)->toBe(2);
});

it('discards controller candidates in a fresh request scope', function () {
    $scans = 0;
    $discover = function () use (&$scans): array {
        $scans++;

        return [];
    };
    $cache = app(ViteControllerCandidateCache::class);
    $cache->remember('build', $discover);

    app()->forgetScopedInstances();

    $freshCache = app(ViteControllerCandidateCache::class);
    $freshCache->remember('build', $discover);

    expect($freshCache)->not->toBe($cache)
        ->and($scans)->toBe(2);
});
