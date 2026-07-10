<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class MenuSubButton extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $active = false,
        public string $size = 'md',
        public string $type = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-sub-button');
    }
}
