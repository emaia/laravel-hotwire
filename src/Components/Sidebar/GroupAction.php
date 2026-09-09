<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class GroupAction extends Part
{
    public function __construct()
    {
        parent::__construct('button', Sidebar::SLOTS['group-action']['name'], 'group-action');
    }
}
