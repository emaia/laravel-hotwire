<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public string $label = 'Toggle Sidebar',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-trigger');
    }
}
