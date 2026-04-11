<?php

namespace Emaia\LaravelHotwire\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
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
    private function packageDependencies(): array
    {
        $path = realpath(__DIR__.'/../../package.json');

        if (! $path) {
            return [];
        }

        $json = json_decode(file_get_contents($path), true);

        return $json['dependencies'] ?? [];
    }

    /** @return array<string, string> */
    private function selectDependencies(): array
    {
        $all = $this->packageDependencies();

        if (empty($all)) {
            return [];
        }

        $core = [];
        $optional = [];

        foreach ($all as $package => $version) {
            if (in_array($package, self::CORE_DEPENDENCIES)) {
                $core[$package] = $version;
            } else {
                $optional[$package] = $version;
            }
        }

        if (empty($optional) || ! $this->input->isInteractive()) {
            return $core;
        }

        $options = collect($optional)->mapWithKeys(
            fn (string $version, string $package) => [$package => "{$package} {$version}"]
        )->toArray();

        $selected = multiselect(
            label: 'Which optional npm dependencies would you like to install?',
            options: $options,
            hint: 'Core dependencies (@hotwired/stimulus, @hotwired/turbo, @emaia/stimulus-dynamic-loader) are always installed.',
        );

        return array_merge($core, array_intersect_key($optional, array_flip($selected)));
    }

    private function addNpmDependencies(): int
    {
        $packageJsonPath = base_path('package.json');

        if (! $this->files->exists($packageJsonPath)) {
            warning('package.json not found. Skipping npm dependency installation.');

            return 0;
        }

        $dependencies = $this->selectDependencies();

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
    }
}
