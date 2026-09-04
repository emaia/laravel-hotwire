<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Cell extends Component
{
    public string $tag = 'td';

    public string $slotName = 'table-cell';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
