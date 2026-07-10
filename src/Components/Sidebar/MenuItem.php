<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

class MenuItem extends Part
{
    public function __construct()
    {
        parent::__construct('li', 'sidebar-menu-item', 'menu-item');
    }
}
