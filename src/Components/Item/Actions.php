<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Item;

class Actions extends Component
{
    public string $tag = 'div';

    public string $slotName = Item::SLOTS['actions']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
