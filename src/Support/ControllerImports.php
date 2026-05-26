<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

/**
 * Resolves the shared (non-controller) files a Stimulus controller imports, so
 * both PublishControllersCommand and CheckCommand can treat those dependencies
 * the same way (publish them / verify they are present and up to date).
 */
readonly class ControllerImports
{
    public function __construct(private Filesystem $files) {}

    /**
     * Resolve every shared dependency reachable from a controller, following
     * relative imports recursively. Returns absolute paths located under
     * $baseDir. Other *_controller files are excluded — they are published
     * independently.
     *
     * @return string[]
     *
     * @throws FileNotFoundException
     */
    public function sharedDependencies(string $sourceFile, string $baseDir): array
    {
        $baseDir = (string) realpath($baseDir);
        $deps = [];
        $visited = [];
        $queue = [$sourceFile];

        while ($queue !== []) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            foreach ($this->extractRelativeImports($current) as $importPath) {
                $resolved = $this->resolveImport($current, $importPath);

                if ($resolved === null || ! str_starts_with($resolved, $baseDir.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                if (preg_match('/_controller\.(js|ts)$/', $resolved)) {
                    continue;
                }

                $deps[$resolved] = true;
                $queue[] = $resolved;
            }
        }

        return array_keys($deps);
    }

    /**
     * Map a resolved dependency to its path inside the published controllers dir.
     */
    public function targetPath(string $resolved, string $baseDir, string $targetBase): string
    {
        $baseDir = (string) realpath($baseDir);
        $relative = ltrim(str_replace('\\', '/', substr($resolved, strlen($baseDir))), '/');

        return $targetBase.'/'.$relative;
    }

    /** @return string[]
     * @throws FileNotFoundException
     */
    public function extractRelativeImports(string $filePath): array
    {
        if (! $this->files->exists($filePath)) {
            return [];
        }

        $source = $this->files->get($filePath);
        $imports = [];

        $patterns = [
            '/\b(?:import|from|require)\s*\(?\s*[\'"](\.[^\'"]+)[\'"]/',
            '/@import\s+(?:url\()?\s*[\'"](\.[^\'"]+)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $matches)) {
                $imports = array_merge($imports, $matches[1]);
            }
        }

        return array_values(array_unique($imports));
    }

    public function resolveImport(string $fromFile, string $importPath): ?string
    {
        $candidate = dirname($fromFile).'/'.$importPath;

        $direct = realpath($candidate);
        if ($direct && is_file($direct)) {
            return $direct;
        }

        foreach (['.js', '.ts', '.mjs', '.css'] as $ext) {
            $withExt = realpath($candidate.$ext);
            if ($withExt && is_file($withExt)) {
                return $withExt;
            }
        }

        foreach (['.js', '.ts', '.mjs'] as $ext) {
            $index = realpath($candidate.'/index'.$ext);
            if ($index && is_file($index)) {
                return $index;
            }
        }

        return null;
    }
}
