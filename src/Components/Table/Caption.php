<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Table;

class Caption extends Component
{
    public string $tag = 'caption';

    public string $slotName = Table::SLOTS['caption']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
