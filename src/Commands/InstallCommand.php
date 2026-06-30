<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\LoaderStub;
use Emaia\LaravelHotwire\Support\PackageInstaller;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    public $signature = 'hotwire:install
                        {--force : Overwrite existing files}
                        {--only= : Install only "js" or "css"}
                        {--with-deps=* : Add npm deps only for these controllers (comma-separated or repeatable). Without this flag (and without --core-only), every catalog dep is added.}
                        {--core-only : Add only core npm deps (stimulus, turbo, dynamic-loader). Skip catalog deps entirely.}
                        {--skip-install : Do not run the package manager (bun/npm/pnpm/yarn) install after writing package.json. Leaves dep fetching to the caller.}
                        {--fix : Auto-apply hotwire:check --fix during the post-install verification (non-interactive friendly)}';

    public $description = 'Install Hotwire scaffolding into your Laravel application';

    private const string VITE_ALIAS_KEY = '@hotwire';

    private const string VITE_ALIAS_PATH = 'vendor/emaia/laravel-hotwire/resources/js';

    private const array CORE_DEPENDENCIES = [
        '@emaia/stimulus-dynamic-loader',
        '@hotwired/stimulus',
        '@hotwired/turbo',
    ];

    public function __construct(
        private readonly Filesystem $files,
        private readonly PackageInstaller $packageInstaller,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $filter = $this->option('only');

        if ($filter !== null && ! in_array($filter, ['js', 'css'])) {
            warning("Invalid --only value: \"$filter\". Use 'js' or 'css'.");

            return self::FAILURE;
        }

        if (! $this->validateDepFlags()) {
            return self::FAILURE;
        }

        $stubBase = realpath(__DIR__.'/../../stubs/resources');
        $targetBase = resource_path();

        $stubFiles = $this->stubFiles($stubBase, $filter);
        $copied = $this->copyStubs($stubFiles, $stubBase, $targetBase);

        $depsAdded = 0;
        $aliasResult = null;
        if ($filter !== 'css') {
            $depsAdded = $this->addNpmDependencies();
            $aliasResult = $this->packageInstaller->addViteAlias(
                $this->files,
                self::VITE_ALIAS_KEY,
                self::VITE_ALIAS_PATH,
            );
        }

        $this->showSummary($copied, $depsAdded, $aliasResult);

        if ($depsAdded > 0 && $this->shouldInstallDependencies()) {
            $exit = $this->installDependencies();
            $this->runPostInstallCheck();

            return $exit;
        }

        $this->runPostInstallCheck();

        return self::SUCCESS;
    }

    /**
     * Run hotwire:check after install to surface any drift between the
     * generated loader stub and the controllers actually referenced in views.
     * Skips silently when the user opted into the default mode (no exclusions
     * to worry about) since drift detection only matters for --core-only or
     * --with-deps installs.
     *
     * Inherits interactivity from this command: when install was run in a TTY,
     * check stays interactive too so its shouldFix() prompt fires directly —
     * the user doesn't have to re-run `hotwire:check --fix` manually.
     */
    private function runPostInstallCheck(): void
    {
        if (! $this->option('core-only') && $this->controllerFilter() === null) {
            return;
        }

        $this->newLine();
        $this->line('Verifying view usage matches install config...');

        $args = $this->input->isInteractive() ? [] : ['--no-interaction' => true];

        if ($this->option('fix')) {
            $args['--fix'] = true;
        }

        if ($this->option('skip-install')) {
            $args['--skip-install'] = true;
        }

        $this->call('hotwire:check', $args);
    }

    private function validateDepFlags(): bool
    {
        $withDeps = $this->controllerFilter();

        if ($this->option('core-only') && $withDeps !== null) {
            warning('Cannot combine --core-only with --with-deps. Use one or the other.');

            return false;
        }

        if ($withDeps === null) {
            return true;
        }

        $registry = HotwireRegistry::make();

        foreach ($withDeps as $identifier) {
            if ($registry->controller($identifier) !== null) {
                continue;
            }

            warning("Unknown controller \"$identifier\". Run `php artisan hotwire:controllers --list` for available controllers.");

            return false;
        }

        return true;
    }

    /**
     * Parse --with-deps into a flat list of controller identifiers. Accepts both
     * --with-deps=foo,bar and --with-deps=foo --with-deps=bar shapes. Returns null
     * when the flag is absent (caller treats null as "install everything").
     *
     * @return string[]|null
     */
    private function controllerFilter(): ?array
    {
        $raw = $this->option('with-deps');

        if ($raw === []) {
            return null;
        }

        $ids = [];

        foreach ((array) $raw as $entry) {
            foreach (explode(',', (string) $entry) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $ids[] = $piece;
                }
            }
        }

        return $ids === [] ? null : array_values(array_unique($ids));
    }

    /** @return string[] */
    private function stubFiles(string $stubBase, ?string $filter): array
    {
        $finder = Finder::create()->files()->in($stubBase);

        $files = [];

        foreach ($finder as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());

            if ($filter !== null && ! str_starts_with($relativePath, $filter.'/')) {
                continue;
            }

            $files[] = $relativePath;
        }

        sort($files);

        return $files;
    }

    private const string LOADER_STUB_RELATIVE = 'js/controllers/index.js';

    /** @param string[] $files */
    private function copyStubs(array $files, string $stubBase, string $targetBase): int
    {
        $copied = 0;
        $loaderStubContent = $this->loaderStubContent();

        foreach ($files as $relativePath) {
            $sourceFile = $stubBase.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetFile = $targetBase.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            $isLoaderStub = $relativePath === self::LOADER_STUB_RELATIVE;
            $generatedContent = $isLoaderStub ? $loaderStubContent : null;

            if ($this->files->exists($targetFile)) {
                $matches = $isLoaderStub
                    ? $this->files->get($targetFile) === $generatedContent
                    : $this->files->hash($sourceFile) === $this->files->hash($targetFile);

                if ($matches) {
                    continue;
                }

                if (! $this->option('force')) {
                    if ($isLoaderStub && LoaderStub::isAutoGenerated($this->files->get($targetFile))) {
                        // Previously auto-generated file: safe to silently regenerate
                        // even without --force, since the marker tells us the user
                        // didn't hand-edit it.
                    } elseif (! $this->input->isInteractive()) {
                        warning("File \"$relativePath\" already exists. Use --force to overwrite.");

                        continue;
                    } elseif (! confirm("File \"$relativePath\" already exists and differs. Overwrite?")) {
                        continue;
                    }
                }
            }

            $this->files->ensureDirectoryExists(dirname($targetFile));

            if ($isLoaderStub) {
                $this->files->put($targetFile, $generatedContent);
            } else {
                $this->files->copy($sourceFile, $targetFile);
            }

            info("Published: $relativePath");
            $copied++;
        }

        return $copied;
    }

    /**
     * Build the controllers/index.js content tailored to the install flags.
     * Default mode (no filter, no core-only) yields a stub that globs every
     * package controller — identical in effect to a copy of the bundled stub
     * plus the auto-generated marker line.
     */
    private function loaderStubContent(): string
    {
        $registry = HotwireRegistry::make();

        if ($this->option('core-only')) {
            return LoaderStub::generate($registry, []);
        }

        $filter = $this->controllerFilter();

        if ($filter === null) {
            return LoaderStub::generate($registry);
        }

        // The opt-in list at the registration level should also include the
        // zero-dep controllers' identifiers — but LoaderStub already preserves
        // every zero-dep controller (only com-dep ones are exclusion-eligible).
        return LoaderStub::generate($registry, $filter);
    }

    /** @return array<string, string> */
    private function coreDependencies(): array
    {
        $path = realpath(__DIR__.'/../../package.json');

        if (! $path) {
            warning('Could not read package.json from the laravel-hotwire package — core dependencies (stimulus, turbo, dynamic-loader) were not added.');

            return [];
        }

        $json = json_decode(file_get_contents($path), true);
        $all = $json['dependencies'] ?? [];

        return array_intersect_key($all, array_flip(self::CORE_DEPENDENCIES));
    }

    /** @return array<string, string> */
    private function catalogDependencies(): array
    {
        $registry = HotwireRegistry::make();
        $filter = $this->controllerFilter();

        $deps = [];

        foreach ($registry->controllers() as $identifier => $controller) {
            if ($filter !== null && ! in_array($identifier, $filter, true)) {
                continue;
            }

            if (empty($controller->npm)) {
                continue;
            }

            foreach ($controller->npm as $package => $version) {
                $deps[$package] = $version;
            }
        }

        ksort($deps);

        return $deps;
    }

    private function addNpmDependencies(): int
    {
        $packageJsonPath = base_path('package.json');

        if (! $this->files->exists($packageJsonPath)) {
            warning('package.json not found. Skipping npm dependency installation.');

            return 0;
        }

        $deps = $this->coreDependencies();

        if (! $this->option('core-only')) {
            $deps = array_merge($deps, $this->catalogDependencies());
        }

        return count($this->packageInstaller->addDevDependencies($this->files, $deps, updateExisting: false));
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

    private function showSummary(int $copied, int $depsAdded, ?string $aliasResult): void
    {
        $pm = $this->packageInstaller->detect($this->files);

        $this->newLine();
        info('Hotwire installed successfully!');

        if ($copied > 0) {
            $this->line("  Files copied: $copied");
        }

        if ($depsAdded > 0) {
            $this->line("  Dependencies added: $depsAdded");
        }

        $this->reportAliasResult($aliasResult);

        $this->newLine();
        $this->line('Next steps:');

        if ($this->option('skip-install')) {
            $this->line("  1. Run `$pm install` to install dependencies");
            $this->line("  2. Run `$pm run dev` to compile assets");
        } else {
            $this->line("  1. Run `$pm run dev` to compile assets");
        }

        $this->newLine();
        $this->line('Discover what ships with Hotwire:');
        $this->line('  • `php artisan hotwire:components`         list Blade components and their controllers');
        $this->line('  • `php artisan hotwire:controllers --list` list every available Stimulus controller');
        $this->newLine();
        $this->line('Day-to-day commands:');
        $this->line('  • `php artisan hotwire:check`              verify npm packages required by your views (use --fix to add missing ones)');
        $this->line('  • `php artisan hotwire:controllers <name>` fork a controller into your app to customise it');
        $this->newLine();
        $this->line('Controllers auto-load from the vendor directory — no publish step is required unless you want to customise.');
    }

    private function reportAliasResult(?string $aliasResult): void
    {
        if ($aliasResult === null) {
            return;
        }

        $key = self::VITE_ALIAS_KEY;

        match ($aliasResult) {
            PackageInstaller::VITE_ALIAS_ADDED => $this->line("  Vite alias $key added to vite.config.js"),
            PackageInstaller::VITE_ALIAS_ALREADY_PRESENT => $this->line("  Vite alias $key already configured"),
            PackageInstaller::VITE_ALIAS_NO_CONFIG => null,
            PackageInstaller::VITE_ALIAS_PATTERN_MISMATCH => $this->printAliasSnippet(),
            default => null,
        };
    }

    private function printAliasSnippet(): void
    {
        $key = self::VITE_ALIAS_KEY;
        $path = self::VITE_ALIAS_PATH;

        $this->newLine();
        warning("Could not auto-add the $key Vite alias to your config (custom shape detected). Paste this manually inside your defineConfig({...}):");
        $this->line('');
        $this->line("    import { fileURLToPath } from 'node:url';");
        $this->line('');
        $this->line('    resolve: {');
        $this->line('        alias: {');
        $this->line("            '$key': fileURLToPath(new URL('$path', import.meta.url)),");
        $this->line('        },');
        $this->line('    },');
    }
}
