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
use Emaia\LaravelHotwire\Commands\StylesCommand;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\HotwireTagCompiler;
use Emaia\LaravelHotwire\Support\SessionToast;
use Emaia\LaravelHotwire\Support\ViteControllerAssetResolver;
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use WeakMap;

class LaravelHotwireServiceProvider extends ServiceProvider
{
    /** @var WeakMap<object, Vite>|null */
    private static ?WeakMap $viteControllerPreloadUsage = null;

    private const COMMANDS = [
        InstallCommand::class,
        MakeControllerCommand::class,
        MakePresetCommand::class,
        StylesCommand::class,
        PublishControllersCommand::class,
        ListComponentsCommand::class,
        CheckCommand::class,
        DocsCommand::class,
        IdeJsonCommand::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(self::packagePath('config/hotwire.php'), 'hotwire');

        $this->app->singleton(CssModuleManifest::class, fn (): CssModuleManifest => CssModuleManifest::load());
        $this->app->scopedIf(ComponentId::class);
        $this->app->scopedIf(SessionToast::class);
    }

    public function boot(): void
    {
        $this->bootPackageResources();
        $this->bootBladeIntegration();
    }

    /**
     * Register the component aliases, tag compiler and macros.
     *
     * Public and separate from boot() because it is the only part that is safe to re-run
     * after the configured prefix changes — tests rely on that.
     */
    public function bootBladeIntegration(): void
    {
        $prefix = config('hotwire.prefix', 'hw');
        $registry = HotwireRegistry::make();

        foreach (ComponentAliases::prefixes($prefix) as $componentPrefix) {
            Blade::anonymousComponentNamespace('hotwire::components', $componentPrefix);

            foreach ($registry->bladeComponentAliases($componentPrefix) as $alias => $class) {
                Blade::component($class, $alias);
            }

            $this->registerSubComponents($componentPrefix);
        }

        $this->registerTagCompiler($prefix);
        $this->registerToastMacro();
        $this->registerRedirectToastMacro();
        $this->registerViteControllerPreloadsMacro();
    }

    /**
     * Register views, translations, commands and the publish tags documented in the README.
     */
    private function bootPackageResources(): void
    {
        $views = self::packagePath('resources/views');
        $translations = self::packagePath('resources/lang');

        $this->loadViewsFrom($views, 'hotwire');
        $this->loadTranslationsFrom($translations, 'hotwire');
        $this->loadJsonTranslationsFrom($translations);
        $this->loadJsonTranslationsFrom(lang_path('vendor/hotwire'));
        $this->commands(self::COMMANDS);

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([self::packagePath('config/hotwire.php') => config_path('hotwire.php')], 'hotwire-config');
        $this->publishes([$views => base_path('resources/views/vendor/hotwire')], 'hotwire-views');
        $this->publishes([$translations => lang_path('vendor/hotwire')], 'hotwire-translations');
    }

    private static function packagePath(string $path): string
    {
        return dirname(__DIR__).'/'.$path;
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

    private function registerRedirectToastMacro(): void
    {
        if (RedirectResponse::hasMacro('toast')) {
            return;
        }

        RedirectResponse::macro('toast', function (
            string $type,
            string $message,
            ?string $description = null,
            ?string $position = null,
        ) {
            /** @var RedirectResponse $this */
            return $this->with('toast', array_filter([
                'type' => $type,
                'message' => $message,
                'description' => $description,
                'position' => $position,
            ], fn (?string $value): bool => $value !== null));
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

    private function registerTagCompiler(string $prefix): void
    {
        $compiler = new HotwireTagCompiler(
            app('blade.compiler')->getClassComponentAliases(),
            app('blade.compiler')->getClassComponentNamespaces(),
            app('blade.compiler'),
            ComponentAliases::prefixes($prefix),
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
