<?php

namespace Emaia\LaravelHotwire\Components\Empty;

use Illuminate\View\Component;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-header';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
