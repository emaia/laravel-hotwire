<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Table extends Component
{
    public function render()
    {
        return view('hotwire::component-views.table');
    }
}
