<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class GroupLabel extends Part
{
    public function __construct()
    {
        parent::__construct('div', Sidebar::SLOTS['group-label']['name'], 'group-label');
    }
}
