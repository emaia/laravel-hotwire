<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class CheckCommand extends Command
{
    public $signature = 'hotwire:check
                        {--path=* : Paths to scan for blade files (default: resources/views)}
                        {--fix   : Publish missing/outdated controllers without prompting}';

    public $description = 'Check that Stimulus controllers for used Hotwire components are published';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $prefix = config('hotwire.prefix', 'hwc');
        $paths = $this->scanPaths();
        $targetBase = resource_path('js/controllers');
        $sourceBase = realpath(__DIR__.'/../../resources/js/controllers');

        $totalFiles = 0;
        $usedKeys = $this->detectUsedComponents($paths, $prefix, $totalFiles);

        $this->line('Scanning '.implode(', ', array_map('basename', $paths))." ({$totalFiles} files)...");
        $this->line('');

        if (empty($usedKeys)) {
            info('No Hotwire components found in views.');

            return self::SUCCESS;
        }

        $issues = $this->reportStatus($usedKeys, $prefix, $targetBase, $sourceBase);

        $this->line('');

        if (empty($issues)) {
            info('All controllers up to date.');

            return self::SUCCESS;
        }

        $count = count($issues);
        $this->line("<comment>{$count} controller(s) need attention.</comment>");
        $this->line('');

        if ($this->shouldFix()) {
            $this->publishIssues($issues, $targetBase, $sourceBase);

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /** @return string[] */
    private function scanPaths(): array
    {
        $paths = $this->option('path');

        if (empty($paths)) {
            return [resource_path('views')];
        }

        return (array) $paths;
    }

    /**
     * Scan blade files and return deduplicated component keys found.
     *
     * @param  string[]  $paths
     * @return string[]
     */
    private function detectUsedComponents(array $paths, string $prefix, int &$totalFiles): array
    {
        $pattern = '/<x-'.preg_quote($prefix, '/').'-([a-z][a-z0-9-]*)[\s\/>]/';
        $found = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = Finder::create()->files()->name('*.blade.php')->in($path);

            foreach ($files as $file) {
                $totalFiles++;
                preg_match_all($pattern, $file->getContents(), $matches);

                foreach ($matches[1] as $key) {
                    $found[$key] = true;
                }
            }
        }

        return array_keys($found);
    }

    /**
     * Print the status table and return identifiers with issues.
     *
     * @param  string[]  $usedKeys
     * @return array<int, array{identifier: string, source_file: string, target_file: string}>
     */
    private function reportStatus(array $usedKeys, string $prefix, string $targetBase, string $sourceBase): array
    {
        $issues = [];

        foreach ($usedKeys as $key) {
            $class = LaravelHotwireServiceProvider::COMPONENTS[$key] ?? null;
            $tag = "<x-{$prefix}-{$key}>";

            if ($class === null) {
                continue;
            }

            if (! is_a($class, HasStimulusControllers::class, true)) {
                $this->line("  <info>✓</info>  {$tag}  No controllers required");

                continue;
            }

            foreach ($class::stimulusControllers() as $identifier) {
                [$dir, $name] = $this->identifierToParts($identifier);
                $filename = "{$name}_controller.js";
                $targetFile = "{$targetBase}/{$dir}/{$filename}";
                $sourceFile = "{$sourceBase}/{$dir}/{$filename}";

                [$status, $symbol, $color] = $this->resolveStatus($targetFile, $sourceFile);

                $this->line("  <{$color}>{$symbol}</{$color}>  {$identifier}  {$status}  <fg=gray>(used by {$tag})</>");

                if ($status !== 'up to date') {
                    $issues[] = [
                        'identifier' => $identifier,
                        'source_file' => $sourceFile,
                        'target_file' => $targetFile,
                    ];
                }
            }
        }

        return $issues;
    }

    /** @return array{string, string, string} [status, symbol, color] */
    private function resolveStatus(string $targetFile, string $sourceFile): array
    {
        if (! $this->files->exists($targetFile)) {
            return ['not published', '✗', 'error'];
        }

        if ($this->files->exists($sourceFile) && $this->files->hash($sourceFile) !== $this->files->hash($targetFile)) {
            return ['outdated', '!', 'comment'];
        }

        return ['up to date', '✓', 'info'];
    }

    private function shouldFix(): bool
    {
        if ($this->option('fix')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm('Publish missing/outdated controllers?', default: true);
    }

    /** @param array<int, array{identifier: string, source_file: string, target_file: string}> $issues */
    private function publishIssues(array $issues, string $targetBase, string $sourceBase): void
    {
        foreach ($issues as $issue) {
            $targetDir = dirname($issue['target_file']);
            $this->files->ensureDirectoryExists($targetDir);
            $this->files->copy($issue['source_file'], $issue['target_file']);
            info("Published: {$issue['identifier']}");
        }
    }

    /** @return array{string, string} [relative_dir, name] */
    private function identifierToParts(string $identifier): array
    {
        $parts = explode('--', $identifier, 2);
        $dir = str_replace('--', '/', $parts[0]);
        $name = str_replace('-', '_', $parts[1] ?? '');

        return [$dir, $name];
    }
}
