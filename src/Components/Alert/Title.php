<?php

namespace Emaia\LaravelHotwire\Components\Alert;

use Illuminate\View\Component;

class Title extends Component
{
    public string $tag = 'div';

    public string $slotName = 'alert-title';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
