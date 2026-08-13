<?php

namespace Emaia\LaravelHotwire;

use Closure;
use Emaia\LaravelHotwire\Commands\CheckCommand;
use Emaia\LaravelHotwire\Commands\DocsCommand;
use Emaia\LaravelHotwire\Commands\IdeJsonCommand;
use Emaia\LaravelHotwire\Commands\InstallCommand;
use Emaia\LaravelHotwire\Commands\ListComponentsCommand;
use Emaia\LaravelHotwire\Commands\MakeControllerCommand;
use Emaia\LaravelHotwire\Commands\MakePresetCommand;
use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\HotwireTagCompiler;
use Emaia\LaravelHotwire\Support\ViteControllerAssetResolver;
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use RuntimeException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use WeakMap;

class LaravelHotwireServiceProvider extends PackageServiceProvider
{
    /** @var WeakMap<object, Vite>|null */
    private static ?WeakMap $viteControllerPreloadUsage = null;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('hotwire')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasCommands([
                InstallCommand::class,
                MakeControllerCommand::class,
                MakePresetCommand::class,
                PublishControllersCommand::class,
                ListComponentsCommand::class,
                CheckCommand::class,
                DocsCommand::class,
                IdeJsonCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        $prefix = config('hotwire.prefix', 'hw');
        $registry = HotwireRegistry::make();

        foreach ($this->componentPrefixes($prefix) as $componentPrefix) {
            Blade::anonymousComponentNamespace('hotwire::components', $componentPrefix);

            foreach ($registry->bladeComponentAliases($componentPrefix) as $alias => $class) {
                Blade::component($class, $alias);
            }

            $this->registerSubComponents($componentPrefix);
        }

        $this->registerTagCompiler($prefix);
        $this->registerToastMacro();
        $this->registerViteControllerPreloadsMacro();
    }

    private function registerToastMacro(): void
    {
        if (TurboStreamBuilder::hasMacro('toast')) {
            return;
        }

        TurboStreamBuilder::macro('toast', function (
            string $type,
            string $message,
            ?string $description = null,
            ?string $position = null,
            string $target = 'toaster',
        ) {
            /** @var TurboStreamBuilder $this */
            return $this->append($target, Blade::render(
                '<x-hw::toast :type="$type" :message="$message" :description="$description" :position="$position" />',
                compact('type', 'message', 'description', 'position'),
            ));
        });
    }

    private function registerViteControllerPreloadsMacro(): void
    {
        self::$viteControllerPreloadUsage ??= new WeakMap;
        $usage = self::$viteControllerPreloadUsage;
        $warningsHtml = static function (array $warnings): string {
            if ($warnings === [] || ! config('app.debug', false)) {
                return '';
            }

            return collect($warnings)
                ->map(fn (string $warning): string => '<!-- Laravel Hotwire controller preload warning: '.str_replace('-->', '--&gt;', $warning).' -->')
                ->implode("\n");
        };

        if (! Vite::hasMacro('controllerPreloads')) {
            Vite::macro('controllerPreloads', function (iterable $identifiers, ?string $buildDirectory = null) use ($usage, $warningsHtml): HtmlString {
                /** @var Vite $this */
                $identifiers = array_values(is_array($identifiers) ? $identifiers : iterator_to_array($identifiers));

                if ($identifiers === [] || $this->isRunningHot()) {
                    return new HtmlString('');
                }

                $buildDirectory ??= $this->buildDirectory;
                $usage[app()] = $this;

                try {
                    $manifest = $this->manifest($buildDirectory);
                    $resolver = new ViteControllerAssetResolver(
                        $manifest,
                        fn (string $_manifestKey, string $file): string => $this->assetPath($buildDirectory.'/'.$file),
                    );

                    $assets = [];
                    $warnings = [];

                    foreach ($identifiers as $identifier) {
                        try {
                            array_push($assets, ...$resolver->resolve([$identifier]));
                        } catch (RuntimeException $exception) {
                            $warnings[] = $exception->getMessage();
                            Log::warning($exception->getMessage());
                        }
                    }
                } catch (ViteException $exception) {
                    $warnings = [$exception->getMessage()];
                    Log::warning($exception->getMessage());

                    return new HtmlString($warningsHtml($warnings));
                }

                $tags = $warningsHtml($warnings);

                foreach ($assets as $asset) {
                    if (array_key_exists($asset->url, $this->preloadedAssets)) {
                        continue;
                    }

                    $chunk = $manifest[$asset->manifestKey];

                    if ($asset->style && ($chunk['file'] ?? null) !== $asset->file) {
                        $chunk['file'] = $asset->file;

                        if ($this->integrityKey !== false) {
                            unset($chunk[$this->integrityKey]);
                        }
                    }

                    $tags .= $this->makePreloadTagForChunk(
                        $asset->source,
                        $asset->url,
                        $chunk,
                        $manifest,
                    );
                }

                return new HtmlString($tags);
            });
        }

        $this->app->terminating(function () use ($usage): void {
            $container = app();
            $vite = $usage[$container] ?? null;

            if ($vite === null) {
                return;
            }

            unset($usage[$container]);

            self::flushViteState($vite);
        });
    }

    /** Flush request-scoped Vite state across Laravel versions. */
    private static function flushViteState(object $vite): void
    {
        if (is_callable([$vite, 'flush'])) {
            $vite->flush();

            return;
        }

        Closure::bind(function (): void {
            $this->preloadedAssets = [];
        }, $vite, $vite)();
    }

    /** @return string[] */
    private function componentPrefixes(string $prefix): array
    {
        return array_values(array_unique([$prefix, 'hw']));
    }

    private function registerTagCompiler(string $prefix): void
    {
        $compiler = new HotwireTagCompiler(
            app('blade.compiler')->getClassComponentAliases(),
            app('blade.compiler')->getClassComponentNamespaces(),
            app('blade.compiler'),
            $this->componentPrefixes($prefix),
        );

        app()->bind('hotwire.compiler', fn () => $compiler);

        app('blade.compiler')->precompiler(fn (string $value): string => $compiler->compile($value));
    }

    private function registerSubComponents(string $prefix): void
    {
        foreach (ComponentAliases::subComponents() as $suffix => $class) {
            Blade::component($class, "{$prefix}::{$suffix}");
        }
    }
}
