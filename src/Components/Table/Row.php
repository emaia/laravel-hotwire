<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Illuminate\View\Component;

class Row extends Component
{
    public string $tag = 'tr';

    public string $slotName = 'table-row';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
