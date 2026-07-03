<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Illuminate\View\Component;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-state-header';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
