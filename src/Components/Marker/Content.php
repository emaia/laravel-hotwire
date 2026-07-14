<?php

namespace Emaia\LaravelHotwire\Components\Marker;

use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.marker-content');
    }
}
