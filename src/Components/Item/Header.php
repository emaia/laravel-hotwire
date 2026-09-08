<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Item;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = Item::SLOTS['header']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
