<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Trigger extends Component
{
    public function render()
    {
        return view('hotwire::component-views.popover-trigger');
    }
}
