<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.hover-card-content');
    }
}
