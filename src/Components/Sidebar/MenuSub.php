<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

class MenuSub extends Part
{
    public function __construct()
    {
        parent::__construct('ul', 'sidebar-menu-sub', 'menu-sub');
    }
}
