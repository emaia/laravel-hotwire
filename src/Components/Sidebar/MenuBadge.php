<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

class MenuBadge extends Part
{
    public function __construct()
    {
        parent::__construct('div', 'sidebar-menu-badge', 'menu-badge');
    }
}
