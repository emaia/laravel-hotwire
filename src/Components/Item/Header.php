<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = 'item-header';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
