<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Description extends Component
{
    public string $tag = 'p';

    public string $slotName = 'item-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
