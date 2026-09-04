<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Group extends Component
{
    public function render()
    {
        return view('hotwire::component-views.item-group');
    }
}
