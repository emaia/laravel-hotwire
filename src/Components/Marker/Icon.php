<?php

namespace Emaia\LaravelHotwire\Components\Marker;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Marker;

class Icon extends Component
{
    public function render()
    {
        return view('hotwire::component-views.marker-icon', [
            'slotName' => Marker::SLOTS['icon']['name'],
        ]);
    }
}
