<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ControllerImports;
use Emaia\LaravelHotwire\Support\PackageInstaller;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class CheckCommand extends Command
{
    public $signature = 'hotwire:check
                        {--path=* : Paths to scan for blade files (default: resources/views)}
                        {--fix   : Publish missing/outdated controllers and add missing npm deps without prompting}
                        {--install : Run package manager install after adding missing npm deps}';

    public $description = 'Check that Stimulus controllers for used Hotwire components are published';

    public function __construct(
        private readonly Filesystem $files,
        private readonly PackageInstaller $packageInstaller,
        private readonly ControllerImports $imports,
    ) {
        parent::__construct();
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $prefix = config('hotwire.prefix', 'hwc');
        $paths = $this->scanPaths();
        $targetBase = resource_path('js/controllers');
        $registry = HotwireRegistry::make();

        $totalFiles = 0;
        ['components' => $usedComponentKeys, 'controllers' => $standaloneControllers] =
            $this->scanViews($paths, $prefix, $registry, $totalFiles);

        $this->line('Scanning '.implode(', ', array_map('basename', $paths))." ($totalFiles files)...");
        $this->line('');

        if (empty($usedComponentKeys) && empty($standaloneControllers)) {
            info('No Hotwire components or controllers found in views.');

            return self::SUCCESS;
        }

        ['issues' => $issues, 'controllers' => $controllers] = $this->reportStatus($usedComponentKeys, $prefix, $targetBase, $registry);

        // A controller already reported via its component must not be reported
        // (or published, or counted) again as a standalone usage.
        $standaloneControllers = array_diff_key($standaloneControllers, $controllers);

        $standaloneResult = $this->reportStandaloneControllers($standaloneControllers, $targetBase, $registry);
        $issues = array_merge($issues, $standaloneResult['issues']);
        $controllers = array_merge($controllers, $standaloneResult['controllers']);

        $required = $this->collectRequiredDependencies($controllers);
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
            $depsAdded = $this->writeMissingDependencies($missingDeps);

            if ($depsAdded > 0) {
                if ($this->shouldInstallDependencies()) {
                    return $this->installDependencies();
                }

                $this->line('');
                $this->line('<comment>Run your package manager install command to fetch the new dependencies.</comment>');
            }

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
     * Single pass over the blade files: collect both the Hotwire component keys
     * and the direct Stimulus controller usages, reading each file only once.
     *
     * Component detection recognizes both the configured prefix and the literal
     * 'hotwire' alias (registered globally by the service provider).
     *
     * @param  string[]  $paths
     * @return array{components: string[], controllers: array<string, ControllerDefinition>}
     */
    private function scanViews(array $paths, string $prefix, HotwireRegistry $registry, int &$totalFiles): array
    {
        $prefixes = array_unique([$prefix, 'hotwire']);
        $alt = implode('|', array_map(fn (string $p) => preg_quote($p, '/'), $prefixes));
        $componentPattern = '/<x-(?:'.$alt.')::([a-z][a-z0-9-]*)[\s\/>]/';

        $components = [];
        $controllers = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = Finder::create()->files()->name('*.blade.php')->in($path);

            foreach ($files as $file) {
                $totalFiles++;
                // Strip comments/scripts/styles once so neither components nor
                // controllers are detected inside commented-out or non-markup code.
                $content = $this->stripNonMarkup($file->getContents());

                preg_match_all($componentPattern, $content, $matches);

                foreach ($matches[1] as $key) {
                    $components[$key] = true;
                }

                $this->collectControllerUsages($content, $registry, $controllers);
            }
        }

        return ['components' => array_keys($components), 'controllers' => $controllers];
    }

    /**
     * Strip Blade comments and script/style blocks to avoid false positives
     * when scanning for data-controller attributes and stimulus_*() calls.
     */
    private function stripNonMarkup(string $content): string
    {
        $content = preg_replace('/{{--.*?--}}/s', '', $content);
        $content = preg_replace('/<script[\s>][\s\S]*?<\/script>/i', '', $content);

        return preg_replace('/<style[\s>][\s\S]*?<\/style>/i', '', $content);
    }

    /**
     * Collect direct Stimulus controller usages from already-stripped blade
     * content — raw data-controller attributes, stimulus_controller() /
     * stimulus()->controller() / ->controllers() calls, and stimulus_action() /
     * stimulus_target() references.
     *
     * Only controllers that exist in the package registry are kept; user-defined
     * controllers are silently ignored.
     *
     * @param  array<string, ControllerDefinition>  $found
     */
    private function collectControllerUsages(string $content, HotwireRegistry $registry, array &$found): void
    {
        $id = '[a-z][a-z0-9-]*(?:--[a-z][a-z0-9-]*)?';

        // 1. data-controller="foo bar"
        preg_match_all('/data-controller\s*=\s*["\']([^"\']+)["\']/', $content, $matches);

        foreach ($matches[1] as $value) {
            foreach (preg_split('/\s+/', trim($value)) as $identifier) {
                $this->keepRegistered($identifier, $registry, $found);
            }
        }

        // 2. ->controller('foo', ...) (incl. chained) / stimulus_controller('foo', ...)
        $singlePattern = '/->\s*controller\s*\(\s*[\'"]('.$id.')[\'"]'
            .'|stimulus_controller\s*\(\s*[\'"]('.$id.')[\'"]/';
        preg_match_all($singlePattern, $content, $singleMatches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        foreach ($singleMatches as $m) {
            $this->keepRegistered($m[1] ?? $m[2], $registry, $found);
        }

        // 3. stimulus()->controllers('a', 'b', ...) — variadic
        preg_match_all('/->\s*controllers\s*\(([^)]+)\)/', $content, $controllersMatches);

        foreach ($controllersMatches[1] as $args) {
            preg_match_all('/[\'"]('.$id.')[\'"]/', $args, $strings);

            foreach ($strings[1] as $identifier) {
                $this->keepRegistered($identifier, $registry, $found);
            }
        }

        // 4. stimulus_action('foo', ...) / stimulus_target('foo', ...)
        $refPattern = '/stimulus_action\s*\(\s*[\'"]('.$id.')[\'"]'
            .'|stimulus_target\s*\(\s*[\'"]('.$id.')[\'"]/';
        preg_match_all($refPattern, $content, $refMatches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        foreach ($refMatches as $m) {
            $this->keepRegistered($m[1] ?? $m[2], $registry, $found);
        }
    }

    /**
     * Record an identifier when it maps to a controller in the package registry.
     *
     * @param  array<string, ControllerDefinition>  $found
     */
    private function keepRegistered(?string $identifier, HotwireRegistry $registry, array &$found): void
    {
        if ($identifier === null || $identifier === '') {
            return;
        }

        if ($controller = $registry->controller($identifier)) {
            $found[$identifier] = $controller;
        }
    }

    /**
     * Report the status of controllers used directly (without a component wrapper)
     * and return issues and controller definitions compatible with the existing pipeline.
     *
     * @param  array<string, ControllerDefinition>  $standaloneControllers
     * @return array{issues: array<int, array{identifier: string, source_file: string, target_file: string}>, controllers: array<string, ControllerDefinition>}
     *
     * @throws FileNotFoundException
     */
    private function reportStandaloneControllers(array $standaloneControllers, string $targetBase, HotwireRegistry $registry): array
    {
        $issues = [];
        $controllers = [];
        $seenDeps = [];
        $controllersBase = $registry->basePath().'/resources/js/controllers';

        foreach ($standaloneControllers as $controller) {
            $this->checkController($controller, $targetBase, $controllersBase, $registry->basePath(), 'standalone', $issues, $controllers, $seenDeps);
        }

        return ['issues' => $issues, 'controllers' => $controllers];
    }

    /**
     * Print the per-controller status and return both the issues list and a map
     * of identifier → controller definition (used later for npm dependency checks).
     *
     * @param  string[]  $usedKeys
     * @return array{issues: array<int, array{identifier: string, source_file: string, target_file: string}>, controllers: array<string, ControllerDefinition>}
     *
     * @throws FileNotFoundException
     */
    private function reportStatus(array $usedKeys, string $prefix, string $targetBase, HotwireRegistry $registry): array
    {
        $issues = [];
        $controllers = [];
        $seenDeps = [];
        $controllersBase = $registry->basePath().'/resources/js/controllers';

        foreach ($usedKeys as $key) {
            $component = $registry->component($key);
            $tag = "<x-$prefix::$key>";

            if ($component === null) {
                continue;
            }

            if ($component->controllers === []) {
                $this->line("  <info>✓</info>  $tag  No controllers required");

                continue;
            }

            foreach ($registry->controllersForComponent($component) as $controller) {
                $this->checkController($controller, $targetBase, $controllersBase, $registry->basePath(), $tag, $issues, $controllers, $seenDeps);
            }
        }

        return ['issues' => $issues, 'controllers' => $controllers];
    }

    /**
     * Check and report the status of a single controller, collecting issues and
     * shared dependency checks.
     *
     * @param  array<int, array{identifier: string, source_file: string, target_file: string}>  $issues
     * @param  array<string, ControllerDefinition>  $controllers
     * @param  array<string, bool>  $seenDeps
     *
     * @throws FileNotFoundException
     */
    private function checkController(
        ControllerDefinition $controller,
        string $targetBase,
        string $controllersBase,
        string $packageBasePath,
        string $origin,
        array &$issues,
        array &$controllers,
        array &$seenDeps,
    ): void {
        $sourceFile = $controller->sourcePath($packageBasePath);
        $targetFile = $controller->relativeDir() === ''
            ? "$targetBase/{$controller->filename()}"
            : "$targetBase/{$controller->relativeDir()}/{$controller->filename()}";

        $controllers[$controller->identifier] = $controller;
        [$status, $symbol, $color] = $this->resolveStatus($targetFile, $sourceFile);

        $this->line("  <$color>$symbol</$color>  $controller->identifier  $status  <fg=gray>(used by $origin)</>");

        if ($status !== 'up to date') {
            $issues[] = [
                'identifier' => $controller->identifier,
                'source_file' => $sourceFile,
                'target_file' => $targetFile,
            ];
        }

        $this->reportSharedDeps($controller, $sourceFile, $controllersBase, $targetBase, $issues, $seenDeps);
    }

    /**
     * Verify the shared (non-controller) files a controller imports are published
     * and up to date. A controller can hash-match the package while a dependency
     * it imports (e.g. _form_errors.js) is missing — which would break the build.
     *
     * @param  array<int, array{identifier: string, source_file: string, target_file: string}>  $issues
     * @param  array<string, bool>  $seenDeps
     *
     * @throws FileNotFoundException
     */
    private function reportSharedDeps(
        ControllerDefinition $controller,
        string $sourceFile,
        string $controllersBase,
        string $targetBase,
        array &$issues,
        array &$seenDeps,
    ): void {
        foreach ($this->imports->sharedDependencies($sourceFile, $controllersBase) as $depSource) {
            $depTarget = $this->imports->targetPath($depSource, $controllersBase, $targetBase);

            if (isset($seenDeps[$depTarget])) {
                continue;
            }
            $seenDeps[$depTarget] = true;

            $name = basename($depSource);
            [$status, $symbol, $color] = $this->resolveStatus($depTarget, $depSource);

            $this->line("  <$color>$symbol</$color>  $name  $status  <fg=gray>(required by $controller->identifier)</>");

            if ($status !== 'up to date') {
                $issues[] = [
                    'identifier' => $name,
                    'source_file' => $depSource,
                    'target_file' => $depTarget,
                ];
            }
        }
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
     * Aggregate npm package requirements from the registry and annotate them
     * with the identifiers that require each one.
     *
     * @param  array<string, ControllerDefinition>  $controllers  identifier => controller definition
     * @return array<string, array{version: string, used_by: string[]}>
     */
    private function collectRequiredDependencies(array $controllers): array
    {
        $collected = [];

        foreach ($controllers as $identifier => $controller) {
            foreach ($controller->npm as $package => $version) {
                if (! isset($collected[$package])) {
                    $collected[$package] = [
                        'version' => $version,
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

    /**
     * Print the required npm dependency section and return the packages missing
     * from the app's package.json (package name => expected version).
     *
     * @param  array<string, array{version: string, used_by: string[]}>  $required
     * @return array<string, string>
     *
     * @throws FileNotFoundException
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
                $this->line("  <info>✓</info>  $package {$info['version']}  <fg=gray>(used by $usedBy)</>");

                continue;
            }

            $this->line("  <error>✗</error>  $package {$info['version']}  <fg=gray>missing from package.json (used by $usedBy)</>");
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
            $this->line("<comment>$count controller(s) need attention.</comment>");
        }

        if (! empty($missingDeps)) {
            $count = count($missingDeps);
            $this->line("<comment>$count npm dependency(ies) missing from package.json.</comment>");
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

        return confirm('Publish missing/outdated controllers and add missing npm deps?');
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

    /** @param array<string, string> $missingDeps package name => version
     * @throws FileNotFoundException
     */
    private function writeMissingDependencies(array $missingDeps): int
    {
        if (empty($missingDeps)) {
            return 0;
        }

        $packageJsonPath = base_path('package.json');

        if (! $this->files->exists($packageJsonPath)) {
            return 0;
        }

        $json = json_decode($this->files->get($packageJsonPath), true) ?: [];
        $devDeps = $json['devDependencies'] ?? [];

        foreach ($missingDeps as $package => $version) {
            $devDeps[$package] = $version;
            info("Added to devDependencies: $package $version");
        }

        $json['devDependencies'] = $devDeps;

        $this->files->put(
            $packageJsonPath,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        return count($missingDeps);
    }

    private function shouldInstallDependencies(): bool
    {
        if ($this->option('install')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        $manager = $this->packageInstaller->detect($this->files);

        return confirm("Run $manager install now?");
    }

    private function installDependencies(): int
    {
        $manager = $this->packageInstaller->detect($this->files);
        $command = implode(' ', $this->packageInstaller->command($manager));

        $this->line('');
        info("Running $command...");

        $exitCode = $this->packageInstaller->install($manager, $this);

        if ($exitCode !== self::SUCCESS) {
            $this->components->error("$command failed.");

            return self::FAILURE;
        }

        info("$command completed.");

        return self::SUCCESS;
    }
}
