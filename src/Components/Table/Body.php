<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Body extends Component
{
    public string $tag = 'tbody';

    public string $slotName = Table::SLOTS['body']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
