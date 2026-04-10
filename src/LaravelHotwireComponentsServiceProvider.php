<?php

namespace Emaia\LaravelHotwireComponents;

use Emaia\LaravelHotwireComponents\Commands\PublishControllersCommand;
use Emaia\LaravelHotwireComponents\Components\FlashMessage\FlashMessage;
use Emaia\LaravelHotwireComponents\Components\Loader\Loader;
use Emaia\LaravelHotwireComponents\Components\Modal\Modal;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hwc')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(PublishControllersCommand::class);
    }

    public function packageBooted(): void
    {
        $prefix = config('hwc.prefix', 'hwc');

        Blade::component("{$prefix}-modal", Modal::class);
        Blade::component("{$prefix}-flash-message", FlashMessage::class);
        Blade::component("{$prefix}-loader", Loader::class);
    }
}
