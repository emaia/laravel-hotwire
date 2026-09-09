<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Spinner extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'spinner', 'kind' => 'visual'],
    ];

    public function render()
    {
        return view('hotwire::component-views.spinner', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
