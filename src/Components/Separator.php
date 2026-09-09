<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Separator extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'separator', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $orientation = 'horizontal',
        public string $slotName = self::SLOTS['root']['name'],
    ) {}

    public function render()
    {
        return view('hotwire::component-views.separator');
    }
}
