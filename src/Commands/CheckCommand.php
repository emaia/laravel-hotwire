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

    private const array CORE_NPM_PACKAGES = [
        '@hotwired/stimulus',
        '@hotwired/turbo',
        '@emaia/stimulus-dynamic-loader',
    ];

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

        ['issues' => $issues, 'sources' => $sources] = $this->reportStatus($usedKeys, $prefix, $targetBase, $sourceBase);

        $required = $this->collectRequiredDependencies($sources);
        $missingDeps = $this->reportDependencies($required);

        $this->line('');

        $hasControllerIssues = ! empty($issues);
        $hasMissingDeps = ! empty($missingDeps);

        if (! $hasControllerIssues && ! $hasMissingDeps) {
            info('All controllers up to date.');

            return self::SUCCESS;
        }

        $this->printIssueSummary($issues, $missingDeps);

        if ($this->shouldFix()) {
            $this->publishIssues($issues);
            $this->writeMissingDependencies($missingDeps);

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
     * Recognizes both the configured prefix and the literal 'hotwire' alias
     * (registered globally by the service provider).
     *
     * @param  string[]  $paths
     * @return string[]
     */
    private function detectUsedComponents(array $paths, string $prefix, int &$totalFiles): array
    {
        $prefixes = array_unique([$prefix, 'hotwire']);
        $alt = implode('|', array_map(fn (string $p) => preg_quote($p, '/'), $prefixes));
        $pattern = '/<x-(?:'.$alt.')::([a-z][a-z0-9-]*)[\s\/>]/';
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
     * Print the per-controller status and return both the issues list and a map
     * of identifier → source file (used later for npm dependency extraction).
     *
     * @param  string[]  $usedKeys
     * @return array{issues: array<int, array{identifier: string, source_file: string, target_file: string}>, sources: array<string, string>}
     */
    private function reportStatus(array $usedKeys, string $prefix, string $targetBase, string $sourceBase): array
    {
        $issues = [];
        $sources = [];

        foreach ($usedKeys as $key) {
            $class = LaravelHotwireServiceProvider::COMPONENTS[$key] ?? null;
            $tag = "<x-{$prefix}::{$key}>";

            if ($class === null) {
                continue;
            }

            if (! is_a($class, HasStimulusControllers::class, true)) {
                $this->line("  <info>✓</info>  {$tag}  No controllers required");

                continue;
            }

            foreach ($class::stimulusControllers() as $identifier) {
                [$dir, $name] = $this->identifierToParts($identifier);
                $sourceFile = $this->resolveSourceFile($sourceBase, $dir, $name);
                $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
                $filename = "{$name}_controller.{$ext}";
                $targetFile = $dir === ''
                    ? "{$targetBase}/{$filename}"
                    : "{$targetBase}/{$dir}/{$filename}";

                $sources[$identifier] = $sourceFile;

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

        return ['issues' => $issues, 'sources' => $sources];
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

    /**
     * Parse controller source files and aggregate their non-core npm packages,
     * annotated with the identifiers that require each one.
     *
     * @param  array<string, string>  $sources  identifier => source file path
     * @return array<string, array{version: string, used_by: string[]}>
     */
    private function collectRequiredDependencies(array $sources): array
    {
        $packageVersions = $this->ownPackageDependencies();
        $collected = [];

        foreach ($sources as $identifier => $sourceFile) {
            if (! $this->files->exists($sourceFile)) {
                continue;
            }

            foreach ($this->extractPackages($this->files->get($sourceFile)) as $package) {
                if (in_array($package, self::CORE_NPM_PACKAGES, true)) {
                    continue;
                }

                if (! isset($collected[$package])) {
                    $collected[$package] = [
                        'version' => $packageVersions[$package] ?? '*',
                        'used_by' => [],
                    ];
                }

                if (! in_array($identifier, $collected[$package]['used_by'], true)) {
                    $collected[$package]['used_by'][] = $identifier;
                }
            }
        }

        ksort($collected);

        return $collected;
    }

    /** @return string[] */
    private function extractPackages(string $content): array
    {
        preg_match_all('/(?:from|import)\s+["\']([^"\']+)["\']/', $content, $matches);

        $packages = [];

        foreach ($matches[1] as $spec) {
            if (str_starts_with($spec, '.') || str_starts_with($spec, '/')) {
                continue;
            }

            $packages[$this->packageBaseName($spec)] = true;
        }

        return array_keys($packages);
    }

    private function packageBaseName(string $spec): string
    {
        if (str_starts_with($spec, '@')) {
            $parts = explode('/', $spec, 3);

            return isset($parts[1]) ? $parts[0].'/'.$parts[1] : $parts[0];
        }

        return explode('/', $spec, 2)[0];
    }

    /** @return array<string, string> */
    private function ownPackageDependencies(): array
    {
        $path = realpath(__DIR__.'/../../package.json');

        if (! $path) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true) ?: [];

        return array_merge(
            $json['dependencies'] ?? [],
            $json['devDependencies'] ?? []
        );
    }

    /**
     * Print the required npm dependency section and return the packages missing
     * from the app's package.json (package name => expected version).
     *
     * @param  array<string, array{version: string, used_by: string[]}>  $required
     * @return array<string, string>
     */
    private function reportDependencies(array $required): array
    {
        if (empty($required)) {
            return [];
        }

        $this->line('');
        $this->line('<options=bold>Required npm dependencies:</>');

        $packageJsonPath = base_path('package.json');

        if (! $this->files->exists($packageJsonPath)) {
            $this->line('  <comment>package.json not found — skipping npm dependency check.</comment>');

            return [];
        }

        $appJson = json_decode($this->files->get($packageJsonPath), true) ?: [];
        $installed = array_merge(
            $appJson['dependencies'] ?? [],
            $appJson['devDependencies'] ?? []
        );

        $missing = [];

        foreach ($required as $package => $info) {
            $usedBy = implode(', ', $info['used_by']);

            if (array_key_exists($package, $installed)) {
                $this->line("  <info>✓</info>  {$package} {$info['version']}  <fg=gray>(used by {$usedBy})</>");

                continue;
            }

            $this->line("  <error>✗</error>  {$package} {$info['version']}  <fg=gray>missing from package.json (used by {$usedBy})</>");
            $missing[$package] = $info['version'];
        }

        return $missing;
    }

    /**
     * @param  array<int, array{identifier: string, source_file: string, target_file: string}>  $issues
     * @param  array<string, string>  $missingDeps
     */
    private function printIssueSummary(array $issues, array $missingDeps): void
    {
        if (! empty($issues)) {
            $count = count($issues);
            $this->line("<comment>{$count} controller(s) need attention.</comment>");
        }

        if (! empty($missingDeps)) {
            $count = count($missingDeps);
            $this->line("<comment>{$count} npm dependency(ies) missing from package.json.</comment>");
        }

        $this->line('');
    }

    private function shouldFix(): bool
    {
        if ($this->option('fix')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm('Publish missing/outdated controllers and add missing npm deps?', default: true);
    }

    /** @param array<int, array{identifier: string, source_file: string, target_file: string}> $issues */
    private function publishIssues(array $issues): void
    {
        foreach ($issues as $issue) {
            $targetDir = dirname($issue['target_file']);
            $this->files->ensureDirectoryExists($targetDir);
            $this->files->copy($issue['source_file'], $issue['target_file']);
            info("Published: {$issue['identifier']}");
        }
    }

    /** @param array<string, string> $missingDeps package name => version */
    private function writeMissingDependencies(array $missingDeps): void
    {
        if (empty($missingDeps)) {
            return;
        }

        $packageJsonPath = base_path('package.json');

        if (! $this->files->exists($packageJsonPath)) {
            return;
        }

        $json = json_decode($this->files->get($packageJsonPath), true) ?: [];
        $devDeps = $json['devDependencies'] ?? [];

        foreach ($missingDeps as $package => $version) {
            $devDeps[$package] = $version;
            info("Added to devDependencies: {$package} {$version}");
        }

        $json['devDependencies'] = $devDeps;

        $this->files->put(
            $packageJsonPath,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        $this->line('');
        $this->line('<comment>Run your package manager install command to fetch the new dependencies.</comment>');
    }

    private function resolveSourceFile(string $sourceBase, string $dir, string $name): string
    {
        $base = $dir === '' ? $sourceBase : "{$sourceBase}/{$dir}";

        foreach (['.js', '.ts'] as $ext) {
            $path = "{$base}/{$name}_controller{$ext}";
            if ($this->files->exists($path)) {
                return $path;
            }
        }

        return "{$base}/{$name}_controller.js";
    }

    /** @return array{string, string} [relative_dir, name] */
    private function identifierToParts(string $identifier): array
    {
        if (str_contains($identifier, '--')) {
            [$dir, $name] = explode('--', $identifier, 2);
        } else {
            $dir = '';
            $name = $identifier;
        }

        return [$dir, str_replace('-', '_', $name)];
    }
}
