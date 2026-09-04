<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Caption extends Component
{
    public string $tag = 'caption';

    public string $slotName = 'table-caption';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
