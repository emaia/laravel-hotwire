<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class MenuSubItem extends Part
{
    public function __construct()
    {
        parent::__construct('li', Sidebar::SLOTS['menu-sub-item']['name'], 'menu-sub-item');
    }
}
