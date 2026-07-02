<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Actions extends Component
{
    public string $tag = 'div';

    public string $slotName = 'item-actions';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
