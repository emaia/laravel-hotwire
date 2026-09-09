<?php

namespace Emaia\LaravelHotwire\Components\Alert;

use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Action extends Component
{
    public string $tag = 'div';

    public string $slotName = Alert::SLOTS['action']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
