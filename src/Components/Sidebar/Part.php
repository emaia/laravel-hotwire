<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

abstract class Part extends Component
{
    public function __construct(
        public ?string $tag = null,
        public ?string $slotName = null,
        public ?string $sidebarName = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-part');
    }
}
