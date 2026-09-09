<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Item;

class Description extends Component
{
    public string $tag = 'p';

    public string $slotName = Item::SLOTS['description']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
