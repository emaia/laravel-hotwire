<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Illuminate\View\Component;

class Separator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.field-separator');
    }
}
