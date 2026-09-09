<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Optimistic extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'optimistic', 'kind' => 'structural'],
    ];

    public function __construct(
        public string $target = '',
        public string $targets = '',
        public string $action = 'replace',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.optimistic', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}
