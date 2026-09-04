<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Head extends Component
{
    public string $tag = 'th';

    public string $slotName = 'table-head';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
