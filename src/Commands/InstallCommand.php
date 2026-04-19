<?php

namespace Emaia\LaravelHotwire\Commands;

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
                        {--only= : Install only "js" or "css"}';

    public $description = 'Install Hotwire scaffolding into your Laravel application';

    private const array CORE_DEPENDENCIES = [
        '@emaia/stimulus-dynamic-loader',
        '@hotwired/stimulus',
        '@hotwired/turbo',
    ];

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $filter = $this->option('only');

        if ($filter !== null && ! in_array($filter, ['js', 'css'])) {
            warning("Invalid --only value: \"{$filter}\". Use 'js' or 'css'.");

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

        return self::SUCCESS;
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
                        warning("File \"{$relativePath}\" already exists. Use --force to overwrite.");

                        continue;
                    }

                    if (! confirm("File \"{$relativePath}\" already exists and differs. Overwrite?")) {
                        continue;
                    }
                }
            }

            $this->files->ensureDirectoryExists(dirname($targetFile));
            $this->files->copy($sourceFile, $targetFile);

            info("Published: {$relativePath}");
            $copied++;
        }

        return $copied;
    }

    /** @return array<string, string> */
    private function coreDependencies(): array
    {
        $path = realpath(__DIR__.'/../../package.json');

        if (! $path) {
            return [];
        }

        $json = json_decode(file_get_contents($path), true);
        $all = $json['dependencies'] ?? [];

        return array_intersect_key($all, array_flip(self::CORE_DEPENDENCIES));
    }

    private function addNpmDependencies(): int
    {
        $packageJsonPath = base_path('package.json');

        if (! $this->files->exists($packageJsonPath)) {
            warning('package.json not found. Skipping npm dependency installation.');

            return 0;
        }

        $dependencies = $this->coreDependencies();

        if (empty($dependencies)) {
            return 0;
        }

        $json = json_decode($this->files->get($packageJsonPath), true);
        $devDeps = $json['devDependencies'] ?? [];
        $added = 0;

        foreach ($dependencies as $package => $version) {
            if (($devDeps[$package] ?? null) === $version) {
                continue;
            }

            $devDeps[$package] = $version;
            $added++;
        }

        if ($added > 0) {
            $json['devDependencies'] = $devDeps;

            $this->files->put(
                $packageJsonPath,
                json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
        }

        return $added;
    }

    private function detectPackageManager(): string
    {
        $lockFiles = [
            'bun.lock' => 'bun',
            'pnpm-lock.yaml' => 'pnpm',
            'yarn.lock' => 'yarn',
            'package-lock.json' => 'npm',
        ];

        foreach ($lockFiles as $file => $manager) {
            if ($this->files->exists(base_path($file))) {
                return $manager;
            }
        }

        return 'npm';
    }

    private function showSummary(int $copied, int $depsAdded): void
    {
        $pm = $this->detectPackageManager();

        $this->newLine();
        info('Hotwire installed successfully!');

        if ($copied > 0) {
            $this->line("  Files copied: {$copied}");
        }

        if ($depsAdded > 0) {
            $this->line("  Dependencies added: {$depsAdded}");
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line("  1. Run `{$pm} install` to install dependencies");
        $this->line("  2. Run `{$pm} run dev` to compile assets");
        $this->newLine();
        $this->line('Discover what ships with Hotwire:');
        $this->line('  • `php artisan hotwire:components`         list Blade components and their controllers');
        $this->line('  • `php artisan hotwire:controllers --list` list every available Stimulus controller');
        $this->newLine();
        $this->line('Publishing what you need:');
        $this->line('  • Components in your views → `php artisan hotwire:check --fix`');
        $this->line('    (publishes required controllers and adds any missing npm packages)');
        $this->line('  • Standalone controller    → `php artisan hotwire:controllers <namespace/name>`');
    }
}
