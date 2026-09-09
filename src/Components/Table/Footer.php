<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Footer extends Component
{
    public string $tag = 'tfoot';

    public string $slotName = Table::SLOTS['footer']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
