<?php

namespace Emaia\LaravelHotwire\Components\Empty;

use Illuminate\View\Component;

class Content extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-content';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
