<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Footer extends Component
{
    public string $tag = 'div';

    public string $slotName = 'item-footer';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
