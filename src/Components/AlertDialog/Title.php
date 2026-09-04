<?php

namespace Emaia\LaravelHotwire\Components\AlertDialog;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'h2', 'slotName' => 'alert-dialog-title']);
    }
}
