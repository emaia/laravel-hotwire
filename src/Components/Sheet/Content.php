<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sheet-content');
    }
}
