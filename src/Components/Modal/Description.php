<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Illuminate\View\Component;

class Description extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'p', 'slotName' => 'modal-description']);
    }
}
