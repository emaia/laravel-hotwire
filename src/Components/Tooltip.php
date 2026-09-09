<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Tooltip extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'tooltip', 'kind' => 'visual'],
        'arrow' => ['name' => 'tooltip-arrow', 'kind' => 'visual'],
    ];

    public function render()
    {
        return view('hotwire::component-views.tooltip', [
            'slotName' => self::SLOTS['root']['name'],
            'arrowSlotName' => self::SLOTS['arrow']['name'],
        ]);
    }
}
