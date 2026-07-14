<?php

namespace Emaia\LaravelHotwire\Components\Progress;

use Illuminate\View\Component;

class Indicator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.progress-indicator');
    }
}
