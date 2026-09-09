<?php

namespace Emaia\LaravelHotwire\Components\EmptyState;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\EmptyState;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = EmptyState::SLOTS['header']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
