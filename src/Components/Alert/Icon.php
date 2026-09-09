<?php

namespace Emaia\LaravelHotwire\Components\Alert;

use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Icon extends Component
{
    public string $tag = 'span';

    public string $slotName = Alert::SLOTS['icon']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
