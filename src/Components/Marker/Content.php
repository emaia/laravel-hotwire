<?php

namespace Emaia\LaravelHotwire\Components\Marker;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Marker;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.marker-content', [
            'slotName' => Marker::SLOTS['content']['name'],
        ]);
    }
}
