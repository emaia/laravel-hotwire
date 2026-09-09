<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Cell extends Component
{
    public string $tag = 'td';

    public string $slotName = Table::SLOTS['cell']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
