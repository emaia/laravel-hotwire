<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

class MenuSubItem extends Part
{
    public function __construct()
    {
        parent::__construct('li', 'sidebar-menu-sub-item', 'menu-sub-item');
    }
}
