<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Item;

class Group extends Component
{
    public function render()
    {
        return view('hotwire::component-views.item-group', [
            'slotName' => Item::SLOTS['group']['name'],
        ]);
    }
}
