<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

class GroupContent extends Part
{
    public function __construct()
    {
        parent::__construct('div', 'sidebar-group-content', 'group-content');
    }
}
