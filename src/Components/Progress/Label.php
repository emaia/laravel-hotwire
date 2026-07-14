<?php

namespace Emaia\LaravelHotwire\Components\Progress;

use Illuminate\View\Component;

class Label extends Component
{
    public function render()
    {
        return view('hotwire::component-views.progress-label');
    }
}
