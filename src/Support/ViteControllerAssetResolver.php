<?php

namespace Emaia\LaravelHotwire\Support;

use Closure;
use Illuminate\Foundation\Vite;
use JsonException;
use RuntimeException;

final class ViteControllerAssetResolver
{
    /** @var array<array-key, mixed> */
    private array $manifest;

    /** @var Closure(string, string): string */
    private Closure $assetUrlResolver;

    /**
     * Build a resolver from a decoded manifest or a manifest JSON path.
     *
     * @param  array<array-key, mixed>|string  $manifest
     * @param  (callable(string, string): string)|null  $assetUrlResolver
     */
    public function __construct(
        array|string $manifest,
        ?callable $assetUrlResolver = null,
        string $buildDirectory = 'build',
    ) {
        $this->manifest = is_array($manifest) ? $manifest : $this->readManifest($manifest);
        $this->assetUrlResolver = $assetUrlResolver === null
            ? fn (string $_manifestKey, string $file): string => asset(trim($buildDirectory, '/').'/'.$file)
            : Closure::fromCallable($assetUrlResolver);
    }

    /**
     * Build a resolver whose URLs honor Laravel Vite's configured asset path resolver.
     *
     * @param  array<array-key, mixed>|string  $manifest
     */
    public static function usingVite(
        array|string $manifest,
        Vite $vite,
        string $buildDirectory = 'build',
    ): self {
        $assetPath = Closure::bind(
            fn (string $path): string => $this->assetPath($path),
            $vite,
            $vite,
        );

        return new self(
            $manifest,
            fn (string $_manifestKey, string $file): string => $assetPath(trim($buildDirectory, '/').'/'.$file),
            $buildDirectory,
        );
    }

    /**
     * Resolve requested controllers and every recursive static import in preload order.
     *
     * @param  iterable<string>  $identifiers
     * @return list<ViteControllerAsset>
     *
     * @throws RuntimeException
     */
    public function resolve(iterable $identifiers): array
    {
        $controllers = $this->controllerCandidates();
        $assets = [];
        $seen = [];

        foreach ($identifiers as $identifier) {
            $candidates = $controllers[$identifier] ?? ['local' => [], 'package' => []];
            $scope = $candidates['local'] !== [] ? 'local' : 'package';
            $matches = $candidates[$scope];

            if ($matches === []) {
                throw new RuntimeException("Unable to locate Stimulus controller [{$identifier}] in the Vite manifest.");
            }

            if (count($matches) > 1) {
                throw new RuntimeException(sprintf(
                    'Ambiguous %s Stimulus controller [%s] in the Vite manifest: %s.',
                    $scope,
                    $identifier,
                    implode(', ', $matches),
                ));
            }

            $this->appendChunk($matches[0], $assets, $seen);
        }

        return $assets;
    }

    /** @return array<string, array{local: list<string>, package: list<string>}> */
    private function controllerCandidates(): array
    {
        $controllers = [];

        foreach ($this->manifest as $manifestKey => $entry) {
            if (! is_string($manifestKey)) {
                continue;
            }

            $source = is_array($entry) && isset($entry['src']) && is_string($entry['src'])
                ? $entry['src']
                : $manifestKey;
            $candidate = $this->controllerCandidate($source);

            if ($candidate === null) {
                continue;
            }

            [$identifier, $scope] = $candidate;
            $controllers[$identifier] ??= ['local' => [], 'package' => []];
            $controllers[$identifier][$scope][] = $manifestKey;
        }

        return $controllers;
    }

    /** @return array{string, 'local'|'package'}|null */
    private function controllerCandidate(string $source): ?array
    {
        $source = ltrim(str_replace('\\', '/', $source), './');
        $localPrefix = 'resources/js/controllers/';

        if (str_starts_with($source, $localPrefix)) {
            $relative = substr($source, strlen($localPrefix));
            $scope = 'local';
        } elseif (preg_match('#(?:^|/)laravel-hotwire/resources/js/controllers/(.+)$#', $source, $matches) === 1) {
            $relative = $matches[1];
            $scope = 'package';
        } else {
            return null;
        }

        $name = preg_replace('/_controller\.(js|ts)$/', '', $relative);

        if ($name === null || $name === $relative) {
            return null;
        }

        $name = ControllerResolver::logicalPath($name);

        return [str_replace(['/', '_'], ['--', '-'], $name), $scope];
    }

