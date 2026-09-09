<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Header extends Component
{
    public string $tag = 'thead';

    public string $slotName = Table::SLOTS['header']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
