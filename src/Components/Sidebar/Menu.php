<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class Menu extends Part
{
    public function __construct()
    {
        parent::__construct('ul', Sidebar::SLOTS['menu']['name'], 'menu');
    }
}
