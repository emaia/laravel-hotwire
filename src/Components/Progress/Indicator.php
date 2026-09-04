<?php

namespace Emaia\LaravelHotwire\Components\Progress;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Indicator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.progress-indicator');
    }
}
