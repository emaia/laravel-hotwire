<?php

namespace Emaia\LaravelHotwire\Components\InputGroup;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Addon extends Component
{
    public function __construct(
        public string $align = 'inline-start',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.input-group-addon');
    }
}
