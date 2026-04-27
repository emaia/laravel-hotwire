<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\CheckCommand;
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
            ]);
    }

    public function packageBooted(): void
    {
        $prefix = config('hotwire.prefix', 'hwc');
        $registry = HotwireRegistry::make();

        foreach ($registry->bladeComponentAliases($prefix) as $alias => $class) {
            Blade::component($class, $alias);
        }

        if ($prefix !== 'hotwire') {
            foreach ($registry->bladeComponentAliases('hotwire') as $alias => $class) {
                Blade::component($class, $alias);
            }
        }
    }
}
