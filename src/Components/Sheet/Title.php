<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sheet;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'h2', 'slotName' => Sheet::SLOTS['title']['name']]);
    }
}
