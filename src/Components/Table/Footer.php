<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Illuminate\View\Component;

class Footer extends Component
{
    public string $tag = 'tfoot';

    public string $slotName = 'table-footer';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
