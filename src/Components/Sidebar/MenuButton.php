<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class MenuButton extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $active = false,
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-button');
    }
}
