<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sheet;

class Close extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sheet-close', [
            'slotName' => Sheet::SLOTS['close']['name'],
        ]);
    }
}
