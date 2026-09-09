<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Marker extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'marker', 'kind' => 'visual'],
        'icon' => ['name' => 'marker-icon', 'kind' => 'visual'],
        'content' => ['name' => 'marker-content', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $variant = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.marker', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
