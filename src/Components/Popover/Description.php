<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Illuminate\View\Component;

class Description extends Component
{
    public string $tag = 'p';

    public string $slotName = 'popover-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
