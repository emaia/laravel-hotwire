<?php

namespace Emaia\LaravelHotwire\Components\AlertDialog;

use Illuminate\View\Component;

class Footer extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'alert-dialog-footer']);
    }
}
