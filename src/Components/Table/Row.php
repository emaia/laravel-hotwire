<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Row extends Component
{
    public string $tag = 'tr';

    public string $slotName = Table::SLOTS['row']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
