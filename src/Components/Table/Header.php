<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Illuminate\View\Component;

class Header extends Component
{
    public string $tag = 'thead';

    public string $slotName = 'table-header';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
