<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\EmptyState;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = EmptyState::SLOTS['description']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
