<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Illuminate\View\Component;

class Head extends Component
{
    public string $tag = 'th';

    public string $slotName = 'table-head';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
