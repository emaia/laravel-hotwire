<?php

namespace Emaia\LaravelHotwire\Components\Alert;

use Illuminate\View\Component;

class Action extends Component
{
    public string $tag = 'div';

    public string $slotName = 'alert-action';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
