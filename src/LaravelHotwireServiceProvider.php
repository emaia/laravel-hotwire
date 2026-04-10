<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Components\FlashMessage\FlashMessage;
use Emaia\LaravelHotwire\Components\Loader\Loader;
use Emaia\LaravelHotwire\Components\Modal\Modal;
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
            ->hasCommand(PublishControllersCommand::class);
    }

    public function packageBooted(): void
    {
        $prefix = config('hotwire.prefix', 'hwc');

        Blade::component("{$prefix}-modal", Modal::class);
        Blade::component("{$prefix}-flash-message", FlashMessage::class);
        Blade::component("{$prefix}-loader", Loader::class);
    }
}
