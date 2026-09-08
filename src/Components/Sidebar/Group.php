<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class Group extends Part
{
    public function __construct()
    {
        parent::__construct('div', Sidebar::SLOTS['group']['name'], 'group');
    }
}
