<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class MenuBadge extends Part
{
    public function __construct()
    {
        parent::__construct('div', Sidebar::SLOTS['menu-badge']['name'], 'menu-badge');
    }
}
