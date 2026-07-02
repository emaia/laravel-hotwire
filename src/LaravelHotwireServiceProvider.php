<?php

namespace Emaia\LaravelHotwire;

use Emaia\LaravelHotwire\Commands\CheckCommand;
use Emaia\LaravelHotwire\Commands\DocsCommand;
use Emaia\LaravelHotwire\Commands\InstallCommand;
use Emaia\LaravelHotwire\Commands\ListComponentsCommand;
use Emaia\LaravelHotwire\Commands\MakeControllerCommand;
use Emaia\LaravelHotwire\Commands\PublishControllersCommand;
use Emaia\LaravelHotwire\Commands\UiCommand;
use Emaia\LaravelHotwire\Components\Modal\Content;
use Emaia\LaravelHotwire\Components\Modal\Description;
use Emaia\LaravelHotwire\Components\Modal\Footer;
use Emaia\LaravelHotwire\Components\Modal\Header;
use Emaia\LaravelHotwire\Components\Modal\Title;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHotwireServiceProvider extends PackageServiceProvider
{
    private const string COMPONENT_NAMESPACE = 'Emaia\\LaravelHotwire\\Components';

    private const array SUB_COMPONENT_ALIASES = [
        'modal.header' => Header::class,
        'modal.title' => Title::class,
        'modal.description' => Description::class,
        'modal.content' => Content::class,
        'modal.footer' => Footer::class,
        'alert-dialog.header' => Components\AlertDialog\Header::class,
        'alert-dialog.title' => Components\AlertDialog\Title::class,
        'alert-dialog.description' => Components\AlertDialog\Description::class,
        'alert-dialog.content' => Components\AlertDialog\Content::class,
        'alert-dialog.footer' => Components\AlertDialog\Footer::class,
        'field.content' => Components\Field\Content::class,
        'field.legend' => Components\Field\Legend::class,
        'field.separator' => Components\Field\Separator::class,
        'field.set' => Components\Field\FieldSet::class,
        'field.title' => Components\Field\Title::class,
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
                DocsCommand::class,
                UiCommand::class,
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

        $this->registerSubComponents($prefix);

        if ($prefix !== 'hotwire') {
            Blade::componentNamespace(self::COMPONENT_NAMESPACE, 'hotwire');
            Blade::anonymousComponentNamespace('hotwire::components', 'hotwire');

            foreach ($registry->bladeComponentAliases('hotwire') as $alias => $class) {
                Blade::component($class, $alias);
            }

            $this->registerSubComponents('hotwire');
        }
    }

    private function registerSubComponents(string $prefix): void
    {
        foreach (self::SUB_COMPONENT_ALIASES as $suffix => $class) {
            Blade::component($class, "{$prefix}::{$suffix}");
        }
    }
}
