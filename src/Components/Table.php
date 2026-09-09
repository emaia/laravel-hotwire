<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Table extends Component
{
    public const array SLOTS = [
        'container' => ['name' => 'table-container', 'kind' => 'visual'],
        'root' => ['name' => 'table', 'kind' => 'visual'],
        'header' => ['name' => 'table-header', 'kind' => 'visual'],
        'body' => ['name' => 'table-body', 'kind' => 'visual'],
        'footer' => ['name' => 'table-footer', 'kind' => 'visual'],
        'row' => ['name' => 'table-row', 'kind' => 'visual'],
        'head' => ['name' => 'table-head', 'kind' => 'visual'],
        'cell' => ['name' => 'table-cell', 'kind' => 'visual'],
        'caption' => ['name' => 'table-caption', 'kind' => 'visual'],
    ];

    public function render()
    {
        return view('hotwire::component-views.table', [
            'containerSlotName' => self::SLOTS['container']['name'],
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
