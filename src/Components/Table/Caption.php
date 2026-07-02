<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Illuminate\View\Component;

class Caption extends Component
{
    public string $tag = 'caption';

    public string $slotName = 'table-caption';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
