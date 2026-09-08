<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class MenuItem extends Part
{
    public function __construct()
    {
        parent::__construct('li', Sidebar::SLOTS['menu-item']['name'], 'menu-item');
    }
}
