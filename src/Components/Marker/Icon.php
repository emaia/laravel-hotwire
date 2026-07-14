<?php

namespace Emaia\LaravelHotwire\Components\Marker;

use Illuminate\View\Component;

class Icon extends Component
{
    public function render()
    {
        return view('hotwire::component-views.marker-icon');
    }
}
