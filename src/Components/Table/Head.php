<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Head extends Component
{
    public string $tag = 'th';

    public string $slotName = Table::SLOTS['head']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
