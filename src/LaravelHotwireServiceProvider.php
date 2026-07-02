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
        'alert.action' => Components\Alert\Action::class,
        'alert.description' => Components\Alert\Description::class,
        'alert.title' => Components\Alert\Title::class,
        'button-group.separator' => Components\ButtonGroup\Separator::class,
        'button-group.text' => Components\ButtonGroup\Text::class,
        'card.action' => Components\Card\Action::class,
        'card.content' => Components\Card\Content::class,
        'card.description' => Components\Card\Description::class,
        'card.footer' => Components\Card\Footer::class,
        'card.header' => Components\Card\Header::class,
        'card.title' => Components\Card\Title::class,
        'empty.content' => Components\Empty\Content::class,
        'empty.description' => Components\Empty\Description::class,
        'empty.header' => Components\Empty\Header::class,
        'empty.media' => Components\Empty\Media::class,
        'empty.title' => Components\Empty\Title::class,
        'field.content' => Components\Field\Content::class,
        'field.description' => Components\Field\Description::class,
        'field.label' => Components\Field\Label::class,
        'field.legend' => Components\Field\Legend::class,
        'field.separator' => Components\Field\Separator::class,
        'field.set' => Components\Field\FieldSet::class,
        'field.title' => Components\Field\Title::class,
        'table.header' => Components\Table\Header::class,
        'table.body' => Components\Table\Body::class,
        'table.footer' => Components\Table\Footer::class,
        'table.row' => Components\Table\Row::class,
        'table.head' => Components\Table\Head::class,
        'table.cell' => Components\Table\Cell::class,
        'table.caption' => Components\Table\Caption::class,
        'item.actions' => Components\Item\Actions::class,
        'item.content' => Components\Item\Content::class,
        'item.description' => Components\Item\Description::class,
        'item.footer' => Components\Item\Footer::class,
        'item.group' => Components\Item\Group::class,
        'item.header' => Components\Item\Header::class,
        'item.media' => Components\Item\Media::class,
        'item.separator' => Components\Item\Separator::class,
        'item.title' => Components\Item\Title::class,
        'kbd.group' => Components\Kbd\Group::class,
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
