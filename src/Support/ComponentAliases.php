<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Components;

final class ComponentAliases
{
    /** @return array<string, class-string> */
    public static function subComponents(): array
    {
        return [
            'modal.header' => Components\Modal\Header::class,
            'modal.title' => Components\Modal\Title::class,
            'modal.description' => Components\Modal\Description::class,
            'modal.content' => Components\Modal\Content::class,
            'modal.footer' => Components\Modal\Footer::class,
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
            'empty-state.content' => Components\EmptyState\Content::class,
            'empty-state.description' => Components\EmptyState\Description::class,
            'empty-state.header' => Components\EmptyState\Header::class,
            'empty-state.media' => Components\EmptyState\Media::class,
            'empty-state.title' => Components\EmptyState\Title::class,
            'field.content' => Components\Field\Content::class,
            'field.description' => Components\Field\Description::class,
            'field.label' => Components\Field\Label::class,
            'field.legend' => Components\Field\Legend::class,
            'field.separator' => Components\Field\Separator::class,
            'field.set' => Components\Field\Set::class,
            'field.title' => Components\Field\Title::class,
            'table.header' => Components\Table\Header::class,
            'table.body' => Components\Table\Body::class,
            'table.footer' => Components\Table\Footer::class,
            'table.row' => Components\Table\Row::class,
            'table.head' => Components\Table\Head::class,
            'table.cell' => Components\Table\Cell::class,
            'table.caption' => Components\Table\Caption::class,
            'tabs.list' => Components\Tabs\TabList::class,
            'tabs.trigger' => Components\Tabs\Trigger::class,
            'tabs.panel' => Components\Tabs\Panel::class,
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
    }
}
