<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\CheckCommand;
use Emaia\LaravelHotwire\Commands\DocsCommand;
use Emaia\LaravelHotwire\Commands\InstallCommand;
use Emaia\LaravelHotwire\Commands\ListComponentsCommand;
use Emaia\LaravelHotwire\Commands\MakeControllerCommand;
use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireServiceProvider extends PackageServiceProvider
{
    private const string COMPONENT_NAMESPACE = 'Emaia\\LaravelHotwire\\Components';

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
            ]);
    }

    public function packageBooted(): void
    {
        $prefix = config('hotwire.prefix', 'hwc');
        $registry = HotwireRegistry::make();

        if ($prefix === 'hwc') {
            Blade::componentNamespace(self::COMPONENT_NAMESPACE, 'hwc');
        }

        Blade::anonymousComponentNamespace('hotwire::components', $prefix);

        foreach ($registry->bladeComponentAliases($prefix) as $alias => $class) {
            Blade::component($class, $alias);
        }

        if ($prefix !== 'hotwire') {
            Blade::componentNamespace(self::COMPONENT_NAMESPACE, 'hotwire');
            Blade::anonymousComponentNamespace('hotwire::components', 'hotwire');

            foreach ($registry->bladeComponentAliases('hotwire') as $alias => $class) {
                Blade::component($class, $alias);
            }
        }
    }
}
