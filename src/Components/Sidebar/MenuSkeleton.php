<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sidebar;

class MenuSkeleton extends Component
{
    public function __construct(
        public bool $showIcon = false,
        public string $width = '70%',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-skeleton', [
            'slotName' => Sidebar::SLOTS['menu-skeleton']['name'],
            'iconSlotName' => Sidebar::SLOTS['menu-skeleton-icon']['name'],
            'textSlotName' => Sidebar::SLOTS['menu-skeleton-text']['name'],
        ]);
    }
}
