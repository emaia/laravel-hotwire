<?php

namespace Emaia\LaravelHotwire\Components\ButtonGroup;

use Illuminate\View\Component;

class Text extends Component
{
    public function __construct(
        public string $as = 'div',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.button-group-text');
    }
}
