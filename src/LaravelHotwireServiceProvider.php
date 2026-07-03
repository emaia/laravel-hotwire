<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\CheckCommand;
use Emaia\LaravelHotwire\Commands\DocsCommand;
use Emaia\LaravelHotwire\Commands\IdeJsonCommand;
use Emaia\LaravelHotwire\Commands\InstallCommand;
use Emaia\LaravelHotwire\Commands\ListComponentsCommand;
use Emaia\LaravelHotwire\Commands\MakeControllerCommand;
use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Commands\UiCommand;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\HotwireTagCompiler;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hotwire')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                InstallCommand::class,
                MakeControllerCommand::class,
                PublishControllersCommand::class,
                ListComponentsCommand::class,
                CheckCommand::class,
                DocsCommand::class,
                UiCommand::class,
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
