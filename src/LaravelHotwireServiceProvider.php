<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\CheckCommand;
use Emaia\LaravelHotwire\Commands\InstallCommand;
use Emaia\LaravelHotwire\Commands\ListComponentsCommand;
use Emaia\LaravelHotwire\Commands\MakeControllerCommand;
use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Components\ConfirmDialog;
use Emaia\LaravelHotwire\Components\FlashMessage;
use Emaia\LaravelHotwire\Components\Loader;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Components\Optimistic;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireServiceProvider extends PackageServiceProvider
{
    /** @var array<string, class-string> */
    public const array COMPONENTS = [
        'modal' => Modal::class,
        'confirm-dialog' => ConfirmDialog::class,
        'flash-message' => FlashMessage::class,
        'loader' => Loader::class,
        'optimistic' => Optimistic::class,
    ];

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

        Blade::componentNamespace('Emaia\\LaravelHotwire\\Components', $prefix);

        if ($prefix !== 'hotwire') {
            Blade::componentNamespace('Emaia\\LaravelHotwire\\Components', 'hotwire');
        }

    }
}
