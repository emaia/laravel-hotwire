<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Table extends Component
{
    public function render()
    {
        return view('hotwire::component-views.table');
    }
}
