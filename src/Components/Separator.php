<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Separator extends Component
{
    public function __construct(
        public string $orientation = 'horizontal',
        public string $slotName = 'separator',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.separator');
    }
}
