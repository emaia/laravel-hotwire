<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

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
