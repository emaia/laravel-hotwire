<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Dropdown;

class TriggerIcon extends Component
{
    public string $tag = 'span';

    public string $slotName = Dropdown::SLOTS['trigger-icon']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
