<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Content extends Component
{
    public string $tag = 'div';

    public string $slotName = 'item-content';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
