<?php

namespace Emaia\LaravelHotwire\Components\Table;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Body extends Component
{
    public string $tag = 'tbody';

    public string $slotName = 'table-body';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}
