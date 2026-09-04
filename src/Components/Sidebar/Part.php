<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

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
