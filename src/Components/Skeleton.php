<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Skeleton extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'skeleton', 'kind' => 'visual'],
    ];

    public string $tag = 'div';

    public string $slotName = self::SLOTS['root']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
