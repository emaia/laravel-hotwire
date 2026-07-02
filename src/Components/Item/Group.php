<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Group extends Component
{
    public function render()
    {
        return view('hotwire::component-views.item-group');
    }
}
