<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Alert extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'alert', 'kind' => 'visual'],
        'icon' => ['name' => 'alert-icon', 'kind' => 'visual'],
        'title' => ['name' => 'alert-title', 'kind' => 'visual'],
        'description' => ['name' => 'alert-description', 'kind' => 'visual'],
        'action' => ['name' => 'alert-action', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $variant = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.alert', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
