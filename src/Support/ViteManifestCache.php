<?php

namespace Emaia\LaravelHotwire\Support;

use Closure;

final class ViteManifestCache
{
    /** @var array<string, array<string, array{local: list<string>, package: list<string>}>> */
    private array $candidates = [];

    /** @var array<string, array<string, string|null>> */
    private array $manifestFiles = [];

    /**
     * Reuse a manifest's controller candidates within the current request scope.
     *
     * @param  Closure(): array<string, array{local: list<string>, package: list<string>}>  $discover
     * @return array<string, array{local: list<string>, package: list<string>}>
     */
    public function rememberControllerCandidates(string $buildDirectory, Closure $discover): array
    {
        return $this->candidates[$buildDirectory] ??= $discover();
    }

    /**
     * Reuse a manifest key lookup for one built file within the current request scope.
     *
     * Memoized per file rather than as a whole index: building the index costs more than the
     * scan it replaces until a request resolves preloads several times over.
     *
     * @param  Closure(): (string|null)  $discover
     */
    public function rememberManifestFile(string $buildDirectory, string $file, Closure $discover): ?string
    {
        if (! array_key_exists($file, $this->manifestFiles[$buildDirectory] ?? [])) {
            $this->manifestFiles[$buildDirectory][$file] = $discover();
        }

        return $this->manifestFiles[$buildDirectory][$file];
    }

    /** Discard every cached manifest lookup. */
    public function flush(): void
    {
        $this->candidates = [];
        $this->manifestFiles = [];
    }
}
