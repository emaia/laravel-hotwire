<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Close extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sheet-close');
    }
}
