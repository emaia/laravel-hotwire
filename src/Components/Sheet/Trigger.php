<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Trigger extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sheet-trigger');
    }
}
