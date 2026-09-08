<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Kbd extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'kbd', 'kind' => 'visual'],
        'group' => ['name' => 'kbd-group', 'kind' => 'visual'],
    ];

    public string $tag = 'kbd';

    public string $slotName = self::SLOTS['root']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
