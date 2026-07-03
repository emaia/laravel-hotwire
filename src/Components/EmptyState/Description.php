<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Illuminate\View\Component;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-state-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