    /**
     * @param  list<ViteControllerAsset>  $assets
     * @param  array<string, true>  $seen
     */
    private function appendChunk(string $manifestKey, array &$assets, array &$seen): void
    {
        if (isset($seen[$manifestKey])) {
            return;
        }

        $entry = $this->manifest[$manifestKey] ?? null;

        if (! is_array($entry) || ! isset($entry['file']) || ! is_string($entry['file']) || $entry['file'] === '') {
            throw new RuntimeException("Vite manifest entry [{$manifestKey}] has no valid file.");
        }

        $seen[$manifestKey] = true;
        $source = isset($entry['src']) && is_string($entry['src']) ? $entry['src'] : $manifestKey;
        $integrity = isset($entry['integrity']) && is_string($entry['integrity']) ? $entry['integrity'] : null;
        $assets[] = new ViteControllerAsset(
            manifestKey: $manifestKey,
            source: $source,
            file: $entry['file'],
            url: ($this->assetUrlResolver)($manifestKey, $entry['file']),
            integrity: $integrity,
        );

        $cssAssets = $entry['css'] ?? [];

        if (! is_array($cssAssets) || ! array_is_list($cssAssets)) {
            throw new RuntimeException("Vite manifest entry [{$manifestKey}] has malformed CSS assets.");
        }

        foreach ($cssAssets as $css) {
            if (! is_string($css) || $css === '') {
                throw new RuntimeException("Vite manifest entry [{$manifestKey}] has malformed CSS assets.");
            }

            $cssManifestKey = $this->manifestKeyForFile($css);
            $cssKey = $cssManifestKey ?? "{$manifestKey}#css:{$css}";

            if (isset($seen[$cssKey])) {
                continue;
            }

            $cssEntry = $cssManifestKey === null ? [] : $this->manifest[$cssManifestKey];
            $seen[$cssKey] = true;
            $assets[] = new ViteControllerAsset(
                manifestKey: $cssManifestKey ?? $manifestKey,
                source: is_array($cssEntry) && isset($cssEntry['src']) && is_string($cssEntry['src'])
                    ? $cssEntry['src']
                    : $source,
                file: $css,
                url: ($this->assetUrlResolver)($cssManifestKey ?? $manifestKey, $css),
                integrity: is_array($cssEntry) && isset($cssEntry['integrity']) && is_string($cssEntry['integrity'])
                    ? $cssEntry['integrity']
                    : null,
                style: true,
            );
        }

        if (! array_key_exists('imports', $entry)) {
            return;
        }

        if (! is_array($entry['imports']) || ! array_is_list($entry['imports'])) {
            throw new RuntimeException("Vite manifest entry [{$manifestKey}] has malformed imports.");
        }

        foreach ($entry['imports'] as $import) {
            if (! is_string($import) || $import === '') {
                throw new RuntimeException("Vite manifest entry [{$manifestKey}] has malformed imports.");
            }

            if (! array_key_exists($import, $this->manifest)) {
                throw new RuntimeException("Vite manifest import [{$import}] referenced by [{$manifestKey}] is missing.");
            }

            $this->appendChunk($import, $assets, $seen);
        }
    }

    private function manifestKeyForFile(string $file): ?string
    {
        foreach ($this->manifest as $key => $entry) {
            if (is_string($key)
                && is_array($entry)
                && ($entry['file'] ?? null) === $file
            ) {
                return $key;
            }
        }

        return null;
    }

    /** @return array<array-key, mixed> */
    private function readManifest(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Vite manifest not found at [{$path}].");
        }

        try {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Unable to decode Vite manifest at [{$path}].", previous: $exception);
        }

        if (! is_array($manifest)) {
            throw new RuntimeException("Unable to decode Vite manifest at [{$path}].");
        }

        return $manifest;
    }
}
