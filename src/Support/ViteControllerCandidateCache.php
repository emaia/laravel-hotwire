<?php

namespace Emaia\LaravelHotwire\Support;

use Closure;

final class ViteControllerCandidateCache
{
    /** @var array<string, array<string, array{local: list<string>, package: list<string>}>> */
    private array $candidates = [];

    /**
     * Reuse a manifest's controller candidates within the current request scope.
     *
     * @param  Closure(): array<string, array{local: list<string>, package: list<string>}>  $discover
     * @return array<string, array{local: list<string>, package: list<string>}>
     */
    public function remember(string $buildDirectory, Closure $discover): array
    {
        return $this->candidates[$buildDirectory] ??= $discover();
    }

    /** Discard every cached controller candidate map. */
    public function flush(): void
    {
        $this->candidates = [];
    }
}
