<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Illuminate\View\Component;

class Content extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-state-content';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
