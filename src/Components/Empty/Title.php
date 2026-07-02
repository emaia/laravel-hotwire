<?php

namespace Emaia\LaravelHotwire\Components\Empty;

use Illuminate\View\Component;

class Title extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-title';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
