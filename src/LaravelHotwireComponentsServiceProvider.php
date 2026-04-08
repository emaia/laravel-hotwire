<?php

namespace Emaia\LaravelHotwireComponents;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Emaia\LaravelHotwireComponents\Commands\LaravelHotwireComponentsCommand;

class LaravelHotwireComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-hotwire-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_hotwire_components_table')
            ->hasCommand(LaravelHotwireComponentsCommand::class);
    }
}
