<?php

namespace Emaia\LaravelHotwireComponents;

use Emaia\LaravelHotwireComponents\Commands\PublishControllersCommand;
use Emaia\LaravelHotwireComponents\Components\Modal\Modal;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hotwire-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(PublishControllersCommand::class);
    }

    public function packageBooted(): void
    {
        $prefix = config('hotwire-components.prefix', 'hwc');

        Blade::component("{$prefix}-modal", Modal::class);
    }
}
