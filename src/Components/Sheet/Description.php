<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sheet;

class Description extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'p', 'slotName' => Sheet::SLOTS['description']['name']]);
    }
}
