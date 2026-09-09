<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class MenuSub extends Part
{
    public function __construct()
    {
        parent::__construct('ul', Sidebar::SLOTS['menu-sub']['name'], 'menu-sub');
    }
}
