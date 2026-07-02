<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Illuminate\View\Component;

class Legend extends Component
{
    public function __construct(
        public string $variant = 'legend',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'legend', 'slotName' => 'field-legend']);
    }
}
