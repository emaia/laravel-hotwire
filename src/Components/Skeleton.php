<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Skeleton extends Component
{
    public string $tag = 'div';

    public string $slotName = 'skeleton';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
