<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Illuminate\View\Component;

class Title extends Component
{
    public string $tag = 'h2';

    public string $slotName = 'popover-title';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
