<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ControllerImports;
use Emaia\LaravelHotwire\Support\LoaderStub;
use Emaia\LaravelHotwire\Support\PackageInstaller;
use Emaia\LaravelHotwire\Support\PackageMarker;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class CheckCommand extends Command
{
    public $signature = 'hotwire:check
                        {--path=* : Paths to scan for blade files (default: resources/views)}
                        {--fix   : Publish missing/outdated controllers and add missing npm deps without prompting}
                        {--install : Run package manager install after adding missing npm deps}';

    public $description = 'Check that Stimulus controllers used by your views (via components or directly) are published';

    /** @var array<int, array{key: string, line: string}> Buffered "needs attention" entries, printed at the end alphabetically so they sit right next to the prompt. */
    private array $problemLines = [];

    /** @var string[] OK status lines for component-driven controllers, kept in component-scan order so each component's controllers stay grouped. */
    private array $okComponentControllerLines = [];

    /** @var string[] OK status lines for `<x-hwc::*>` components without controllers, kept in alphabetical scan order. */
    private array $okNoControllerLines = [];

    /** @var string[] OK status lines for standalone controllers, in alphabetical order. */
    private array $okStandaloneLines = [];

    /** @var array<int, array{key: string, line: string}> OK status lines for shared dependencies (`_*.js`, `*.css`), sorted by basename before emission. */
    private array $okHelperLines = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly PackageInstaller $packageInstaller,
        private readonly ControllerImports $imports,
        private readonly PackageMarker $marker,
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

        $this->emitScanOutput();

        $required = $this->collectRequiredDependencies($controllers);
        $missingDeps = $this->reportDependencies($required);
        $excludedFromStub = $this->detectStubExclusions($controllers, $registry);

        $this->line('');

        $hasControllerIssues = ! empty($issues);
        $hasMissingDeps = ! empty($missingDeps);
        $hasStubDrift = ! empty($excludedFromStub);
        $hasProblemLines = ! empty($this->problemLines);

        if (! $hasControllerIssues && ! $hasMissingDeps && ! $hasStubDrift && ! $hasProblemLines) {
            info('All controllers up to date.');

            return self::SUCCESS;
        }

        $this->printProblemLines();
        $this->printIssueSummary($issues, $missingDeps, $excludedFromStub);

        // Only user-owned divergences are present — nothing for --fix to do.
        // Report visibility but keep the exit code green (e.g. CI stays happy).
        if (! $hasControllerIssues && ! $hasMissingDeps && ! $hasStubDrift) {
            return self::SUCCESS;
        }

        if ($this->shouldFix()) {
            $this->publishIssues($issues);
            $depsAdded = $this->writeMissingDependencies($missingDeps);
            $this->regenerateLoaderStub($excludedFromStub, $registry);

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

    /**
     * Identify com-dep controllers used in views but excluded from the
     * auto-generated loader stub. Returns identifiers requiring an --fix
     * regeneration. Skips silently when the user stub is missing or
     * hand-written (no marker).
     *
     * @param  array<string, ControllerDefinition>  $usedControllers
     * @return string[]
     */
    private function detectStubExclusions(array $usedControllers, HotwireRegistry $registry): array
    {
        $stubPath = resource_path('js/controllers/index.js');

        if (! $this->files->exists($stubPath)) {
            return [];
        }

        $included = LoaderStub::includedComDepControllers($this->files->get($stubPath), $registry);

        if ($included === null) {
            return [];
        }

        $missing = [];

        foreach ($usedControllers as $identifier => $controller) {
            if (empty($controller->npm)) {
                continue;
            }
            if (in_array($identifier, $included, true)) {
                continue;
            }
            $missing[] = $identifier;
        }

        sort($missing);

        foreach ($missing as $identifier) {
            $this->problemLines[] = [
                'key' => $identifier,
                'line' => "  <error>✗</error>  $identifier  excluded from loader stub  <fg=gray>(used in views; re-run install with --with-deps including $identifier, or `hotwire:check --fix`)</>",
            ];
        }

        return $missing;
    }

    /**
     * Regenerate the loader stub including every com-dep controller that
     * survived stub-drift detection plus those already included.
     *
     * @param  string[]  $excludedFromStub
     */
    private function regenerateLoaderStub(array $excludedFromStub, HotwireRegistry $registry): void
    {
        if ($excludedFromStub === []) {
            return;
        }

        $stubPath = resource_path('js/controllers/index.js');

        if (! $this->files->exists($stubPath)) {
            return;
        }

        $existing = LoaderStub::includedComDepControllers($this->files->get($stubPath), $registry) ?? [];
        $merged = array_values(array_unique(array_merge($existing, $excludedFromStub)));
        sort($merged);

        $this->files->put($stubPath, LoaderStub::generate($registry, $merged));

        info('Regenerated resources/js/controllers/index.js to include: '.implode(', ', $excludedFromStub));
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

        ksort($standaloneControllers);

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

        sort($usedKeys);

        foreach ($usedKeys as $key) {
            $component = $registry->component($key);
            $tag = "<x-$prefix::$key>";

            if ($component === null) {
                continue;
            }

            if ($component->controllers === []) {
                $this->okNoControllerLines[] = "  <info>✓</info>  $tag  No controllers required";

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

        $line = "  <$color>$symbol</$color>  $controller->identifier  $status  <fg=gray>(used by $origin)</>";

        if ($status === 'up to date' || $status === 'auto-loaded from vendor') {
            if ($origin === 'standalone') {
                $this->okStandaloneLines[] = $line;
            } else {
                $this->okComponentControllerLines[] = $line;
            }
        } else {
            $this->problemLines[] = ['key' => $controller->identifier, 'line' => $line];

            // User-owned divergence is informational: --fix can't (and shouldn't) touch it.
            if ($status !== 'diverged (user-owned)') {
                $issues[] = [
                    'identifier' => $controller->identifier,
                    'source_file' => $sourceFile,
                    'target_file' => $targetFile,
                ];
            }
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
        $controllerTarget = $controller->relativeDir() === ''
            ? "$targetBase/{$controller->filename()}"
            : "$targetBase/{$controller->relativeDir()}/{$controller->filename()}";

        if (! $this->files->exists($controllerTarget)) {
            return;
        }

        $deps = $this->imports->sharedDependencies($sourceFile, $controllersBase);
        usort($deps, fn (string $a, string $b) => strcmp(basename($a), basename($b)));

        foreach ($deps as $depSource) {
            $depTarget = $this->imports->targetPath($depSource, $controllersBase, $targetBase);

            if (isset($seenDeps[$depTarget])) {
                continue;
            }
            $seenDeps[$depTarget] = true;

            $name = basename($depSource);
            [$status, $symbol, $color] = $this->resolveStatus($depTarget, $depSource, true);

            $line = "  <$color>$symbol</$color>  $name  $status  <fg=gray>(required by $controller->identifier)</>";

            if ($status === 'up to date' || $status === 'auto-loaded from vendor') {
                $this->okHelperLines[] = ['key' => $name, 'line' => $line];
            } else {
                $this->problemLines[] = ['key' => $name, 'line' => $line];

                if ($status !== 'diverged (user-owned)') {
                    $issues[] = [
                        'identifier' => $name,
                        'source_file' => $depSource,
                        'target_file' => $depTarget,
                    ];
                }
            }
        }
    }

    /** @return array{string, string, string} [status, symbol, color] */
    private function resolveStatus(string $targetFile, string $sourceFile, bool $isRequired = false): array
    {
        if (! $this->files->exists($targetFile)) {
            return $isRequired
                ? ['not published', '✗', 'error']
                : ['auto-loaded from vendor', '✓', 'info'];
        }

        if ($this->files->exists($sourceFile) && $this->files->hash($sourceFile) !== $this->files->hash($targetFile)) {
            if (! $this->marker->isPackageOwned($targetFile)) {
                return ['diverged (user-owned)', '~', 'comment'];
            }

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

            $this->problemLines[] = [
                'key' => $package,
                'line' => "  <error>✗</error>  $package {$info['version']}  <fg=gray>missing from package.json (used by $usedBy)</>",
            ];
            $missing[$package] = $info['version'];
        }

        return $missing;
    }

    private function emitScanOutput(): void
    {
        foreach ($this->okComponentControllerLines as $line) {
            $this->line($line);
        }

        foreach ($this->okNoControllerLines as $line) {
            $this->line($line);
        }

        if ($this->okStandaloneLines !== []) {
            $this->line('');
            sort($this->okStandaloneLines);
            foreach ($this->okStandaloneLines as $line) {
                $this->line($line);
            }
        }

        if ($this->okHelperLines !== []) {
            $this->line('');
            usort($this->okHelperLines, fn (array $a, array $b) => strcmp($a['key'], $b['key']));
            foreach ($this->okHelperLines as $entry) {
                $this->line($entry['line']);
            }
        }
    }

    private function printProblemLines(): void
    {
        if ($this->problemLines === []) {
            return;
        }

        usort($this->problemLines, fn (array $a, array $b) => strcmp($a['key'], $b['key']));

        $this->line('<options=bold>Needs attention:</>');
        foreach ($this->problemLines as $entry) {
            $this->line($entry['line']);
        }
        $this->line('');
    }

    /**
     * @param  array<int, array{identifier: string, source_file: string, target_file: string}>  $issues
     * @param  array<string, string>  $missingDeps
     * @param  string[]  $excludedFromStub
     */
    private function printIssueSummary(array $issues, array $missingDeps, array $excludedFromStub = []): void
    {
        if (! empty($issues)) {
            $count = count($issues);
            $this->line("<comment>$count controller(s) need attention.</comment>");
        }

        if (! empty($missingDeps)) {
            $count = count($missingDeps);
            $this->line("<comment>$count npm dependency(ies) missing from package.json.</comment>");
        }

        if (! empty($excludedFromStub)) {
            $count = count($excludedFromStub);
            $this->line("<comment>$count controller(s) used in views but excluded from controllers/index.js — --fix will regenerate.</comment>");
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
            if (! $this->marker->isPackageOwned($issue['target_file'])) {
                warning("Skipped \"{$issue['identifier']}\": {$issue['target_file']} is user-owned (missing package marker). Rename or remove the file, or add `".$this->markerHint($issue['target_file']).'` on its first line to opt in to package updates.');

                continue;
            }

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
        $added = $this->packageInstaller->addDevDependencies($this->files, $missingDeps);

        foreach ($added as $package => $version) {
            info("Added to devDependencies: $package $version");
        }

        return count($added);
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

    private function markerHint(string $path): string
    {
        return str_ends_with($path, '.css') ? '/* @hotwire-package */' : '// @hotwire-package';
    }
}
