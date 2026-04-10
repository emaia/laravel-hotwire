<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\ListComponentsCommand;
use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Components\ConfirmDialog\ConfirmDialog;
use Emaia\LaravelHotwire\Components\FlashMessage\FlashMessage;
use Emaia\LaravelHotwire\Components\Loader\Loader;
use Emaia\LaravelHotwire\Components\Modal\Modal;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireServiceProvider extends PackageServiceProvider
{
    /** @var array<string, class-string> */
    public const array COMPONENTS = [
        'modal' => Modal::class,
        'confirm' => ConfirmDialog::class,
        'flash-message' => FlashMessage::class,
        'loader' => Loader::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('hotwire')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([PublishControllersCommand::class, ListComponentsCommand::class]);
    }

    public function packageBooted(): void
    {
        $prefix = config('hotwire.prefix', 'hwc');

        foreach (self::COMPONENTS as $key => $class) {
            Blade::component("{$prefix}-{$key}", $class);
        }
    }
}
