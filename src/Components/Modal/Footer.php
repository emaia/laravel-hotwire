<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Modal;

class Footer extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => Modal::SLOTS['footer']['name']]);
    }
}
