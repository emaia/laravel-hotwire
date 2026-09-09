<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Field;

class Legend extends Component
{
    public function __construct(
        public string $variant = 'legend',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'legend', 'slotName' => Field::SLOTS['legend']['name']]);
    }
}
