<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Input extends Component
{
    public function __construct(
        public string $type = 'text',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-input');
    }
}
