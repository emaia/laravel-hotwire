<?php

namespace Emaia\LaravelHotwire\Components\Empty;

use Illuminate\View\Component;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
