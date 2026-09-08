<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class Header extends Part
{
    public function __construct()
    {
        parent::__construct('div', Sidebar::SLOTS['header']['name'], 'header');
    }
}
