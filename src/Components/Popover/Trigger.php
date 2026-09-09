<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Popover;

class Trigger extends Component
{
    public function render()
    {
        return view('hotwire::component-views.popover-trigger', [
            'slotName' => Popover::SLOTS['trigger']['name'],
        ]);
    }
}
