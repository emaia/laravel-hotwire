<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\OverlayReturnRegion;

class OverlayReturn extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'overlay-return', 'kind' => 'structural'],
    ];

    public function __construct(public string $name)
    {
        OverlayReturnRegion::name($name);
    }

    public function render()
    {
        return view('hotwire::component-views.overlay-return', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
