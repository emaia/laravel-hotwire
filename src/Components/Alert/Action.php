<?php

namespace Emaia\LaravelHotwire\Components\Alert;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Action extends Component
{
    public string $tag = 'div';

    public string $slotName = 'alert-action';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
