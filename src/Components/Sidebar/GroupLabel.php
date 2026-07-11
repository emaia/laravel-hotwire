<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

class GroupLabel extends Part
{
    public function __construct()
    {
        parent::__construct('div', 'sidebar-group-label', 'group-label');
    }
}
