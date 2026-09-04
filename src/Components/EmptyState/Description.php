<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-state-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
