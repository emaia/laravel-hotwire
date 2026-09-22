<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ApplicationPresetValidator;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\ControllerImports;
use Emaia\LaravelHotwire\Support\ControllerLoadConfiguration;
use Emaia\LaravelHotwire\Support\ControllerLoadPlan;
use Emaia\LaravelHotwire\Support\ControllerOrigin;
use Emaia\LaravelHotwire\Support\ControllerResolver;
use Emaia\LaravelHotwire\Support\CssImports;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\GeneratedStyleBundle;
use Emaia\LaravelHotwire\Support\LoaderStub;
use Emaia\LaravelHotwire\Support\PackageInstaller;
use Emaia\LaravelHotwire\Support\PackageMarker;
use Emaia\LaravelHotwire\Support\PresetSourceException;
use Emaia\LaravelHotwire\Support\PresetSourceResolver;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class CheckCommand extends Command
{
    private const string LAZY_LOADER_PACKAGE = '@emaia/stimulus-lazy-loader';

    private const string LAZY_LOADER_VERSION = '^2.0.0';

    public $signature = 'hotwire:check
                        {--path=* : Paths to scan for blade files (default: resources/views)}
                        {--preset=* : Application preset names or paths to validate (repeatable)}
                        {--fix   : Apply safe package-owned fixes without prompting}
                        {--skip-install : Do not run the package manager (bun/npm/pnpm/yarn) install after --fix adds new deps}';

    public $description = 'Check Stimulus controllers, dependencies, and generated CSS';

    /** @var array<int, array{key: string, line: string}> Buffered "needs attention" entries, printed at the end alphabetically so they sit right next to the prompt. */
    private array $problemLines = [];

    /** @var string[] OK status lines for component-driven controllers, kept in component-scan order so each component's controllers stay grouped. */
    private array $okComponentControllerLines = [];

    /** @var string[] OK status lines for `<x-hw::*>` components without controllers, kept in alphabetical scan order. */
    private array $okNoControllerLines = [];

    /** @var string[] OK status lines for standalone controllers, in alphabetical order. */
    private array $okStandaloneLines = [];

    /** @var array<int, array{key: string, line: string}> OK status lines for shared dependencies (`_*.js`, `*.css`), sorted by basename before emission. */
    private array $okHelperLines = [];

    /** @var string[] Valid application preset lines. */
    private array $okStyleLines = [];

    /** @var array<int, array{target: string, path: string, observed: string, expected: string}> */
    private array $styleFixes = [];

    private ?ControllerResolver $controllerResolver = null;

    public function __construct(
        private readonly Filesystem $files,
        private readonly PackageInstaller $packageInstaller,
        private readonly ControllerImports $imports,
        private readonly PackageMarker $marker,
        private readonly CssModuleManifest $styleManifest,
        private readonly CssPresetFiles $presetFiles,
        private readonly GeneratedStyleBundle $styleBundle,
        private readonly ApplicationPresetValidator $applicationPresets,
        private readonly CssImports $cssImports,
    ) {
        parent::__construct();
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $this->resetState();
        $prefix = config('hotwire.prefix', 'hw');
        $paths = $this->scanPaths();
        $targetBase = resource_path('js/controllers');
        $registry = HotwireRegistry::make();

        $totalFiles = 0;
        ['components' => $usedComponentKeys, 'controllers' => $standaloneControllers] =
            $this->scanViews($paths, $prefix, $registry, $totalFiles);
        $styleIssues = $this->reportStyleCoverage($usedComponentKeys, $registry);

        $this->line('Scanning '.implode(', ', array_map('basename', $paths))." ($totalFiles files)...");
        $this->line('');

        try {
            $configuration = ControllerLoadConfiguration::fromConfig();
            $this->controllerResolver = new ControllerResolver($this->files, $registry, $targetBase);
            $this->validateLazyLoaderVersion();
            $policyDrift = $this->detectControllerPolicyDrift($registry, $configuration);
            $configuredControllers = $this->configuredPackageControllers($registry, $configuration);
        } catch (RuntimeException $exception) {
            warning($exception->getMessage());

            return self::FAILURE;
        }

        if (empty($usedComponentKeys) && empty($standaloneControllers) && $configuredControllers === [] && $styleIssues === 0
            && $this->problemLines === [] && $this->okStyleLines === []) {
            info('No Hotwire components or controllers found in views.');

            if (! $policyDrift) {
                return self::SUCCESS;
            }
        }

        try {
            ['issues' => $issues, 'controllers' => $controllers, 'reported' => $reportedControllers] = $this->reportStatus($usedComponentKeys, $targetBase, $registry);

            // A controller already reported via its component must not be reported
            // (or published, or counted) again as a standalone usage.
            $standaloneControllers = array_diff_key($standaloneControllers, $reportedControllers);

            $standaloneResult = $this->reportStandaloneControllers($standaloneControllers, $targetBase, $registry);
            $issues = array_merge($issues, $standaloneResult['issues']);
            $controllers = array_merge($controllers, $standaloneResult['controllers'], $configuredControllers);
        } catch (RuntimeException $exception) {
            warning($exception->getMessage());

            return self::FAILURE;
        }

        $this->emitScanOutput();

        $required = $this->collectRequiredDependencies($controllers);
        $missingDeps = $this->reportDependencies($required);
        $packageControllers = array_filter(
            $controllers,
            fn (ControllerDefinition $_controller, string $identifier): bool => $this->resolver()->resolve($identifier)->origin === ControllerOrigin::Package,
            ARRAY_FILTER_USE_BOTH,
        );
        $excludedFromStub = $this->detectStubExclusions($packageControllers);

        $this->line('');

        $hasControllerIssues = ! empty($issues);
        $hasMissingDeps = ! empty($missingDeps);
        $hasPolicyDrift = $policyDrift;
        $hasStubDrift = ! empty($excludedFromStub);
        $hasProblemLines = ! empty($this->problemLines);

        if (! $hasControllerIssues && ! $hasMissingDeps && ! $hasPolicyDrift && ! $hasStubDrift && ! $hasProblemLines && $styleIssues === 0) {
            info('All controllers up to date.');

            return self::SUCCESS;
        }

        $this->printProblemLines();
        $this->printIssueSummary($issues, $missingDeps, $excludedFromStub, $policyDrift);

        // Only user-owned divergences are present — nothing for --fix to do.
        // Report visibility but keep the exit code green (e.g. CI stays happy).
        if (! $hasControllerIssues && ! $hasMissingDeps && ! $hasPolicyDrift && ! $hasStubDrift && $this->styleFixes === []) {
            $this->printStyleIssueSummary($styleIssues);

            return $styleIssues > 0 ? self::FAILURE : self::SUCCESS;
        }

        if ($this->shouldFix(
            $hasControllerIssues,
            $hasMissingDeps,
            $hasPolicyDrift || $hasStubDrift,
            $this->styleFixes !== [],
        )) {
            $resolvedStyleIssues = $this->applyStyleFixes();
            $remainingStyleIssues = max(0, $styleIssues - $resolvedStyleIssues);
            $this->publishIssues($issues);
            $depsAdded = $this->writeMissingDependencies($missingDeps);
            try {
                $regeneratedLoader = $this->regenerateLoaderStub(
                    $excludedFromStub,
                    $registry,
                    $configuration,
                    $policyDrift,
                );
            } catch (RuntimeException $exception) {
                warning($exception->getMessage());

                return self::FAILURE;
            }

            if ($regeneratedLoader) {
                $this->warnAboutViteRebuild();
            }

            if ($depsAdded > 0) {
                if ($this->shouldInstallDependencies()) {
                    $status = $this->installDependencies();
                    $this->printStyleIssueSummary($remainingStyleIssues);

                    return $status === self::SUCCESS && $remainingStyleIssues > 0 ? self::FAILURE : $status;
                }

                $this->line('');
                $this->line('<comment>Run your package manager install command to fetch the new dependencies.</comment>');
            }

            $this->printStyleIssueSummary($remainingStyleIssues);

            return $remainingStyleIssues > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->printStyleIssueSummary($styleIssues);

        return self::FAILURE;
    }

    private function resetState(): void
    {
        $this->problemLines = [];
        $this->okComponentControllerLines = [];
        $this->okNoControllerLines = [];
        $this->okStandaloneLines = [];
        $this->okHelperLines = [];
        $this->okStyleLines = [];
        $this->styleFixes = [];
        $this->controllerResolver = null;
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
    private function detectStubExclusions(array $usedControllers): array
    {
        $stubPath = resource_path('js/controllers/index.js');

        if (! $this->files->exists($stubPath)) {
            return [];
        }

        $included = LoaderStub::includedComDepControllers($this->files->get($stubPath));

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
    private function regenerateLoaderStub(
        array $excludedFromStub,
        HotwireRegistry $registry,
        ControllerLoadConfiguration $configuration,
        bool $policyDrift = false,
    ): bool {
        if ($excludedFromStub === [] && ! $policyDrift) {
            return false;
        }

        $stubPath = resource_path('js/controllers/index.js');

        if (! $this->files->exists($stubPath)) {
            return false;
        }

        $policy = LoaderStub::policyFromContent($this->files->get($stubPath));

        if ($policy === null) {
            return false;
        }

        $existing = $policy->includedComDepControllers;
        $merged = array_values(array_unique(array_merge($existing, $excludedFromStub)));
        sort($merged);
        $this->files->put($stubPath, LoaderStub::generate(
            $registry,
            $policy->includeAllComDepControllers ? null : $merged,
            $configuration->preload,
            $configuration->eager,
        ));

        $message = $excludedFromStub === []
            ? 'Regenerated resources/js/controllers/index.js from controller loading config.'
            : 'Regenerated resources/js/controllers/index.js to include: '.implode(', ', $excludedFromStub);
        info($message);

        return true;
    }

    private function warnAboutViteRebuild(): void
    {
        $this->line('');
        $this->line('<comment>Rebuild your Vite assets so the production manifest includes the regenerated controller loading policy.</comment>');

        $command = $this->buildScriptCommand();

        if ($command !== null) {
            $this->line("<comment>Run `$command` after this command completes.</comment>");
        }
    }

    private function buildScriptCommand(): ?string
    {
        $path = base_path('package.json');

        if (! $this->files->exists($path)) {
            return null;
        }

        $package = json_decode($this->files->get($path), true);

        if (! is_array($package) || ! array_key_exists('build', $package['scripts'] ?? [])) {
            return null;
        }

        return match ($this->packageInstaller->detect($this->files)) {
            'bun' => 'bun run build',
            'pnpm' => 'pnpm run build',
            'yarn' => 'yarn build',
            default => 'npm run build',
        };
    }

    private function detectControllerPolicyDrift(
        HotwireRegistry $registry,
        ControllerLoadConfiguration $configuration,
    ): bool {
        $path = resource_path('js/controllers/index.js');

        if (! $this->files->exists($path)) {
            return false;
        }

        $content = $this->files->get($path);
        $policy = LoaderStub::policyFromContent($content);

        if ($policy === null) {
            if (LoaderStub::isAutoGenerated($content)) {
                throw new RuntimeException('resources/js/controllers/index.js has unsupported or missing loader metadata. Re-run `php artisan hotwire:install --only=js --skip-install` with your original selection (--core-only or --with-deps when selective; omit both to include all). Then install dependencies and rebuild Vite assets. hotwire:check --fix cannot infer the old selection.');
            }

            return false;
        }

        $expected = ControllerLoadPlan::make(
            $this->files,
            $registry,
            resource_path('js/controllers'),
            $policy->includeAllComDepControllers ? null : $policy->includedComDepControllers,
            $configuration->preload,
            $configuration->eager,
        )->policy;

        if ($policy->preloadControllers === $expected->preloadControllers
            && $policy->eagerControllers === $expected->eagerControllers
            && $policy->eagerControllerPaths === $expected->eagerControllerPaths
        ) {
            return false;
        }

        $changes = [];

        if ($policy->preloadControllers !== $expected->preloadControllers) {
            $changes[] = 'preload: '.$this->identifierList($policy->preloadControllers).' -> '.$this->identifierList($expected->preloadControllers);
        }

        if ($policy->eagerControllers !== $expected->eagerControllers) {
            $changes[] = 'eager: '.$this->identifierList($policy->eagerControllers).' -> '.$this->identifierList($expected->eagerControllers);
        }

        if ($policy->eagerControllerPaths !== $expected->eagerControllerPaths) {
            $changes[] = 'eager paths: '.$this->identifierMap($policy->eagerControllerPaths).' -> '.$this->identifierMap($expected->eagerControllerPaths);
        }

        $detail = implode('; ', $changes);
        $this->problemLines[] = [
            'key' => 'resources/js/controllers/index.js',
            'line' => "  <comment>!</comment>  resources/js/controllers/index.js  outdated  <fg=gray>(controller loading policy differs from config; {$detail})</>",
        ];

        return true;
    }

    /** @param string[] $identifiers */
    private function identifierList(array $identifiers): string
    {
        return '['.implode(', ', $identifiers).']';
    }

    /** @param array<string, string> $values */
    private function identifierMap(array $values): string
    {
        return '['.implode(', ', array_map(
            fn (string $identifier, string $path): string => "{$identifier}={$path}",
            array_keys($values),
            $values,
        )).']';
    }

    /** @return array<string, ControllerDefinition> */
    private function configuredPackageControllers(
        HotwireRegistry $registry,
        ControllerLoadConfiguration $configuration,
    ): array {
        $identifiers = array_values(array_unique(array_merge($configuration->preload, $configuration->eager)));
        $controllers = [];

        foreach ($identifiers as $identifier) {
            $controller = $registry->controller($identifier);

            if ($controller === null) {
                $this->resolver()->resolve($identifier);

                continue;
            }

            $targetFile = $controller->relativeDir() === ''
                ? resource_path('js/controllers/'.$controller->filename())
                : resource_path('js/controllers/'.$controller->relativeDir().'/'.$controller->filename());

            if (! $this->isApplicationOverride($controller, resource_path('js/controllers'), $targetFile)) {
                $controllers[$identifier] = $controller;
            }
        }

        return $controllers;
    }

    private function resolver(): ControllerResolver
    {
        if ($this->controllerResolver === null) {
            throw new LogicException('Controller resolver has not been initialized.');
        }

        return $this->controllerResolver;
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
     * Component detection recognizes the configured prefix and `hw` in both
     * native `<x-prefix::*>` and short `<prefix:*>` forms.
     *
     * @param  string[]  $paths
     * @return array{components: array<string, string>, controllers: array<string, ControllerDefinition>}
     */
    private function scanViews(array $paths, string $prefix, HotwireRegistry $registry, int &$totalFiles): array
    {
        $prefixes = ComponentAliases::prefixes($prefix);
        $alt = implode('|', array_map(fn (string $p) => preg_quote($p, '/'), $prefixes));
        $componentPattern = '/<x-('.$alt.')::([a-z][a-z0-9.-]*)[\s\/>]/';
        $shortComponentPattern = '/<('.$alt.'):([a-z][a-z0-9.-]*)[\s\/>]/';

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

                preg_match_all($componentPattern, $content, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $components[$match[2]] ??= "<x-{$match[1]}::{$match[2]}>";
                }

                preg_match_all($shortComponentPattern, $content, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $components[$match[2]] ??= "<{$match[1]}:{$match[2]}>";
                }

                $this->collectControllerUsages($content, $registry, $controllers);
            }
        }

        return ['components' => $components, 'controllers' => $controllers];
    }

    /**
     * Report visual owners used in views but absent from every generated selective bundle.
     *
     * This deliberately checks global coverage only. An official preset import satisfies that
     * coverage; importing a local preset is the application's assertion that it owns complete
     * coverage. Mapping a view/layout to selective bundles requires an explicit application contract.
     *
     * @param  array<string, string>  $components
     */
    private function reportStyleCoverage(array $components, HotwireRegistry $registry): int
    {
        $directory = resource_path('css');

        if (! is_dir($directory) && $this->option('preset') === []) {
            return 0;
        }

        $plans = [];
        $issues = 0;
        $bundleCoverageUnknowable = false;
        $hasCompletePreset = false;
        $applicationPresets = [];
        $importedApplicationPresets = [];

        foreach ($this->explicitApplicationPresetPaths($directory, $issues) as $preset) {
            $applicationPresets[$this->comparablePath($preset)] = $preset;
        }

        $stylesheets = is_dir($directory)
            ? Finder::create()->files()->name('*.css')->in($directory)
            : [];

        foreach ($stylesheets as $file) {
            $content = $file->getContents();
            $plan = $this->styleBundle->planFromContent($content);
            $stylesheet = $file->getPathname();
            $path = 'resources/css/'.ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');

            if (($plan !== null || $this->styleBundle->looksGenerated($content))
                && ! $this->safeGeneratedStyleTarget($stylesheet)) {
                $this->problemLines[] = [
                    'key' => "styles-path-{$path}",
                    'line' => "  <error>✗</error>  {$path}  generated CSS path must resolve inside resources/css without symlinks",
                ];
                $issues++;
                $bundleCoverageUnknowable = true;

                continue;
            }

            if ($plan !== null) {
                $unknownComponents = array_values(array_filter(
                    $plan['components'],
                    fn (string $component): bool => $registry->component($component) === null,
                ));

                if ($unknownComponents !== []) {
                    $this->problemLines[] = [
                        'key' => "styles-metadata-{$path}",
                        'line' => "  <error>✗</error>  {$path}  generated CSS plan is invalid  <fg=gray>(unknown components: ".implode(', ', $unknownComponents).')</>',
                    ];
                    $issues++;
                    $bundleCoverageUnknowable = true;

                    continue;
                }

                try {
                    $source = $this->presetFiles->sourceForSelection($plan['preset'], $plan['components']);
                    $modules = $this->styleManifest->modulesFor($plan['components']);
                } catch (PresetSourceException $exception) {
                    $this->problemLines[] = [
                        'key' => "styles-content-{$path}",
                        'line' => "  <error>✗</error>  {$path}  recorded preset cannot be rendered  <fg=gray>({$exception->getMessage()})</>",
                    ];
                    $issues++;
                    $bundleCoverageUnknowable = true;

                    continue;
                }

                if ($source === null) {
                    $this->problemLines[] = [
                        'key' => "styles-preset-{$path}",
                        'line' => "  <error>✗</error>  {$path}  recorded preset [{$plan['preset']}] is no longer available  <fg=gray>(recreate the bundle with an available preset)</>",
                    ];
                    $issues++;
                    $bundleCoverageUnknowable = true;

                    continue;
                }

                $expected = $this->styleBundle->renderFromPlan($path, $source, $plan, $modules);
                $command = 'php artisan hotwire:bundle-preset --from='.escapeshellarg($path).' --force';

                if ($plan['version'] === 2 && ! $this->styleBundle->hashMatches($content)) {
                    $this->problemLines[] = [
                        'key' => "styles-content-{$path}",
                        'line' => "  <error>✗</error>  {$path}  generated CSS was edited outside Laravel Hotwire  <fg=gray>({$command})</>\n"
                            .$this->styleDiff($path, $content, $expected),
                    ];
                    $issues++;
                    $bundleCoverageUnknowable = true;

                    continue;
                }

                if (! $this->styleBundle->matches($content, $expected)) {
                    $legacy = $plan['version'] === 1;
                    $detail = $legacy
                        ? "legacy v1 plan cannot be auto-fixed; {$command}"
                        : "package content changed; {$command}";
                    $this->problemLines[] = [
                        'key' => "styles-content-{$path}",
                        'line' => "  <error>✗</error>  {$path}  generated CSS is outdated  <fg=gray>({$detail})</>",
                    ];
                    $issues++;

                    if ($legacy) {
                        $bundleCoverageUnknowable = true;
                    } else {
                        $plans[] = array_fill_keys($modules, true);
                        $this->styleFixes[] = [
                            'target' => $stylesheet,
                            'path' => $path,
                            'observed' => $content,
                            'expected' => $expected,
                        ];
                    }

                    continue;
                }

                $plans[] = array_fill_keys($modules, true);

                continue;
            }

            if ($this->styleBundle->looksGenerated($content)) {
                $this->problemLines[] = [
                    'key' => "styles-metadata-{$path}",
                    'line' => "  <error>✗</error>  {$path}  generated CSS plan is invalid  <fg=gray>(recreate the bundle from its original selection)</>",
                ];
                $issues++;
                $bundleCoverageUnknowable = true;

                continue;
            }

            $imports = $this->completePresetImports($content, $stylesheet);
            $hasCompletePreset = $hasCompletePreset || $imports['official'] !== [];

            if (count($imports['official']) > 1) {
                $this->problemLines[] = [
                    'key' => "styles-presets-{$path}",
                    'line' => "  <error>✗</error>  {$path}  imports multiple official presets: ".implode(', ', $imports['official']).'  <fg=gray>(keep exactly one complete preset import active)</>',
                ];
                $issues++;
            }

            foreach ($imports['application'] as $preset) {
                $key = $this->comparablePath($preset);
                $applicationPresets[$key] = $preset;
                $importedApplicationPresets[$key] = true;
            }
        }

        foreach ($applicationPresets as $key => $preset) {
            $result = $this->applicationPresets->validate($preset, $registry, $directory);
            $path = $this->applicationPath($preset);

            foreach ($result['errors'] as $index => $error) {
                $this->problemLines[] = [
                    'key' => "preset-error-{$path}-{$index}",
                    'line' => "  <error>✗</error>  {$path}  {$this->presetIssueDetail($error)}",
                ];
            }

            foreach ($result['warnings'] as $index => $warning) {
                $this->problemLines[] = [
                    'key' => "preset-warning-{$path}-{$index}",
                    'line' => "  <comment>!</comment>  {$path}  warning: {$this->presetIssueDetail($warning)}",
                ];
            }

            if ($result['errors'] === []) {
                $hasCompletePreset = $hasCompletePreset || isset($importedApplicationPresets[$key]);
                $status = $result['warnings'] === [] ? 'valid application preset' : 'application preset checked with warnings';
                $this->okStyleLines[] = "  <info>✓</info>  {$path}  {$status}";
            }

            $issues += count($result['errors']);
        }

        // Coverage is unknowable while a discovered generated bundle has no readable plan.
        if ($bundleCoverageUnknowable || $hasCompletePreset || $plans === []) {
            return $issues;
        }

        foreach ($components as $key => $tag) {
            $component = $registry->component($key);

            if ($component === null) {
                continue;
            }

            $required = $this->styleManifest->modulesFor([$key]);

            if ($required !== [] && ! $this->modulesCovered($required, $plans)) {
                $this->problemLines[] = [
                    'key' => "styles-component-{$key}",
                    'line' => "  <error>✗</error>  {$tag}  not covered by any generated CSS bundle  <fg=gray>(add `{$key}` to the appropriate `hotwire:bundle-preset` selection and regenerate with --force)</>",
                ];
                $issues++;
            }
        }

        return $issues;
    }

    /** @return array{official: string[], application: string[]} */
    private function completePresetImports(string $content, string $stylesheet): array
    {
        $presetDirectory = realpath(resource_path('css/presets'));

        if ($presetDirectory !== false && $this->containsPath($presetDirectory, realpath($stylesheet) ?: $stylesheet)) {
            return ['official' => [], 'application' => []];
        }

        $official = [];
        $application = [];

        foreach ($this->cssImports->parse($content) as $rule) {
            if (! $this->isUnconditionalImport($rule['conditions'])) {
                continue;
            }

            $import = preg_replace('/[?#].*$/', '', str_replace('\\', '/', $rule['path'])) ?? $rule['path'];

            if (str_starts_with($import, '/') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $import) === 1) {
                continue;
            }

            $resolved = realpath(dirname($stylesheet).'/'.$import);

            if ($resolved === false) {
                continue;
            }

            if ($presetDirectory !== false && is_file($resolved) && $this->samePath($presetDirectory, dirname($resolved))) {
                if (! $this->styleBundle->looksGenerated($this->files->get($resolved))) {
                    $application[] = $resolved;
                }

                continue;
            }

            foreach ($this->presetFiles->names() as $preset) {
                if ($this->matchesShippedPreset($resolved, $preset)) {
                    $official[] = $preset;

                    continue 2;
                }
            }
        }

        return ['official' => $official, 'application' => array_values(array_unique($application))];
    }

    /** @return string[] */
    private function explicitApplicationPresetPaths(string $cssRoot, int &$issues): array
    {
        $paths = [];

        foreach ((array) $this->option('preset') as $preset) {
            $preset = (string) $preset;
            $isName = preg_match('/^(?<name>[a-z][a-z0-9-]*)(?:\.css)?$/', $preset, $matches) === 1;
            $candidate = $isName
                ? resource_path("css/presets/{$matches['name']}.css")
                : ($this->isAbsolutePath($preset) ? $preset : base_path($preset));
            $resolved = realpath($candidate);
            $root = realpath($cssRoot);

            if ($resolved === false || $root === false || pathinfo($resolved, PATHINFO_EXTENSION) !== 'css'
                || ! $this->containsPath($root, $resolved)) {
                $this->problemLines[] = [
                    'key' => "preset-option-{$preset}",
                    'line' => '  <error>✗</error>  application preset  Application preset must be a CSS file under resources/css.',
                ];
                $issues++;

                continue;
            }

            if ($this->styleBundle->looksGenerated($this->files->get($resolved))) {
                $path = $this->applicationPath($resolved);
                $line = "  <comment>-</comment>  {$path}  complete-preset validation skipped: generated selective bundle";

                if (! in_array($line, $this->okStyleLines, true)) {
                    $this->okStyleLines[] = $line;
                }

                continue;
            }

            $paths[] = $resolved;
        }

        return array_values(array_unique($paths));
    }

    private function isAbsolutePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1;
    }

    private function comparablePath(string $path): string
    {
        $path = $this->resolvedPath($path);

        return $this->pathForComparison($path);
    }

    private function applicationPath(string $path): string
    {
        $base = rtrim($this->resolvedPath(base_path()), '/');
        $path = $this->resolvedPath($path);
        $prefix = $base.'/';

        return str_starts_with($this->pathForComparison($path), $this->pathForComparison($prefix))
            ? substr($path, strlen($prefix))
            : basename($path);
    }

    private function resolvedPath(string $path): string
    {
        return str_replace('\\', '/', realpath($path) ?: $path);
    }

    private function pathForComparison(string $path): string
    {
        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    }

    private function presetIssueDetail(string $message): string
    {
        return (string) preg_replace('/^Preset \[[^]]+\] (?:is )?/', '', $message);
    }

    private function matchesShippedPreset(string $resolved, string $preset): bool
    {
        $official = $this->presetFiles->path($preset);

        if (! is_file($resolved) || $official === null) {
            return false;
        }

        if ($this->samePath(realpath($official) ?: $official, $resolved)) {
            return true;
        }

        try {
            $expected = $this->presetFiles->source($preset);
            $actual = (new PresetSourceResolver($this->files, dirname($resolved, 2)))->resolve($resolved);
        } catch (PresetSourceException) {
            return false;
        }

        if ($expected === null
            || $actual->foundationImports() !== $expected->foundationImports()
            || $actual->visualCss() !== $expected->visualCss()) {
            return false;
        }

        foreach ($actual->foundationImports() as $foundation) {
            $actualPath = dirname($resolved, 2).'/'.$foundation;
            $expectedPath = dirname($official, 2).'/'.$foundation;

            if (! $this->stylesheetTreeMatches($actualPath, $expectedPath)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, true> $visited */
    private function stylesheetTreeMatches(string $actual, string $expected, array &$visited = []): bool
    {
        $actual = $this->resolvedPath($actual);
        $expected = $this->resolvedPath($expected);
        $key = $actual."\0".$expected;

        if (isset($visited[$key])) {
            return true;
        }

        $visited[$key] = true;

        if (! is_file($actual) || ! is_file($expected)
            || hash_file('sha256', $actual) !== hash_file('sha256', $expected)) {
            return false;
        }

        foreach ($this->cssImports->parse($this->files->get($expected)) as $import) {
            $path = preg_replace('/[?#].*$/', '', $import['path']) ?? $import['path'];

            if (! str_starts_with($path, '.')) {
                continue;
            }

            if (! $this->stylesheetTreeMatches(dirname($actual).'/'.$path, dirname($expected).'/'.$path, $visited)) {
                return false;
            }
        }

        return true;
    }

    private function isUnconditionalImport(string $conditions): bool
    {
        return $conditions === '';
    }

    private function containsPath(string $parent, string $path): bool
    {
        $parent = str_replace('\\', '/', $parent);
        $path = str_replace('\\', '/', $path);

        if (PHP_OS_FAMILY === 'Windows') {
            $parent = strtolower($parent);
            $path = strtolower($path);
        }

        return $path === $parent || str_starts_with($path, rtrim($parent, '/').'/');
    }

    private function samePath(string $left, string $right): bool
    {
        $left = str_replace('\\', '/', $left);
        $right = str_replace('\\', '/', $right);

        return PHP_OS_FAMILY === 'Windows'
            ? strtolower($left) === strtolower($right)
            : $left === $right;
    }

    /**
     * @param  string[]  $required
     * @param  array<int, array<string, true>>  $plans
     */
    private function modulesCovered(array $required, array $plans): bool
    {
        foreach ($plans as $modules) {
            if (array_diff($required, array_keys($modules)) === []) {
                return true;
            }
        }

        return false;
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
     * @param  array<string, string>  $usedKeys
     * @return array{issues: array<int, array{identifier: string, source_file: string, target_file: string}>, controllers: array<string, ControllerDefinition>, reported: array<string, true>}
     *
     * @throws FileNotFoundException
     */
    private function reportStatus(array $usedKeys, string $targetBase, HotwireRegistry $registry): array
    {
        $issues = [];
        $controllers = [];
        $reported = [];
        $seenDeps = [];
        $controllersBase = $registry->basePath().'/resources/js/controllers';

        ksort($usedKeys);

        foreach ($usedKeys as $key => $tag) {
            $component = $registry->component($key);

            if ($component === null) {
                continue;
            }

            if ($component->controllers === []) {
                $this->okNoControllerLines[] = "  <info>✓</info>  $tag  No controllers required";

                continue;
            }

            foreach ($registry->controllersForComponent($component) as $controller) {
                $reported[$controller->identifier] = true;
                $this->checkController($controller, $targetBase, $controllersBase, $registry->basePath(), $tag, $issues, $controllers, $seenDeps);
            }
        }

        return ['issues' => $issues, 'controllers' => $controllers, 'reported' => $reported];
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

        $localOverride = $this->isApplicationOverride(
            $controller,
            $targetBase,
            $targetFile,
        );

        if (! $localOverride) {
            $controllers[$controller->identifier] = $controller;
        }
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

        if (! $localOverride) {
            $this->reportSharedDeps($controller, $sourceFile, $controllersBase, $targetBase, $issues, $seenDeps);
        }
    }

    private function isApplicationOverride(
        ControllerDefinition $controller,
        string $targetBase,
        string $packageTargetFile,
    ): bool {
        $resolved = $this->resolver()->resolve($controller->identifier);

        if ($resolved->origin !== ControllerOrigin::Application) {
            return false;
        }

        $applicationFile = $targetBase.'/'.substr($resolved->loaderPath, 2);

        return $applicationFile !== $packageTargetFile;
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

    private function validateLazyLoaderVersion(): void
    {
        $stubPath = resource_path('js/controllers/index.js');

        if (! $this->files->exists($stubPath)
            || ! LoaderStub::isAutoGenerated($this->files->get($stubPath))
        ) {
            return;
        }

        $this->packageInstaller->validateDependency(
            $this->files,
            self::LAZY_LOADER_PACKAGE,
            self::LAZY_LOADER_VERSION,
        );
    }

    private function emitScanOutput(): void
    {
        foreach ($this->okStyleLines as $line) {
            $this->line($line);
        }

        if ($this->okStyleLines !== [] && ($this->okComponentControllerLines !== [] || $this->okNoControllerLines !== [])) {
            $this->line('');
        }

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

        $count = count($this->problemLines);
        $word = $count === 1 ? 'issue' : 'issues';

        $this->line("<options=bold>Needs attention ($count $word):</>");
        foreach ($this->problemLines as $entry) {
            $this->line($entry['line']);
        }
        $this->line('');
    }

    private function printStyleIssueSummary(int $issues): void
    {
        if ($issues === 0) {
            return;
        }

        $this->line("<comment>{$issues} CSS validation issue(s) require manual changes or regeneration.</comment>");
        $this->line('');
    }

    private function applyStyleFixes(): int
    {
        $fixed = 0;

        foreach ($this->styleFixes as $fix) {
            $lock = @fopen(sys_get_temp_dir().'/laravel-hotwire-style-'.hash('sha256', $fix['target']).'.lock', 'c');

            if ($lock === false || ! flock($lock, LOCK_EX)) {
                if (is_resource($lock)) {
                    fclose($lock);
                }

                warning("Skipped {$fix['path']} because it could not be locked.");

                continue;
            }

            try {
                if (! $this->styleTargetMatches($fix['target'], $fix['observed'])) {
                    warning("Skipped {$fix['path']} because it changed while hotwire:check was running.");

                    continue;
                }

                if (! $this->replaceStyleAtomically($fix)) {
                    warning("Failed to regenerate {$fix['path']}.");

                    continue;
                }

                info("Regenerated: {$fix['path']}");
                $fixed++;
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }

        return $fixed;
    }

    /** @param resource $stream */
    private function writeStream($stream, string $content): bool
    {
        $offset = 0;
        $length = strlen($content);

        while ($offset < $length) {
            $written = fwrite($stream, substr($content, $offset));

            if ($written === false || $written === 0) {
                return false;
            }

            $offset += $written;
        }

        return true;
    }

    /** @param array{target: string, path: string, observed: string, expected: string} $fix */
    private function replaceStyleAtomically(array $fix): bool
    {
        $temporary = @tempnam(dirname($fix['target']), basename($fix['target']));

        if ($temporary === false) {
            return false;
        }

        $stream = @fopen($temporary, 'wb');

        try {
            if ($stream === false
                || ! $this->writeStream($stream, $fix['expected'])
                || ! fflush($stream)
                || function_exists('fsync') && ! fsync($stream)) {
                return false;
            }

            fclose($stream);
            $stream = null;

            $permissions = @fileperms($fix['target']);

            if ($permissions !== false) {
                @chmod($temporary, $permissions & 0777);
            }

            clearstatcache(true, $fix['target']);

            if (! $this->styleTargetMatches($fix['target'], $fix['observed'])) {
                return false;
            }

            return @rename($temporary, $fix['target']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (file_exists($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function styleTargetMatches(string $target, string $observed): bool
    {
        if (! $this->safeGeneratedStyleTarget($target)) {
            return false;
        }

        try {
            return $this->styleBundle->matches($this->files->get($target), $observed);
        } catch (FileNotFoundException) {
            return false;
        }
    }

    private function safeGeneratedStyleTarget(string $target): bool
    {
        $application = realpath(base_path());
        $styles = resource_path('css');
        $root = realpath($styles);
        $resolved = realpath($target);

        return $application !== false
            && $root !== false
            && $resolved !== false
            && ! is_link($styles)
            && ! $this->traversesSymlink($application, $styles)
            && $this->containsPath($application, $root)
            && is_file($target)
            && ! $this->traversesSymlink($root, $target)
            && $this->containsPath($root, $resolved);
    }

    private function traversesSymlink(string $root, string $target): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $target = str_replace('\\', '/', $target);
        $current = $root;

        foreach (explode('/', substr($target, strlen($root) + 1)) as $segment) {
            $current .= '/'.$segment;

            if (is_link($current)) {
                return true;
            }
        }

        return false;
    }

    private function styleDiff(string $path, string $current, string $expected): string
    {
        $currentLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $current));
        $expectedLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $expected));
        $prefix = 0;
        $limit = min(count($currentLines), count($expectedLines));

        while ($prefix < $limit && $currentLines[$prefix] === $expectedLines[$prefix]) {
            $prefix++;
        }

        $suffix = 0;

        while ($suffix < count($currentLines) - $prefix
            && $suffix < count($expectedLines) - $prefix
            && $currentLines[count($currentLines) - $suffix - 1] === $expectedLines[count($expectedLines) - $suffix - 1]) {
            $suffix++;
        }

        $context = 2;
        $start = max(0, $prefix - $context);
        $currentEnd = min(count($currentLines), count($currentLines) - $suffix + $context);
        $expectedEnd = min(count($expectedLines), count($expectedLines) - $suffix + $context);
        $beforeEnd = $prefix;
        $afterStartCurrent = count($currentLines) - $suffix;
        $afterStartExpected = count($expectedLines) - $suffix;
        $lines = [
            "--- {$path} (current)",
            "+++ {$path} (expected)",
            '@@ -'.($start + 1).','.($currentEnd - $start).' +'.($start + 1).','.($expectedEnd - $start).' @@',
        ];

        foreach (array_slice($currentLines, $start, $beforeEnd - $start) as $line) {
            $lines[] = ' '.OutputFormatter::escape($line);
        }

        foreach (array_slice($currentLines, $prefix, $afterStartCurrent - $prefix) as $line) {
            $lines[] = '-'.OutputFormatter::escape($line);
        }

        foreach (array_slice($expectedLines, $prefix, $afterStartExpected - $prefix) as $line) {
            $lines[] = '+'.OutputFormatter::escape($line);
        }

        foreach (array_slice($currentLines, $afterStartCurrent, $currentEnd - $afterStartCurrent) as $line) {
            $lines[] = ' '.OutputFormatter::escape($line);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array{identifier: string, source_file: string, target_file: string}>  $issues
     * @param  array<string, string>  $missingDeps
     * @param  string[]  $excludedFromStub
     */
    private function printIssueSummary(
        array $issues,
        array $missingDeps,
        array $excludedFromStub = [],
        bool $policyDrift = false,
    ): void {
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
            $this->line("<comment>$count controller(s) used in views but excluded from controllers/index.js</comment>");
        }

        if ($policyDrift || ! empty($excludedFromStub)) {
            $this->line('<comment>1 loader stub needs regeneration.</comment>');
            $this->line('<comment>hotwire:check --fix will regenerate resources/js/controllers/index.js.</comment>');
        }

        $this->line('');
    }

    private function shouldFix(
        bool $controllerIssues,
        bool $missingDependencies,
        bool $regenerateLoader,
        bool $regenerateStyles,
    ): bool {
        if ($this->option('fix')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        $hasInteractiveTerminal = defined('STDIN')
            && function_exists('stream_isatty')
            && stream_isatty(STDIN);

        if (! $hasInteractiveTerminal && ! app()->runningUnitTests()) {
            return false;
        }

        $actions = [];

        if ($controllerIssues) {
            $actions[] = 'publish missing/outdated controller files';
        }

        if ($regenerateLoader) {
            $actions[] = 'regenerate resources/js/controllers/index.js';
        }

        if ($missingDependencies) {
            $actions[] = 'add missing npm dependencies';
        }

        if ($regenerateStyles) {
            $actions[] = 'regenerate untouched selective CSS bundles';
        }

        return confirm('Apply --fix now? This will '.$this->sentenceList($actions).'.', default: false);
    }

    /** @param string[] $items */
    private function sentenceList(array $items): string
    {
        if (count($items) < 2) {
            return $items[0] ?? 'apply the available fixes';
        }

        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
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
        if ($this->option('skip-install')) {
            return false;
        }

        if (! $this->input->isInteractive()) {
            return true;
        }

        $manager = $this->packageInstaller->detect($this->files);

        return confirm("Run $manager install now?", default: true);
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
