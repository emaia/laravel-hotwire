<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Title extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-state-title';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
