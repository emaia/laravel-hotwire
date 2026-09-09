<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class ButtonGroup extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'button-group', 'kind' => 'visual'],
        'separator' => ['name' => 'button-group-separator', 'kind' => 'visual'],
        'text' => ['name' => 'button-group-text', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $orientation = 'horizontal',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.button-group', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
