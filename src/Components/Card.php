<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Card extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'card', 'kind' => 'visual'],
        'header' => ['name' => 'card-header', 'kind' => 'visual'],
        'title' => ['name' => 'card-title', 'kind' => 'visual'],
        'description' => ['name' => 'card-description', 'kind' => 'visual'],
        'action' => ['name' => 'card-action', 'kind' => 'visual'],
        'content' => ['name' => 'card-content', 'kind' => 'visual'],
        'footer' => ['name' => 'card-footer', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $size = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.card', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
