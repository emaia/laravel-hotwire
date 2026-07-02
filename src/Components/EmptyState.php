<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class EmptyState extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
