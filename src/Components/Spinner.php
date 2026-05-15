<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Spinner extends Component
{
    public function render()
    {
        return view('hotwire::component-views.spinner');
    }
}
