<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class EmptyState extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'empty-state', 'kind' => 'visual'],
        'header' => ['name' => 'empty-state-header', 'kind' => 'visual'],
        'media' => ['name' => 'empty-state-media', 'kind' => 'visual'],
        'title' => ['name' => 'empty-state-title', 'kind' => 'visual'],
        'description' => ['name' => 'empty-state-description', 'kind' => 'visual'],
        'content' => ['name' => 'empty-state-content', 'kind' => 'visual'],
    ];

    public string $tag = 'div';

    public string $slotName = self::SLOTS['root']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
