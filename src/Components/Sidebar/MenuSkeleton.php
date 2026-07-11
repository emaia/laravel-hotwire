<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class MenuSkeleton extends Component
{
    public function __construct(
        public bool $showIcon = false,
        public string $width = '70%',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-skeleton');
    }
}
