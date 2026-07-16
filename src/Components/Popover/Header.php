<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Illuminate\View\Component;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = 'popover-header';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
