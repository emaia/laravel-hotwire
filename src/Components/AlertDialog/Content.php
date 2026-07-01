<?php

namespace Emaia\LaravelHotwire\Components\AlertDialog;

use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'alert-dialog-body']);
    }
}
