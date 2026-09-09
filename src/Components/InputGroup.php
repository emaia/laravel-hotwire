<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class InputGroup extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'input-group', 'kind' => 'visual'],
        'addon' => ['name' => 'input-group-addon', 'kind' => 'visual'],
        'control' => ['name' => 'input-group-control', 'kind' => 'visual'],
    ];

    public function render()
    {
        return view('hotwire::component-views.input-group', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
