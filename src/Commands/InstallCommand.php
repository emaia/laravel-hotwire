<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
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
                        {--with-deps : Include all controller npm dependencies in devDependencies}
                        {--with-dep=* : Include npm dependencies for a specific controller (repeatable)}
                        {--install : Run package manager install after adding dependencies}';

    public $description = 'Install Hotwire scaffolding into your Laravel application';

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

        if (! $this->validateWithDep()) {
            return self::FAILURE;
        }

        $stubBase = realpath(__DIR__.'/../../stubs/resources');
        $targetBase = resource_path();

        $stubFiles = $this->stubFiles($stubBase, $filter);
        $copied = $this->copyStubs($stubFiles, $stubBase, $targetBase);

        $depsAdded = 0;
        if ($filter !== 'css') {
            $depsAdded = $this->addNpmDependencies();
        }

        $this->showSummary($copied, $depsAdded);

        if ($depsAdded > 0 && $this->shouldInstallDependencies()) {
            return $this->installDependencies();
        }

        return self::SUCCESS;
    }

    private function validateWithDep(): bool
    {
        $withDep = $this->option('with-dep');

        if (empty($withDep)) {
            return true;
        }

        $registry = HotwireRegistry::make();

        foreach ($withDep as $identifier) {
            if ($registry->controller($identifier) !== null) {
                continue;
            }

            warning("Unknown controller \"$identifier\". Run `php artisan hotwire:controllers --list` for available controllers.");

            return false;
        }

        return true;
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

    /** @param string[] $files */
    private function copyStubs(array $files, string $stubBase, string $targetBase): int
    {
        $copied = 0;

        foreach ($files as $relativePath) {
            $sourceFile = $stubBase.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetFile = $targetBase.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ($this->files->exists($targetFile)) {
                if ($this->files->hash($sourceFile) === $this->files->hash($targetFile)) {
                    continue;
                }

                if (! $this->option('force')) {
                    if (! $this->input->isInteractive()) {
                        warning("File \"$relativePath\" already exists. Use --force to overwrite.");

                        continue;
                    }

                    if (! confirm("File \"$relativePath\" already exists and differs. Overwrite?")) {
                        continue;
                    }
                }
            }

            $this->files->ensureDirectoryExists(dirname($targetFile));
            $this->files->copy($sourceFile, $targetFile);

            info("Published: $relativePath");
            $copied++;
        }

        return $copied;
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
        $withDep = $this->option('with-dep');
        $includeAll = (bool) $this->option('with-deps');

        $deps = [];

        foreach ($registry->controllers() as $identifier => $controller) {
            if (! $includeAll && ! empty($withDep) && ! in_array($identifier, $withDep, true)) {
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

        if ($this->option('with-deps') || ! empty($this->option('with-dep'))) {
            $deps = array_merge($deps, $this->catalogDependencies());
        }

        return count($this->packageInstaller->addDevDependencies($this->files, $deps, updateExisting: false));
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

    private function showSummary(int $copied, int $depsAdded): void
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

        $this->newLine();
        $this->line('Next steps:');

        if ($this->option('install')) {
            $this->line("  1. Run `$pm run dev` to compile assets");
        } else {
            $this->line("  1. Run `$pm install` to install dependencies");
            $this->line("  2. Run `$pm run dev` to compile assets");
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
}