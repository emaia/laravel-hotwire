<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

final readonly class LaravelIdeaMetadataFile
{
    public function __construct(private Filesystem $files) {}

    /** @param  array<string, mixed>  $metadata */
    public function write(string $path, array $metadata): void
    {
        $existing = $this->read($path);
        $merged = $existing === null ? $metadata : $this->merge($existing, $metadata);

        $this->files->put(
            $path,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
        );
    }

    /** @return array<string, mixed>|null */
    private function read(string $path): ?array
    {
        if (! $this->files->exists($path)) {
            return null;
        }

        return json_decode($this->files->get($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function merge(array $existing, array $metadata): array
    {
        $existing['$schema'] ??= $metadata['$schema'];

        if (isset($metadata['blade']['components']['list'])) {
            $existing['blade']['components']['list'] = $this->mergeComponentList(
                $existing['blade']['components']['list'] ?? [],
                $metadata['blade']['components']['list'],
            );
        }

        if (isset($metadata['blade']['components']['phpNamespaces'])) {
            $existing['blade']['components']['phpNamespaces'] = $this->mergePhpNamespaces(
                $existing['blade']['components']['phpNamespaces'] ?? [],
                $metadata['blade']['components']['phpNamespaces'],
            );
        }

        if (isset($metadata['completions'])) {
            $existing['completions'] = $this->mergeCompletions($existing['completions'] ?? [], $metadata['completions']);
        }

        return $existing;
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @param  list<array<string, mixed>>  $hotwire
     * @return list<array<string, mixed>>
     */
    private function mergeComponentList(array $existing, array $hotwire): array
    {
        $existing = array_values(array_filter(
            $existing,
            fn (array $component): bool => ($component['namespace'] ?? null) !== 'hw',
        ));

        return [...$existing, ...$hotwire];
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @param  list<array<string, mixed>>  $hotwire
     * @return list<array<string, mixed>>
     */
    private function mergePhpNamespaces(array $existing, array $hotwire): array
    {
        $existing = array_values(array_filter(
            $existing,
            fn (array $namespace): bool => ($namespace['phpNamespace'] ?? null) !== '\\Emaia\\LaravelHotwire\\Components'
                || ($namespace['prefix'] ?? null) !== 'hw:',
        ));

        return [...$existing, ...$hotwire];
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @param  list<array<string, mixed>>  $hotwire
     * @return list<array<string, mixed>>
     */
    private function mergeCompletions(array $existing, array $hotwire): array
    {
        $hotwireConditions = array_map(fn (array $completion): array => $completion['condition'], $hotwire);
        $existing = array_values(array_filter(
            $existing,
            fn (array $completion): bool => ! in_array($completion['condition'] ?? [], $hotwireConditions, true),
        ));

        return [...$existing, ...$hotwire];
    }
}
