<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Support\PackageInstaller;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class UiCommand extends Command
{
    public $signature = 'hotwire:ui
                         {--css-only : Only inject CSS import, skip JS}
                         {--js-only : Only inject JS import, skip CSS}';

    public $description = 'Install Basecoat UI CSS framework into your Laravel application';

    private const string BASECOAT_NPM = 'basecoat-css';

    private const string CSS_IMPORT = '@import "basecoat-css";';

    private const string JS_IMPORT = "import 'basecoat-css/all';";

    private const string INDEX_IMPORT = 'import "./ui";';

    public function __construct(
        private readonly Filesystem       $files,
        private readonly PackageInstaller $packageInstaller,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cssOnly = (bool)$this->option('css-only');
        $jsOnly = (bool)$this->option('js-only');

        $steps = [];

        $depsAdded = $this->addNpmDependency();
        if ($depsAdded > 0) {
            $steps[] = 'Added basecoat-css to devDependencies';
        }

        if (!$jsOnly) {
            $cssInjected = $this->injectCssImport();
            if ($cssInjected) {
                $steps[] = 'Injected ' . self::CSS_IMPORT . ' into resources/css/app.css';
            }
        }

        if (!$cssOnly) {
            $jsInstalled = $this->installJs();
            if ($jsInstalled) {
                $steps[] = 'Created resources/js/libs/ui.js with Basecoat JS import';
            }
        }

        $this->showSummary($steps);

        return self::SUCCESS;
    }

    private function addNpmDependency(): int
    {
        $packageJsonPath = base_path('package.json');

        if (!$this->files->exists($packageJsonPath)) {
            warning('package.json not found. Skipping npm dependency installation.');

            return 0;
        }

        return count($this->packageInstaller->addDevDependencies(
            $this->files,
            [self::BASECOAT_NPM => $this->resolveBasecoatVersion()],
            updateExisting: false,
        ));
    }

    private function resolveBasecoatVersion(): string
    {
        $path = realpath(__DIR__ . '/../../package.json');

        if (!$path) {
            return '^0.3';
        }

        $json = json_decode(file_get_contents($path), true);
        $version = $json['dependencies'][self::BASECOAT_NPM] ?? null;

        if ($version) {
            return $version;
        }

        return '^0.3';
    }

    private function injectCssImport(): bool
    {
        $cssPath = resource_path('css/app.css');

        if ($this->files->exists($cssPath)) {
            $content = $this->files->get($cssPath);

            if (str_contains($content, self::CSS_IMPORT)) {
                return false;
            }

            $tailwindImport = '@import "tailwindcss";';
            if (str_contains($content, $tailwindImport)) {
                $content = str_replace(
                    $tailwindImport,
                    $tailwindImport . "\n" . self::CSS_IMPORT,
                    $content
                );
            } else {
                $content = self::CSS_IMPORT . "\n" . $content;
            }

            $this->files->put($cssPath, $content);

            return true;
        }

        $this->files->ensureDirectoryExists(dirname($cssPath));
        $this->files->put(
            $cssPath,
            '@import "tailwindcss";' . "\n" . self::CSS_IMPORT . "\n"
        );

        return true;
    }

    private function installJs(): bool
    {
        $uiJsPath = resource_path('js/libs/ui.js');
        $indexJsPath = resource_path('js/libs/index.js');
        $changed = false;

        if ($this->writeOrSkipJsFile($uiJsPath, self::JS_IMPORT . "\n")) {
            $changed = true;
        }

        if ($this->files->exists($indexJsPath)) {
            $content = $this->files->get($indexJsPath);

            if (!str_contains($content, self::INDEX_IMPORT)) {
                $this->files->put($indexJsPath, $content . self::INDEX_IMPORT . "\n");
                $changed = true;
            }
        } else {
            $this->files->ensureDirectoryExists(dirname($indexJsPath));
            $this->files->put($indexJsPath, self::INDEX_IMPORT . "\n");
            $changed = true;
        }

        return $changed;
    }

    private function writeOrSkipJsFile(string $path, string $content): bool
    {
        if ($this->files->exists($path)) {
            if (str_contains($this->files->get($path), self::JS_IMPORT)) {
                return false;
            }
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);

        return true;
    }

    /** @param string[] $steps */
    private function showSummary(array $steps): void
    {
        $pm = $this->packageInstaller->detect($this->files);

        foreach ($steps as $step) {
            info($step);
        }

        $this->newLine();
        info('Basecoat UI installed successfully!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line("  1. Run `$pm install` to install the package");
        $this->line("  2. Run `$pm run dev` to compile assets");
        $this->line('  3. Start using Basecoat classes: btn, card, input, etc.');
        $this->newLine();
        $this->line('  Docs: https://basecoatui.com');
    }
}
